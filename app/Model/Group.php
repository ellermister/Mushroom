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
        if(count($groupIdList)==0) return [];
        $groupList = self::where('id', 'in', $groupIdList)->column(['id', 'group_id', 'group_name', 'brief', 'announcement', 'avatar', 'creator_id', 'member_count'])->get();
        foreach ($groupList as &$group) {
            if (empty($group['avatar'])) {
                $group['avatar'] = self::getDefaultAvatar();
            }
        }
        return $groupList;
    }

    /**
     * 获取群组信息
     *
     * @param $id
     * @return mixed
     * @throws \Mushroom\Core\Database\DbException
     */
    public static function getGroup($id)
    {
        return self::where('id', $id)->find();
    }

    /**
     * 获取群组成员
     *
     * @param $groupId
     * @return array|bool
     * @throws \Mushroom\Core\Database\DbException
     */
    public static function getGroupMembers($groupId)
    {
        return self::table('users_groups')->where('group_id', $groupId)->get();
    }

    /**
     * 获取群组成员分页
     *
     * @param $groupId
     * @return array|bool
     * @throws \Mushroom\Core\Database\DbException
     */
    public static function getGroupMembersPage($groupId)
    {
        return self::table('users_groups')->where('group_id', $groupId)->paginate();
    }

    public static function getDefaultAvatar()
    {
        return "https://pic.sucaibar.com/pic/201307/18/6cd5a2822d.png";
    }

    /**
     * 搜索公共群组
     *
     * @param $username
     * @param $user
     * @return array|bool
     * @throws \Mushroom\Core\Database\DbException
     */
    public static function searchGroupForPublic($username, $user)
    {
        $list = self::where(function ($where) use ($username) {
            $where->where('group_id', $username);
            $where->whereOr('group_name', 'like', '%' . $username . '%');
        })->column(['id', 'group_id', 'group_name', 'avatar', 'brief', 'member_count'])->get();
        $groupId = [];
        if ($list) {
            $groupIdList = self::table('users_groups')->where('user_id', $user['id'])->column(['group_id'])->get();
            foreach ($groupIdList as $item) {
                $groupId[] = $item['group_id'];
            }
        }
        foreach ($list as &$group) {
            if (isset($group['avatar']) && empty($group['avatar'])) {
                $group['avatar'] = "https://ss3.bdstatic.com/70cFv8Sh_Q1YnxGkpoWK1HF6hhy/it/u=2519824424,1132423651&fm=26&gp=0.jpg";
            }
            $group['is_added'] = in_array($group['id'], $groupId) ? true : false;
        }
        return $list;
    }


    /**
     * 添加群组到联系人
     *
     * @param $currentUserId
     * @param $operatingGroupId
     * @return int|mixed
     * @throws \Mushroom\Core\Database\DbException
     */
    public static function addGroupToContact($currentUserId, $operatingGroupId)
    {
        if ($currentUserId == $operatingGroupId || $currentUserId <= 0 || $operatingGroupId <= 0) {
            return false;
        }
        if(!Group::where('id', $operatingGroupId)->count()){
            return false;
        }
        $count = self::table('users_groups')->where('user_id', $currentUserId)->where('group_id', $operatingGroupId)->count();
        if (!$count) {
            self::table('users_groups')->create([
                'user_id'    => $currentUserId,
                'group_id'  => $operatingGroupId,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
            $memberCount = self::table('users_groups')->where('group_id', $operatingGroupId)->count();
            self::where('id', $operatingGroupId)->update(['member_count' => intval($memberCount)]);
            // 因为表没有自增ID，这里创建返回为0，不能确定成功失败。可以通过影响记录判断，但目前也没必要。直接返回成功。
            return true;
        }
        return $count;
    }

}