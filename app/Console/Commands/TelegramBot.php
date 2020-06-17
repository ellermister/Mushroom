<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2020/4/24
 * Time: 1:33
 */

namespace App\Console\Commands;

use App\Lib\Qrcode\QRCode;
use Longman\TelegramBot\Request;
use Mushroom\Application;
use Mushroom\Core\Console\Command;
use Swoole\Process;

class TelegramBot extends Command
{

    protected $signature = 'telegram:bot';

    protected $description = 'telegram bot';

    protected $blockKeyword = [];

    protected $blockUpdateTime1 = 0;
    protected $blockUpdateTime2 = 0;

    protected $daemon = false;

    protected $bot_api_key;
    protected $bot_username;

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
        $this->bot_api_key = $application->getConfig('telegram.bot_api_key');
        $this->bot_username = $application->getConfig('telegram.bot_username');
        $this->app = $application;
        $this->blockKeyword = $this->app->getConfig('telegram.block_keywords');

        // 消息处理
        $this->invoke(function(){
            $this->loopMessage();
        });
        // 图片处理
        $this->invoke(function(){
            $this->loopPhoto();
        });
        $this->wait();
    }

    protected function loopMessage()
    {
        $telegram = new \Longman\TelegramBot\Telegram($this->bot_api_key, $this->bot_username);
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
                        if (!empty($item->message['new_chat_members'])) {
                            $this->blockNewMemberJoinMessage($item->message);
                        }

                        if (isset($item->message['forward_from_chat'])) {
                            $this->blockForwardChat($item->message);
                        }

                        if (isset($item->message['caption']) || isset($item->message['photo'])) {
                            $this->blockPhotoCaption($item->message);
                        }
                    }
                }
            } else {
                var_dump($messages);
            }
        }
    }

    protected function loopPhoto()
    {
        while (1){
            $tg_photo = $this->app->getTable('tg_photo');
            var_dump($tg_photo);
            $tg_photo->set(strval(time().rand(10000,999999)),[
                'is_del' => 0,
                'chat_id' => 222,
                'message_id' => 333,
            ]);
            foreach($tg_photo as $key => $item){
                $tg_photo->del($key);
            }
            sleep(10);
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

    /**
     * 过滤转发规则
     *
     * @param $message
     */
    protected function blockForwardChat($message)
    {
        $chat_id = $message['chat']['id'];
        // 被转发者的名字或者频道名套用名称黑名单规则
        $title = $message['forward_from_chat']['title'];
        if (mb_strlen($title) > $this->app->getConfig('telegram.block_name_length', 12) || $this->blockKeyword($title)) {
            Request::deleteMessage([
                'chat_id'    => $chat_id,
                'message_id' => $message['message_id'],
            ]);
        }
    }

    /**
     * 过滤图文内容
     *
     * @param $message
     */
    protected function blockPhotoCaption($message)
    {
        $chat_id = $message['chat']['id'];
        if (isset($message['photo'])) {
            // 暂无，预加二维码识别
//                $timer = \Swoole\Timer::after(1001,function(){
//                    var_dump('sss');
////                    var_dump('即将执行二维码识别',$message['photo']);
////                    if($this->blockQRCode($message['photo'])){
////                        Request::deleteMessage([
////                            'chat_id'    => $chat_id,
////                            'message_id' => $message['message_id'],
////                        ]);
////                        var_dump('二维码识别执行完成');
////                    }
//                });
                var_dump('已经执行二维码识别事件',$timer);

        }

        if (isset($message['caption']) && $this->blockKeyword($message['caption'])) {
            Request::deleteMessage([
                'chat_id'    => $chat_id,
                'message_id' => $message['message_id'],
            ]);
        }
    }

    protected function blockQRCode($img)
    {
        $cacheDir = $this->app->getBasePath().'/storage/app/img';
        if(!is_dir($cacheDir)) mkdir($cacheDir);
        if(!is_dir($cacheDir)) return false;// 缓存目录不存在则跳过当前拦截
        $isBan = false;

        $lastImg = end($img);//取最后一张图，尺寸大
        $response = Request::getFile(['file_id' => $lastImg['file_id']]);
        if($result = $response->getResult()){
            if(isset($result->file_id)){
                $url = sprintf("https://api.telegram.org/file/bot%s/%s",$this->bot_api_key,$result->file_path);
                $cachePath = $cacheDir.DIRECTORY_SEPARATOR.md5($result->file_path);
                file_put_contents($cachePath, file_get_contents($url));
                try{
                    if(QRCode::text($cachePath) !== false){
                        @unlink($cachePath);
                        $isBan = true;
                    }
                }catch (\Throwable $throwable){
                    $this->error("识别二维码遇到错误：".$throwable->getMessage());
                }
                @unlink($cachePath);
            }
        }
        return $isBan;
    }



}