<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2020/6/18
 * Time: 23:49
 */

namespace App\Model;


use Mushroom\Core\Database\Model;
use Mushroom\Core\Http\Response;

class Friend extends Model
{
    protected $table = 'users_friends';

    /**
     * 获取用户的好友列表
     *
     * @param $userId
     * @return array|bool
     * @throws \Mushroom\Core\Database\DbException
     */
    public static function getUserFriend($userId)
    {
        $list = self::table('users_friends')->where('user_id', $userId)->get();
        $friendId = [$userId];
        if ($list) {
            foreach ($list as $row) {
                $friendId[] = $row['friend_id'];
            }
        }
        if (count($friendId) == 0) return [];
        return self::getFriendList($friendId);
    }

    /**
     * 获取好友列表
     *
     * @param $friendIdList
     * @return array|bool
     * @throws \Mushroom\Core\Database\DbException
     */
    public static function getFriendList($friendIdList)
    {
        $userList = User::where('id', 'in', $friendIdList)->column(['id', 'email', 'username', 'discriminator', 'bio', 'avatar', 'locale'])->get();
        foreach ($userList as &$friend) {
            if (empty($friend['avatar'])) {
                $friend['avatar'] = self::getDefaultAvatar();
            }
        }
        return $userList;
    }

    public static function getDefaultAvatar()
    {
        return "https://ss3.bdstatic.com/70cFv8Sh_Q1YnxGkpoWK1HF6hhy/it/u=2519824424,1132423651&fm=26&gp=0.jpg";
    }

}