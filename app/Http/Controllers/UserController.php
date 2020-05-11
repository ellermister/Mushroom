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
}