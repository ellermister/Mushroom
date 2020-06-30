<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2020/6/29
 * Time: 20:27
 */

namespace App\Model;


use Mushroom\Core\Mongodb;

class RecentContact
{


    /**
     * 获取最近联系人
     *
     * @param $userId
     * @return mixed
     * @throws \Mushroom\Core\Database\DbException
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
        var_dump($result);
        foreach($result->contacts as $row){
            if($row->contact_type == 'friend'){
                if($contact = self::arrayGetItem($friend,'id', $row->id)){
                    $recent [] = array_merge((array) $row, self::formatContact($contact));
                }
            }
            if($row->contact_type == 'group'){
                if($contact = self::arrayGetItem($group,'id', $row->id)){
                    $recent [] = array_merge((array) $row, self::formatContact($contact));
                }
            }
        }
        return $recent;
    }

    /**
     * 从数组中根据item的值获取item
     *
     * @param array $list
     * @param $itemKey
     * @param $itemValue
     * @return bool|mixed
     */
    protected static function arrayGetItem(array &$list, $itemKey, $itemValue)
    {
        foreach($list as $item){
            if(isset($item[$itemKey]) && $item[$itemKey]==$itemValue){
                return $item;
            }
        }
        return false;
    }

    protected static function formatContact(array $contact)
    {
        $contact['active'] = false;
        $contact['last_message'] = '';
        $contact['unread'] = 0;
        $contact['scroll_top'] = -1;
        $contact['read_id'] = "";
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
                $data[] = self::arrayOnly($contact,$need);
                if($contact['contact_type'] == 'friend'){
                    $friend[] = $contact['id'];
                }
                if($contact['contact_type'] == 'group'){
                    $group[] = $contact['id'];
                }
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



}