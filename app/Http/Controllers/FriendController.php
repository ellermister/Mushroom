<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2020/6/18
 * Time: 23:37
 */

namespace App\Http\Controllers;

use App\Model\Friend;
use App\Model\User;
use Mushroom\Core\Http\Request;

/**
 * 用来处理好友的接口
 *
 * Class FriendController
 * @package App\Http\Controllers
 */
class FriendController
{
    /**
     * 好友列表
     *
     * @param Request $request
     * @return false|string
     * @throws \Mushroom\Core\Database\DbException
     */
    public function friendList(Request $request)
    {;
        $token = $request->input('token');
        if($user = User::getUserWithToken($token)){
            $friends = Friend::getUserFriend($user['id']);
            return js_message('ok',200, $friends);
        }
        return js_message('token error',401);
    }

    /**
     * 添加用户为好友
     *
     * @param Request $request
     * @return false|string
     * @throws \Mushroom\Core\Database\DbException
     */
    public function addUserToFriend(Request $request)
    {
        $token = $request->input('token');
        $friendId = $request->input('friend_id');
        if($user = User::getUserWithToken($token)){
            if(Friend::addUserToFriend($user['id'], $friendId)){
                return js_message('ok',200);
            }
            return js_message('add user to friend fail!',500);
        }
        return js_message('token error',401);
    }

}