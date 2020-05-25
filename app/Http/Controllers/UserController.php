<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2020/5/10
 * Time: 22:13
 */

namespace App\Http\Controllers;

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

    public function getFriend(Request $request)
    {
        $friend = $this->friendList();
        return js_message('ok', 200, $friend);
    }

    protected function friendList()
    {
        $onlineUserId = $this->redis->hkeys('online');
        $count = $this->redis->hlen('users');
        $users = $this->redis->hmget('users',$onlineUserId);
        $friend = [];
        foreach ($users as $id => $user) {
            $friend[$id] = unserialize($user);
        }
        return $friend;
    }

    public function getGroupInfo(Request $request,Response $response)
    {
        $groupId = $request->get('group_id');
        $group = $this->getGroup($groupId);
        $response->setContent(js_message('ok',200, $group));
        $response->setHeader('Access-Control-Allow-Origin','*');
        $response->setHeader('Access-Control-Allow-Methods','POST,GET');
        $response->setHeader('Access-Control-Allow-Headers','x-requested-with,Content-Type,X-CSRF-Token');
        return $response;
    }

    public function joinGroupPage(Request $request, Response $response)
    {
        $groupId = $request->get('group_id');
        if(!$this->existsGroup($groupId)){
            $response->setContent(js_message('群组不存在',404));
            $response->setHeader('Access-Control-Allow-Origin','*');
            $response->setHeader('Access-Control-Allow-Methods','POST,GET');
            $response->setHeader('Access-Control-Allow-Headers','x-requested-with,Content-Type,X-CSRF-Token');
            return $response;
        }
        $userKey = $request->get('user_key');
        $waitVerifyToken = net_decrypt_data($userKey, $this->app->getConfig('app.key'));
        $waitVerifyToken = explode(':',$waitVerifyToken);


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
        $users = $this->redis->hvals('groups_users:'.$groupId);
        foreach($users as &$user){
            $user = unserialize($user);
            $user = $this->filterSecretUserField($user);
        }
        $group['users'] = $users;

        // 补全群组信息
        if(empty($group['group_name'])){
            $group['group_name'] = '未命名'.$group['group_id'];
        }

        return $group;
    }
    /**
     * 过滤用户隐私字段
     *
     * @param $user
     * @return mixed
     */
    protected function filterSecretUserField($user){
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

}