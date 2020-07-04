<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2020/6/29
 * Time: 20:27
 */

namespace App\Model;


use MongoDB\BSON\ObjectId;
use Mushroom\Core\Mongodb;

class RecentContact
{


    /**
     * 获取最近联系人
     *
     * @param $userId
     * @return mixed
     * @throws \Mushroom\Core\Database\DbException
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public static function getRecentContact($userId)
    {
        $mongodb = app()->get(Mongodb::class);
        $coll = 'recent.contact';
        $result = $mongodb->find(['user_id' => $userId], $coll,['limit' => 1]);
        if(count($result) > 0) $result = end($result);
        if(empty($result)){
            return [];
        }
        if(isset($result->friend)){
            $friend = Friend::getFriendList($result->friend);
        }
        if(isset($result->group)){
            $group = Group::getGroupList($result->group);
        }
        $recent = [];
        foreach($result->contacts as $row){
            if($row->contact_type == 'friend'){
                if($contact = self::arrayGetItem($friend,['id' => $row->id])){
                    // 获取这个联系人最新滚动数据
                    $latestScroll = self::arrayGetItem($result->contacts,['id' => $row->id,'contact_type' =>$row->contact_type]) ?? [];
                    gettype($latestScroll) == 'object' && $latestScroll = get_object_vars($latestScroll);
                    $lastMessage = self::getFriendLastText($userId, $row->id);
                    if($lastMessage) $latestScroll['last_message'] = $lastMessage->text;
                    if($lastMessage) $latestScroll['last_id'] = $lastMessage->_id->__toString();
                    $latestScroll['unread'] = self::getFriendUnreadCount($userId,$row->id, $row->read_id);
                    $recent [] = array_merge((array) $row, self::formatContact($contact, $latestScroll ?? []));
                }
            }
            if($row->contact_type == 'group'){

                if($contact = self::arrayGetItem($group,['id' => $row->id])){
                    // 获取这个联系人最新滚动数据
                    $latestScroll = self::arrayGetItem($result->contacts,['id' => $row->id,'contact_type' =>$row->contact_type]) ?? [];
                    gettype($latestScroll) == 'object' && $latestScroll = get_object_vars($latestScroll);
                    $lastMessage = self::getGroupLastText($row->id);
                    if($lastMessage) $latestScroll['last_message'] = $lastMessage->text;
                    if($lastMessage) $latestScroll['last_id'] = $lastMessage->_id->__toString();
                    $latestScroll['unread'] = self::getGroupUnreadCount($row->id, $row->read_id);
                    $recent [] = array_merge((array) $row, self::formatContact($contact, $latestScroll ?? []));
                }
            }
        }

        return $recent;
    }

    /**
     * 从数组中根据item的值获取item
     *
     * @param array $list
     * @param array $where
     * @return bool|mixed
     */
    protected static function arrayGetItem(array &$list, array $where)
    {
        foreach($list as $item){
            $matchCount = 0;
            foreach($where as $itemKey => $itemValue){
                if(gettype($item)=='array' && isset($item[$itemKey]) && $item[$itemKey]==$itemValue){
                    $matchCount ++;
                }
                if(gettype($item)=='object' && isset($item->$itemKey) && $item->$itemKey==$itemValue){
                    $matchCount ++;
                }
            }
            if($matchCount == count($where)) return $item;
        }
        return false;
    }

    /**
     * 格式化联系人数据(一般用于最近联系人)
     *
     * @param array $contact
     * @param array $lastRecord
     * @return array
     */
    protected static function formatContact(array $contact, array $lastRecord)
    {
        $contact['active'] = false;
        $contact['last_message'] = '';
        $contact['unread'] = 0;
        $contact['scroll_top'] = -1;
        $contact['read_id'] = "";
        // 合并缓存中的最近联系人数据到联系人中给用户
        $contact = array_merge($contact, $lastRecord);
        return $contact;
    }


    /**
     * 写入最近联系人
     *
     * @param $userId
     * @param $contacts
     * @return mixed
     */
    public static function setRecentContact($userId, $contacts)
    {
        $mongodb = app()->get(Mongodb::class);
        $coll = 'recent.contact';
        list($contacts, $friend, $group) = self::cleanContact($contacts);
        $document = [
            'user_id' => $userId,
            'contacts' => $contacts,
            'friend' => $friend,
            'group' => $group,
        ];
        $result = $mongodb->update(['user_id' => $userId], $document, $coll,['upsert' => true]);
        return $result;
    }

    /**
     * 清理联系人字段
     * 用于处理接收到前端的数据
     *
     * @param array $contacts
     * @return array
     */
    protected static function cleanContact(array $contacts)
    {
        $data = [];
        $friend = $group = [];
        $need = ['id','contact_type','read_id'];
        foreach($contacts as $contact){
            if(isset($contact['id']) && isset($contact['contact_type']) && isset($contact['read_id'])){
                // 去重判断
                if($contact['contact_type'] == 'friend'){
                    if(in_array($contact['id'], $friend)) continue;
                    $friend[] = $contact['id'];
                }
                if($contact['contact_type'] == 'group'){
                    if(in_array($contact['id'], $group)) continue;
                    $group[] = $contact['id'];
                }
                $data[] = self::arrayOnly($contact,$need);
            }
        }
        return [$data, $friend, $group];
    }

    /**
     * 获取数组中指定多个key
     *
     * @param array $list
     * @param array $keys
     * @return array
     */
    protected static function arrayOnly(array &$list, array $keys)
    {
        $data = [];
        foreach($list as $key => $value){
            if(in_array($key, $keys)){
                $data[$key] = $value;
            }
        }
        return $data;
    }

    /**
     * 获取群组消息ID后未读数量
     *
     * @param $groupId
     * @param $messageId
     * @return int
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public static function getGroupUnreadCount($groupId, $messageId)
    {
        $mongodb = app()->get(Mongodb::class);
        $coll = sprintf('groups.group_%s', $groupId);
        /** @var Mongodb $mongodb */
        return $mongodb->count(['_id' => ['$gt' => new ObjectId($messageId)]], $coll);
    }

    /**
     * 获取好友消息ID后未读数量
     *
     * @param $userId
     * @param $friendId
     * @param $messageId
     * @return int
     */
    public static function getFriendUnreadCount($userId, $friendId, $messageId)
    {
        $idList = [trim($userId),trim($friendId)];
        sort($idList);
        $id = implode('_',$idList);
        $coll = sprintf("users.message_%s", $id);
        $mongodb = app()->get(Mongodb::class);
        return $mongodb->count(['_id' => ['$gt' => new ObjectId($messageId)]], $coll);
    }

    /**
     * 获取群组最后一条消息文本
     *
     * @param $groupId
     * @return array|false
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public static function getGroupLastText($groupId)
    {
        $mongodb = app()->get(Mongodb::class);
        $coll = sprintf('groups.group_%s', $groupId);
        /** @var Mongodb $mongodb */
        return current($mongodb->find([], $coll,
            [
                'limit' => 1,
                'sort'  => ['_id' => -1],
                'projection' => ['text' => 1]
            ]
        ));
    }

    /**
     * 获取好友最后一条消息文本
     *
     * @param $userId
     * @param $friendId
     * @return array|false
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public static function getFriendLastText($userId, $friendId)
    {
        $mongodb = app()->get(Mongodb::class);
        $idList = [trim($userId),trim($friendId)];
        sort($idList);
        $id = implode('_',$idList);
        $coll = sprintf("users.message_%s", $id);
        /** @var Mongodb $mongodb */
        return current($mongodb->find([], $coll,
            [
                'limit' => 1,
                'sort'  => ['_id' => -1],
                'projection' => ['text' => 1]
            ]
        ));
    }

}