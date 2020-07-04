<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2020/5/10
 * Time: 13:04
 */

namespace App\Http\Controllers;

use App\Model\Group;
use App\Model\Message;
use App\Model\User;
use MongoDB\BSON\ObjectId;
use Mushroom\Application;
use Mushroom\Core\Http\Request;
use Mushroom\Core\Mongodb;
use Mushroom\Core\Redis;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class MessageController
{

    protected $app;
    protected $server;
    protected $redis;

    public function __construct(Application $application, Server $server, Redis $redis)
    {
        $this->app = $application;
        $this->server = $server;
        $this->redis = $redis;
    }

    public function onOpen(Request $request, Server $server)
    {
        $userTable = $this->app->getTable('users');
        $userTable->set($request->fd, ['fd' => $request->fd, 'auth' => 0]);
        $server->push($request->fd, ws_message('auth:', 401), true);
    }

    public function onClose(Request $request)
    {
        $userTable = $this->app->getTable('users');
        $user = $userTable->get($request->fd);
        $this->setUserOffline($user['id']);
        $userTable->del($request->fd);
    }

    public function onMessage(Request $request, Frame $frame, Server $server)
    {
        $userTable = $this->app->getTable('users');
        if (!($client = $userTable->get($frame->fd))) {
            $server->push($frame->fd, ws_message('未获取到认证信息,或连接已关闭!', 403));
            return $server->close($frame->fd);
        }
        if (is_string($frame->data) && $frame->data == 'ping') {
            $server->push($request->fd, 'pong', 10);
            return false;
        }
        $message = de_message($frame->data);
        if ($client['auth'] == 0) {
            if ($message->message == 'AUTH_VERIFY') {
                $client['auth'] = 1; // 设置认证通过
                $token = $message->data;
                $waitVerifyToken = net_decrypt_data($message->data, $this->app->getConfig('app.key'));
                $waitVerifyToken = explode(':',$waitVerifyToken);
                if(!$user = User::parsePasswordToken($token)){
                    $server->disconnect($frame->fd, 1000, ws_message('auth fail', 403));// 认证失败，断开连接
                }
                $user = User::getUserWithToken($token);
                if(!$user){
                    $server->disconnect($frame->fd, 1000, ws_message('get user profile fail', 403));// 认证失败，断开连接
                    return;
                }
                $client['id'] = $user['id']; // 保存fd对应的用户ID
                $userTable->set($frame->fd, $client);
                $this->setUserOnline($user['id'], $request->fd);
                var_dump("online: id: ".$user['id']." => fd: ". $request->fd);

                return ws_message('AUTH_SUCCESS', 200, [
                    'user'    => $user,
                    'user_key'=> $token,
                    'message' => "auth success,目前在线" . count($userTable) . '个人'
                ]);
            } else {
                $server->disconnect($frame->fd, 1000, ws_message('auth fail', 403));// 认证失败，断开连接
            }
        } else {
            // 认证后交互内容
            $messageData = (array)$message->data;
            if ($message->message == 'private') {
                if (is_array($messageData)) {
                    // 发送私人消息 ['to' => id, 'text' => '']
                    // 拿到对方用户fd
                    $targetFd = $this->getUserOnlineFd($messageData['to']);
                    $user = $userTable->get($request->fd);
                    $userProfile = User::getUserBasicProfile($user['id']);
                    // 构建消息结构
                    $sendMsgArr = ['from' => $userProfile, 'text' => $messageData['text'], ];
                    if(isset($messageData['img'])){
                        $sendMsgArr['img'] = $messageData['img'];
                    }
                    if(isset($messageData['sticker'])){
                        $sendMsgArr['sticker'] = $messageData['sticker'];
                    }
                    // 目标联系人不一样，才发消息
                    if($messageData['to'] != $user['id']){
                        $sendMsgArr['id'] = (new ObjectId())->__toString();
                        $server->push($targetFd, ws_message('private', 200, $sendMsgArr));
                    }
                    Message::storeUserMessage($sendMsgArr,$user['id'],$messageData['to']);
                    return ws_message('SEND_OK', 200);
                }
            } else if ($message->message == 'group') {
                if (is_array($messageData)) {
                    $group = Group::getGroup($messageData['to']);
                    if($group){
                        $members = Group::getGroupMembers($messageData['to']);
                        $user = $userTable->get($request->fd);
                        $userProfile = User::getUserBasicProfile($user['id']);

                        $groupUserId = [];
                        foreach($members as $_user){
                            $groupUserId[] = $_user['user_id'];
                        }
                        $groupUserId = array_diff($groupUserId, [$user['id']]);
                        $groupUserFd = $this->getOnlineUserFd($groupUserId);

                        $sendMsgArr = ['from' => $userProfile, 'text' => $messageData['text'],'group' => $group];
                        if(isset($messageData['img'])){
                            $sendMsgArr['img'] = $messageData['img'];
                        }
                        if(isset($messageData['sticker'])){
                            $sendMsgArr['sticker'] = $messageData['sticker'];
                        }

                        Message::storeGroupMessage($sendMsgArr,$group['id']);
                        foreach($groupUserFd as $_fd){
                            $sendMsgArr['id'] = (new ObjectId())->__toString();
                            $server->push($_fd,  ws_message('group', 200, $sendMsgArr));
                        }
                        return ws_message('SEND_OK', 200);
                    }
                    // 发送群组消息
                }
            }
        }
        return ws_message("无效操作", 400);
    }

    protected function randAvatar()
    {
        $list = ['alert.0.png', 'bewildered.0.png', 'blink.0.png', 'finger.0.png'];
        $rand = rand(0, count($list) - 1);
        return '/img/avatar/' . $list[$rand];
    }

    protected function randName()
    {
        $path = $this->app->getBasePath() . '/storage/app/npc.txt';
        if (is_file($path)) {
            $npc = file_get_contents($path);
            $npcList = json_decode($npc, true);
            $rand = rand(0, count($npcList) - 1);
            return $npcList[$rand] ?? "罗杰和苹果";
        }
        return "罗伯特";
    }


    protected function flushOnline()
    {
        $this->redis->del('online');
        $this->redis->del('online_date');
    }

    protected function setUserOnline($userId, $fd)
    {
        $this->redis->hset('online', $userId, $fd);
        $this->redis->hset('online_date', $userId, time());
    }

    protected function setUserOffline($userId)
    {
        $this->redis->hdel('online', $userId);
        $this->redis->hdel('online_date', $userId);
    }

    protected function getUserOnlineFd($userId)
    {
        return $this->redis->hget('online', $userId);
    }

    /**
     * 获取在线用户FD
     *
     * @param array $userId
     * @return mixed
     */
    protected function getOnlineUserFd($userId = [])
    {
        return $this->redis->hmget('online',$userId);
    }

}