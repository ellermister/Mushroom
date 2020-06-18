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

class Group extends Model
{

    protected $table = 'groups';

    /**
     * 获取用户的群组列表
     *
     * @param $userId
     * @return array|bool
     * @throws \Mushroom\Core\Database\DbException
     */
    public static function getUserGroups($userId)
    {
        $list = self::table('users_groups')->where('user_id', $userId)->get();
        $groupId = [];
        if ($list) {
            foreach ($list as $row) {
                $groupId[] = $row['group_id'];
            }
        }
        if (count($groupId) == 0) return [];
        return self::getGroupList($groupId);
    }

    /**
     * 获取群组列表
     *
     * @param $groupIdList
     * @return array|bool
     * @throws \Mushroom\Core\Database\DbException
     */
    public static function getGroupList($groupIdList)
    {
        $groupList = self::where('id', 'in', $groupIdList)->column(['id', 'group_id', 'group_name', 'brief', 'announcement', 'avatar', 'creator_id','member_count'])->get();
        foreach ($groupList as &$group) {
            if (empty($group['avatar'])) {
                $group['avatar'] = self::getDefaultAvatar();
            }
        }
        return $groupList;
    }

    public static function getDefaultAvatar()
    {
        return "https://pic.sucaibar.com/pic/201307/18/6cd5a2822d.png";
    }

}