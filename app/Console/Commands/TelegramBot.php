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

    /**
     * @var Application
     */
    protected $app;

    public function __construct()
    {
        putenv('HTTP_PROXY=192.168.75.1:10809');
        putenv('HTTPS_PROXY=192.168.75.1:10809');
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
                        echo '收到：' . $item->message['text'] . PHP_EOL;
                        $chat_id = $item->message['chat']['id'];
                        $this->blockMessage($item);
                    }
                }
            } else {
                var_dump($messages);
            }
        }
    }

    protected function blockMessage($item)
    {
        $chat_id = $item->message['chat']['id'];
        $chat_name = $item->message['chat']['first_name'] ?? "";

        $storagePath = $this->app->getBasePath() . '/storage/app';

        echo $storagePath . DIRECTORY_SEPARATOR . 'block_text.txt'.PHP_EOL;

        if (is_file($storagePath . DIRECTORY_SEPARATOR . 'block_text.txt')) {
            if (filemtime($storagePath . DIRECTORY_SEPARATOR . 'block_text.txt') > $this->blockUpdateTime1) {
                $raw = file_get_contents($storagePath . DIRECTORY_SEPARATOR . 'block_text.txt');
                $this->blockKeyword['text'] = array_merge($this->blockKeyword['text'], explode(PHP_EOL, $raw));
                $this->blockUpdateTime1 = time();
            }
        }
        if (is_file($storagePath . DIRECTORY_SEPARATOR . 'block_preg.txt')) {
            if (filemtime($storagePath . DIRECTORY_SEPARATOR . 'block_preg.txt') > $this->blockUpdateTime2) {
                $raw = file_get_contents($storagePath . DIRECTORY_SEPARATOR . 'block_preg.txt');
                $this->blockKeyword['preg'] = array_merge($this->blockKeyword['preg'], explode(PHP_EOL, $raw));
                $this->blockUpdateTime2 = time();
            }
        }

        foreach ($this->blockKeyword['preg'] as $blockItem) {
            if (preg_match('/' . $blockItem . '/is', $item->message['text'])) {
                Request::deleteMessage([
                    'chat_id'    => $chat_id,
                    'message_id' => $item->message['message_id'],
                ]);
                continue;
            }
        }
        foreach ($this->blockKeyword['text'] as $keyword) {
            if (strpos($item->message['text'], $keyword) !== false) {
                Request::deleteMessage([
                    'chat_id'    => $chat_id,
                    'message_id' => $item->message['message_id'],
                ]);
                continue;
            }
        }
    }

}