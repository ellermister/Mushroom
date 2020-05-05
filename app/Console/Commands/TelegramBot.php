<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2020/4/24
 * Time: 1:33
 */

namespace App\Console\Commands;

use Longman\TelegramBot\Request;
use Mushroom\Application;
use Mushroom\Core\Console\Command;

class TelegramBot extends Command
{

    protected $signature = 'telegram:bot';

    protected $description = 'telegram bot';

    protected $blockKeyword = [];

    protected $blockUpdateTime1 = 0;
    protected $blockUpdateTime2 = 0;

    protected $daemon = true;

    /**
     * @var Application
     */
    protected $app;

    public function __construct()
    {
        if (env('HTTP_PROXY')) {
            putenv('HTTP_PROXY=' . env('HTTP_PROXY'));
            putenv('HTTPS_PROXY=' . env('HTTP_PROXY'));
        }
    }

    /**
     * Execute the console command.
     *
     * @param Application $application
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function handle(Application $application)
    {
        $bot_api_key = $application->getConfig('telegram.bot_api_key');
        $bot_username = $application->getConfig('telegram.bot_username');
        $this->app = $application;
        $this->blockKeyword = $this->app->getConfig('telegram.block_keywords');

        $telegram = new \Longman\TelegramBot\Telegram($bot_api_key, $bot_username);
        $mysql_credentials = [
            'host'     => '127.0.0.1',
            'user'     => 'root',
            'password' => 'root',
            'database' => 'db_tg',
            'port'     => 3307
        ];
//        $telegram->enableMySql($mysql_credentials);
//        $telegram->handleGetUpdates();
        $telegram->useGetUpdatesWithoutDatabase(true);
        $handler = true;
        while ($handler) {
            $messages = $telegram->handleGetUpdates();
            if (isset($messages->result) && is_array($messages->result)) {
                foreach ($messages->result as $item) {
                    if ($item->message['chat']['type'] == 'private') {
                        echo '收到：' . $item->message['text'] . PHP_EOL;
                        $chat_id = $item->message['chat']['id'];
                        $chat_name = $item->message['chat']['first_name'];

                        $vars = [
                            'name'    => $chat_name,
                            'chat_id' => $chat_id,
                            'text'    => $item->message['text'],
                        ];

                    } else {
                        if (isset($item->message['document'])) {
                            $fileInfo = pathinfo($item->message['document']['file_name']);

                            if (isset($fileInfo['extension'])) {
                                if (in_array($fileInfo['extension'], explode(',', $this->app->getConfig('telegram.block_file_ext', 'exe,bat,pif')))) {
                                    Request::deleteMessage([
                                        'chat_id'    => $item->message['chat']['id'],
                                        'message_id' => $item->message['message_id'],
                                    ]);
                                }
                            }
                        }
                        if (!empty($item->message['text'])) {
                            echo '收到：' . $item->message['text'] . PHP_EOL;
                            $chat_id = $item->message['chat']['id'];
                            $this->blockMessage($item);
                        }

                        // 进群名字异常删除广告
                        if(!empty($item->message['new_chat_members'])){
                            $this->blockNewMemberJoinMessage($item->message);
                        }
                    }
                }
            } else {
                var_dump($messages);
            }
        }
    }

    /**
     * 黑名单消息处理
     *
     * @param $item
     * @return bool
     */
    protected function blockMessage($item)
    {
        $chat_id = $item->message['chat']['id'];
        $chat_name = $item->message['chat']['first_name'] ?? "";

        $storagePath = $this->app->getBasePath() . '/storage/app';

        if (is_file($storagePath . DIRECTORY_SEPARATOR . 'block_text.txt')) {
            if (filemtime($storagePath . DIRECTORY_SEPARATOR . 'block_text.txt') > $this->blockUpdateTime1) {
                $raw = file_get_contents($storagePath . DIRECTORY_SEPARATOR . 'block_text.txt');
                $this->blockKeyword['text'] = array_merge($this->blockKeyword['text'], explode(PHP_EOL, trim($raw)));
                $this->blockUpdateTime1 = time();
            }
        }

        if (is_file($storagePath . DIRECTORY_SEPARATOR . 'block_preg.txt')) {
            if (filemtime($storagePath . DIRECTORY_SEPARATOR . 'block_preg.txt') > $this->blockUpdateTime2) {
                $raw = file_get_contents($storagePath . DIRECTORY_SEPARATOR . 'block_preg.txt');
                $this->blockKeyword['preg'] = array_merge($this->blockKeyword['preg'], explode(PHP_EOL, trim($raw)));
                $this->blockUpdateTime2 = time();
            }
        }
        $member = Request::getChatMember([
            'chat_id' => $chat_id,
            'user_id' => $item->message['from']['id']
        ]);

        // 如果是管理员或者创建者则跳过
        if (in_array($member->result->status, ['administrator', 'creator'])) {
//            var_dump($member->result->user['first_name'].$member->result->user['last_name'].'='.$member->result->status);
            return false;
        }

        // 检测内容
        if ($this->blockKeyword($item->message['text'])) {
            Request::deleteMessage([
                'chat_id'    => $chat_id,
                'message_id' => $item->message['message_id'],
            ]);
        }

        // 检测名字
        $name = $item->message['from']['first_name'] . ($item->message['from']['last_name'] ?? "");
        if (mb_strlen($name) > $this->app->getConfig('telegram.block_name_length', 12) || $this->blockKeyword($name)) {
            Request::deleteMessage([
                'chat_id'    => $chat_id,
                'message_id' => $item->message['message_id'],
            ]);
        }

    }

    /**
     * 黑名单关键词
     *
     * @param $text
     * @return bool
     */
    protected function blockKeyword($text)
    {
        foreach ($this->blockKeyword['preg'] as $blockItem) {
            if (empty($blockItem)) {
                continue;
            }
            if (preg_match('/' . $blockItem . '/is', $text)) {
                return true;
            }
        }
        foreach ($this->blockKeyword['text'] as $keyword) {
            if (empty($keyword)) {
                continue;
            }
            if (mb_strpos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * 新成员名字广告
     */
    protected function blockNewMemberJoinMessage($message)
    {
        $chat_id = $message['chat']['id'];
        $chat_name = $message['chat']['first_name'] ?? "";
        $members = $message['new_chat_members'];
        foreach ($members as $newMember) {
            $memberName = $newMember['first_name'] . $newMember['last_name'] ?? '';
            if (mb_strlen($memberName) > $this->app->getConfig('telegram.block_name_length', 12) || $this->blockKeyword($memberName)) {
                Request::deleteMessage([
                    'chat_id'    => $chat_id,
                    'message_id' => $message['message_id'],
                ]);
            }
        }
    }

}