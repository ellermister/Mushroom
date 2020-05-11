<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2020/5/10
 * Time: 13:04
 */

namespace App\Http\Controllers;

use Mushroom\Application;
use Mushroom\Core\Http\Request;
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
                $waitVerifyToken = net_decrypt_data($message->data, $this->app->getConfig('app.key'));
                $waitVerifyToken = explode(':',$waitVerifyToken);
                if ($this->hasUser($waitVerifyToken[0])) {
                    $userId = $waitVerifyToken[0];
                    $user = $this->getUser($userId);

                    if(!isset($user['password'])){
                        // 没有密码，则是旧版本过渡，无视验证
                        $user['password'] = make_random_string();
                    }else{
                        // 有密码则必须验证密码
                        if(trim($user['password']) != trim($waitVerifyToken[1])){
                            return ws_message('AUTH_FAIL',401,'密码验证失败');
                        }
                    }
                    $this->updateUser($user);
                    $token = net_encrypt_data($user['id'].':'.$user['password'],$this->app->getConfig('app.key'));
                } else {
                    $avatar = $this->randAvatar();
                    $userId = $this->getNewUserId();
                    $user = [
                        'id'         => $userId,
                        'first_name' => $this->randName(),
                        'avatar'     => $avatar,
                        'password'   => make_random_string(),
                    ];
                    $token = net_encrypt_data($user['id'].':'.$user['password'],$this->app->getConfig('app.key'));
                    $this->registerUser($user);
                }

                $client['id'] = $user['id']; // 保存fd对应的用户ID
                $userTable->set($frame->fd, $client);
                $this->setUserOnline($user['id'], $request->fd);


                return ws_message('AUTH_SUCCESS', 200, [
                    'friend'  => $this->friendList(),
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
                    $targetFd = $this->getUserOnlineFd($messageData['to']);
                    $user = $userTable->get($request->fd);
                    $userProfile = $this->getUser($user['id']);
                    $sendMsgArr = ['from' => $userProfile, 'text' => $messageData['text'], ];
                    if(isset($messageData['img'])){
                        $sendMsgArr['img'] = $messageData['img'];
                    }
                    $server->push($targetFd, ws_message('private', 200, $sendMsgArr));
                    return ws_message('SEND_OK', 200);
                }
            } else if ($message->message == 'group') {
                if (is_array($messageData)) {
                    // 发送群组消息
                }
            } else if ($message->message == 'FRIEND_LIST') {
                return ws_message('FRIEND_LIST', 200, $this->friendList());
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

    protected function friendList()
    {
        $onlineUserId = $this->redis->hkeys('online');
        $count = $this->redis->hlen('users');
        $users = $this->redis->hmget('users', $onlineUserId);
        $friend = [];
        foreach ($users as $id => $user) {
            $friend[$id] = unserialize($user);
            $friend[$id]['last_message'] = "";
        }
        return $friend;
    }

    protected function registerUser($user)
    {
        return $this->redis->hset('users', $user['id'], serialize($user));
    }

    protected function addUserToChannel()
    {

    }


    protected function flushOnline()
    {
        $this->redis->del('online');
    }

    protected function setUserOnline($userId, $fd)
    {
        $this->redis->hset('online', $userId, $fd);
    }

    protected function setUserOffline($userId)
    {
        $this->redis->hdel('online', $userId);
    }

    protected function getUserOnlineFd($userId)
    {
        return $this->redis->hget('online', $userId);
    }


    protected function getNewUserId()
    {
        $newId = $this->redis->get('users_new_id') ?? 1;
        while ($this->redis->hexists('users', $newId)) {
            $newId++;
        }
        $this->redis->set('users_new_id', $newId + 1);
        return $newId;
    }

    protected function hasUser($uid)
    {
        return $this->redis->hexists('users', $uid);
    }

    protected function getUser($uid)
    {
        return unserialize($this->redis->hget('users', $uid)) ?? [];
    }

    protected function updateUser($user)
    {
        return $this->redis->hset('users', $user['id'], serialize($user));
    }





}