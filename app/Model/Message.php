<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2020/6/27
 * Time: 20:28
 */

namespace App\Model;


use MongoDB\BSON\ObjectId;
use Mushroom\Core\Mongodb;

class Message
{
    /**
     * 存储用户消息
     *
     * @param $message
     * @param $groupId
     * @return mixed
     */
    public static function storeGroupMessage($message, $groupId)
    {
        $coll = sprintf("groups.group_%s", $groupId);
        $mongodb = app()->get(Mongodb::class);
        return $mongodb->insert($message, $coll);
    }

    /**
     * 存储用户消息
     *
     * @param $message
     * @param $fromId
     * @param $targetId
     * @return mixed
     */
    public static function storeUserMessage($message, $fromId, $targetId)
    {
        $idList = [trim($fromId),trim($targetId)];
        sort($idList);
        $id = implode('_',$idList);
        $coll = sprintf("users.message_%s", $id);
        $mongodb = app()->get(Mongodb::class);
        return $mongodb->insert($message, $coll);
    }

    /**
     * 获取用户消息
     *
     * @param $fromId
     * @param $targetId
     * @param null $messageId
     * @param int $action 0:id前记录 1:id后的记录 2:id前后的记录
     * @return mixed
     */
    public static function getUserMessage($fromId,$targetId, $messageId = null,$action = 0)
    {
        if(!$messageId){
            $messageId = null;//如果为null，则会创建一个最新的objectId,相当于获取最新的记录
        }
        $idList = [trim($fromId),trim($targetId)];
        sort($idList);
        $id = implode('_',$idList);
        $coll = sprintf("users.message_%s", $id);
        $mongodb = app()->get(Mongodb::class);
        if($action == 0){
            $result = $mongodb->find(['_id' => ['$lt' => new ObjectId($messageId)]], $coll);
        }else if($action == 1){
            $result = $mongodb->find(['_id' => ['$gt' => new ObjectId($messageId)]], $coll);
        }else if($action == 2){
            $pre = $mongodb->find(['_id' => ['$lt' => new ObjectId($messageId)]], $coll);
            $next = $mongodb->find(['_id' => ['$gte' => new ObjectId($messageId)]], $coll);
            $result = array_merge($pre, $next);
        }
        foreach($result as &$row){
            $row = self::formatMessage($row, $fromId,'private');
        }
        return $result;
    }

    /**
     * 获取群组消息
     *
     * @param $fromId
     * @param $targetId
     * @param null $messageId
     * @param int $action
     * @return mixed
     */
    public static function getGroupMessage($fromId,$targetId, $messageId = null, $action=0)
    {
        if(!$messageId){
            $messageId = null;
        }
        $coll = sprintf("groups.group_%s", $targetId);
        $mongodb = app()->get(Mongodb::class);

        if($action == 0){
            $result = $mongodb->find(['_id' => ['$lt' => new ObjectId($messageId)]], $coll);
        }else if($action == 1){
            $result = $mongodb->find(['_id' => ['$gt' => new ObjectId($messageId)]], $coll);
        }else if($action == 2){
            $pre = $mongodb->find(['_id' => ['$lt' => new ObjectId($messageId)]], $coll);
            $next = $mongodb->find(['_id' => ['$gte' => new ObjectId($messageId)]], $coll);
            $result = array_merge($pre, $next);
        }

        foreach($result as &$row){
            $row = self::formatMessage($row, $fromId,'group');
        }
        return $result;
    }

    /**
     * 格式化消息给客户端
     *
     * @param $message
     * @param $fromId
     * @param string $fromType  private/group
     * @return mixed
     */
    protected static function formatMessage($message, $fromId, $fromType = 'private')
    {
        if(isset($message->from) && isset($message->from->id)){
            $message->from_type = $fromType;
            if($message->from->id == $fromId){
                $message->type = 'send';
            }else{
                $message->type = 'receive';
            }
            $message->id = $message->_id->__toString();
        }
        return $message;
    }
}