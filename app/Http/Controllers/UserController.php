<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2020/5/10
 * Time: 22:13
 */

namespace App\Http\Controllers;

use App\Model\Friend;
use App\Model\Group;
use App\Model\Message;
use App\Model\User;
use Mushroom\Application;
use Mushroom\Core\Http\Request;
use Mushroom\Core\Http\Response;
use Mushroom\Core\Redis;

class UserController
{
    protected $app;
    protected $redis;

    public function __construct(Application $application, Redis $redis)
    {
        $this->app = $application;
        $this->redis = $redis;
    }

    public function test()
    {
        return js_message('ok', 200, User::getAllUser());
    }

    /**
     * 创建访客
     *
     * @return false|string
     */
    public function registerGuestUser()
    {
        $guest = User::registerGuestUser();
        if ($guest) {
            return js_message('ok', 200, $guest);
        }
        return js_message('创建访客失败', 500);
    }

    /**
     * 验证访客内容
     *
     * @param Request $request
     * @return false|string
     */
    public function verifyGuest(Request $request)
    {
        $token = $request->input('token');
        if(User::parsePasswordToken($token)){
            return js_message('ok',200);
        }
        return js_message('token error',401);
    }

    /**
     * 登录验证
     *
     * @param Request $request
     * @return false|string
     * @throws \Mushroom\Core\Database\DbException
     */
    public function loginUser(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');
        echo bcrypt('123123');
        if($user = User::loginUser($username, $password)){
            return js_message('ok',200, $user);
        }
        return js_message('用户名或者密码错误',500);
    }

    /**
     * 获取个人资料(token)
     *
     * @param Request $request
     * @return mixed
     * @throws \Mushroom\Core\Database\DbException
     */
    public function getProfile(Request $request)
    {
        $token = $request->input('token');
        if($user = User::getUserWithToken($token)){
            return js_message('ok',200, $user);
        }
        return js_message('token error',401);
    }

    /**
     * 获取最近消息（联系人、群组）
     *
     * @param Request $request
     * @return false|string
     * @throws \Mushroom\Core\Database\DbException
     */
    public function getRecentMessage(Request $request)
    {
        $token = $request->input('token');
        if($user = User::getUserWithToken($token)){
            $recent = [];
            $groups = Group::getUserGroups($user['id']);
            $friends = Friend::getUserFriend($user['id']);
            foreach($groups as $item){
                $item['contact_type'] = 'group';
                $item['active'] = false;
                $item['last_message'] = '';
                $item['unread'] = 0;
                $recent[] = $item;
            }
            foreach($friends as $item){
                $item['contact_type'] = 'friend';
                $item['active'] = false;
                $item['last_message'] = '';
                $item['unread'] = 0;
                $recent[] = $item;
            }
            return js_message('ok',200, $recent);
        }
        return js_message('token error',401);
    }

    /**
     * 通过用户名搜索用户列表
     *
     * @param Request $request
     * @return false|string
     * @throws \Mushroom\Core\Database\DbException
     */
    public function searchUserList(Request $request)
    {
        $token = $request->input('token');
        if(!$user = User::getUserWithToken($token)){
            return js_message('token valid',401, []);
        }

        $username = $request->input('username');
        if(empty($username)){
            return js_message('username keyword valid!',200, []);
        }
        $list = User::searchUserForPublic($username, $user);
        return js_message('ok',200, $list);
    }

    /**
     * 通过名称搜索群组列表
     *
     * @param Request $request
     * @return false|string
     * @throws \Mushroom\Core\Database\DbException
     */
    public function searchGroupList(Request $request)
    {
        $token = $request->input('token');
        if(!$user = User::getUserWithToken($token)){
            return js_message('token valid',401, []);
        }
        $username = $request->input('username');
        if(empty($username)){
            return js_message('username keyword valid!',200, []);
        }
        $list = Group::searchGroupForPublic($username, $user);
        return js_message('ok',200, $list);
    }

    /**
     * 添加群组到联系人
     *
     * @param Request $request
     * @return false|string
     * @throws \Mushroom\Core\Database\DbException
     */
    public function addGroupToContact(Request $request)
    {
        $token = $request->input('token');
        $groupId = $request->input('group_id');
        if($user = User::getUserWithToken($token)){
            if(Group::addGroupToContact($user['id'], $groupId)){
                return js_message('ok',200);
            }
            return js_message('add group to contact fail!',500);
        }
        return js_message('token error',401);
    }

    /**
     * 获取贴图列表
     *
     * @return false|string
     */
    public function getStickerList(Request $request)
    {
        $stickersDir = $this->app->getBasePath().'/public/stickers';
        $subDir = scandir($stickersDir);
        $list = [];
        foreach($subDir as $dir){
            if($dir == '.' || $dir == '..'){
                continue;
            }
            $list[$dir] = [];
        }

        $httpPubic = $request->getSchemeAndHttpHost()."/stickers/";
        foreach($list as $name => $item){
            $dir = $this->app->getBasePath().'/public/stickers/'.$name;
            $fileList = scandir($dir);
            foreach($fileList as $file){
                if($file == '.' || $file == '..'){
                    continue;
                }
                $list[$name][] = $httpPubic.$name.DIRECTORY_SEPARATOR.$file;
            }
        }
        return js_message('stickers list',200, $list);
    }

    /**
     * 获取用户消息
     *
     * @param Request $request
     * @return false|string
     * @throws \Mushroom\Core\Database\DbException
     */
    public function getUserMessage(Request $request)
    {
        $token = $request->input('token');
        if(!$user = User::getUserWithToken($token)){
            js_message('user not found!',404);
        }
        $fromId = $user['id'];
        $targetId = $request->input('target_id');
        $messageId = $request->input('message_id');
        if(!$targetId){
            return js_message('target id valid', 404,['target_id' => $targetId,'message_id' => $messageId]);
        }

        $list = Message::getUserMessage($fromId,$targetId, $messageId);
        $lastId = null;
        $last = end($list);
        if($last)  $lastId = $last->_id->__toString();
        return js_message('ok',200, ['list' => array_reverse($list), 'last_id' => $lastId]);
    }
    /**
     * 获取群组消息
     *
     * @param Request $request
     * @return false|string
     * @throws \Mushroom\Core\Database\DbException
     */
    public function getGroupMessage(Request $request)
    {
        $token = $request->input('token');
        if(!$user = User::getUserWithToken($token)){
            js_message('user not found!',404);
        }
        $fromId = $user['id'];
        $targetId = $request->input('target_id');
        $messageId = $request->input('message_id');
        if(!$targetId){
            return js_message('target id valid', 404,['target_id' => $targetId,'message_id' => $messageId]);
        }

        $list = Message::getGroupMessage($fromId,$targetId, $messageId);
        $lastId = null;
        $last = end($list);
        if($last)  $lastId = $last->_id->__toString();
        return js_message('ok',200, ['list' => array_reverse($list), 'last_id' => $lastId]);
    }


    /**
     *   以下为旧版本内容
     **/


    public function getFriend(Request $request)
    {
        $friend = $this->friendList();
        return js_message('ok', 200, $friend);
    }

    protected function friendList()
    {
        $onlineUserId = $this->redis->hkeys('online');
        $count = $this->redis->hlen('users');
        $users = $this->redis->hmget('users', $onlineUserId);
        $friend = [];
        foreach ($users as $id => $user) {
            $friend[$id] = unserialize($user);
        }
        return $friend;
    }

    public function getGroupInfo(Request $request, Response $response)
    {
        $groupId = $request->get('group_id');
        $group = $this->getGroup($groupId);
        $response->setContent(js_message('ok', 200, $group));
        $response->setHeader('Access-Control-Allow-Origin', '*');
        $response->setHeader('Access-Control-Allow-Methods', 'POST,GET');
        $response->setHeader('Access-Control-Allow-Headers', 'x-requested-with,Content-Type,X-CSRF-Token');
        return $response;
    }

    public function joinGroupPage(Request $request, Response $response)
    {
        $groupId = $request->get('group_id');
        if (!$this->existsGroup($groupId)) {
            $response->setContent(js_message('群组不存在', 404));
            $response->setHeader('Access-Control-Allow-Origin', '*');
            $response->setHeader('Access-Control-Allow-Methods', 'POST,GET');
            $response->setHeader('Access-Control-Allow-Headers', 'x-requested-with,Content-Type,X-CSRF-Token');
            return $response;
        }
        $userKey = $request->get('user_key');
        if (empty($userKey)) {

            $avatar = $this->randAvatar();
            $userId = $this->getNewUserId();
            $user = [
                'id'         => $userId,
                'first_name' => $this->randName(),
                'avatar'     => $avatar,
                'password'   => make_random_string(),
            ];
            $token = net_encrypt_data($user['id'] . ':' . $user['password'], $this->app->getConfig('app.key'));
            $this->registerUser($user);
            $userKey = $token;
        }
        $waitVerifyToken = net_decrypt_data($userKey, $this->app->getConfig('app.key'));
        $waitVerifyToken = explode(':', $waitVerifyToken);
        if (empty($waitVerifyToken)) {

            $response->setContent(js_message('用户不存在', 404));
            $response->setHeader('Access-Control-Allow-Origin', '*');
            $response->setHeader('Access-Control-Allow-Methods', 'POST,GET');
            $response->setHeader('Access-Control-Allow-Headers', 'x-requested-with,Content-Type,X-CSRF-Token');
            return $response;
        }
        $userId = $waitVerifyToken[0];
        $userInfo = $this->getUser($userId);
        // 判断是否已经加入
        if ($this->redis->hget('groups_users:' . $groupId, $userId)) {
            $response->setContent(js_message('已经加入', 302));
            $response->setHeader('Access-Control-Allow-Origin', '*');
            $response->setHeader('Access-Control-Allow-Methods', 'POST,GET');
            $response->setHeader('Access-Control-Allow-Headers', 'x-requested-with,Content-Type,X-CSRF-Token');
            return $response;
        }
        if ($res = $this->redis->hset('groups_users:' . $groupId, $userId, serialize($userInfo))) {
            // 关联到创建者
            $this->redis->sadd('users_union_group:' . $userId, $groupId);
            $response->setContent(js_message('加入群组成功', 200, ['token' => $userKey]));
        } else {
            $response->setContent(js_message('加入群组失败', 500, $res));
        }
        $response->setHeader('Access-Control-Allow-Origin', '*');
        $response->setHeader('Access-Control-Allow-Methods', 'POST,GET');
        $response->setHeader('Access-Control-Allow-Headers', 'x-requested-with,Content-Type,X-CSRF-Token');
        return $response;

    }

    /**
     * 获取群组信息
     *
     * @param $groupId
     * @return mixed
     */
    protected function getGroup($groupId)
    {
        $group = unserialize($this->redis->hget('groups', $groupId));
        // SMEMBERS
        $users = $this->redis->hvals('groups_users:' . $groupId);
        foreach ($users as &$user) {
            $user = unserialize($user);
            $user = $this->filterSecretUserField($user);
        }
        $group['users'] = $users;

        // 补全群组信息
        if (empty($group['group_name'])) {
            $group['group_name'] = '未命名' . $group['group_id'];
        }

        return $group;
    }

    /**
     * 过滤用户隐私字段
     *
     * @param $user
     * @return mixed
     */
    protected function filterSecretUserField($user)
    {
        unset($user['password']);
        return $user;
    }

    /**
     * 判断群组是否存在
     *
     * @param $groupId
     * @return mixed
     */
    protected function existsGroup($groupId)
    {
        return $this->redis->hexists('groups', $groupId);
    }

    protected function getUser($uid)
    {
        return unserialize($this->redis->hget('users', $uid)) ?? [];
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

    protected function getNewUserId()
    {
        $newId = $this->redis->get('users_new_id') ?? 1;
        while ($this->redis->hexists('users', $newId)) {
            $newId++;
        }
        $this->redis->set('users_new_id', $newId + 1);
        return $newId;
    }

    protected function registerUser($user)
    {
        return $this->redis->hset('users', $user['id'], serialize($user));
    }


}