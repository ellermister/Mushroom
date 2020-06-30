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
        if(count($friendIdList)==0) return [];
        $userList = User::where('id', 'in', $friendIdList)->column(['id', 'username', 'discriminator', 'bio', 'avatar', 'locale'])->get();
        foreach ($userList as &$friend) {
            if (empty($friend['avatar'])) {
                $friend['avatar'] = self::getDefaultAvatar();
            }
        }
        return $userList;
    }

    /**
     * 添加用户为好友
     *
     * @param $currentUserId
     * @param $operatingUserId
     * @return int|mixed
     * @throws \Mushroom\Core\Database\DbException
     */
    public static function addUserToFriend($currentUserId, $operatingUserId)
    {
        if ($currentUserId == $operatingUserId || $currentUserId <= 0 || $operatingUserId <= 0) {
            return false;
        }
        if(!User::where('id', $operatingUserId)->count()){
            return false;
        }
        $count = self::table('users_friends')->where('user_id', $currentUserId)->where('friend_id', $operatingUserId)->count();
        if (!$count) {
            self::table('users_friends')->create([
                'user_id'    => $currentUserId,
                'friend_id'  => $operatingUserId,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
            // 因为表没有自增ID，这里创建返回为0，不能确定成功失败。可以通过影响记录判断，但目前也没必要。直接返回成功。
            return true;
        }
        return $count;
    }


    public static function getDefaultAvatar()
    {
        return "https://ss3.bdstatic.com/70cFv8Sh_Q1YnxGkpoWK1HF6hhy/it/u=2519824424,1132423651&fm=26&gp=0.jpg";
    }

}