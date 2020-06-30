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
     * @return mixed
     */
    public static function getUserMessage($fromId,$targetId, $messageId = null)
    {
        if(!$messageId){
            $messageId = null;
        }
        $idList = [trim($fromId),trim($targetId)];
        sort($idList);
        $id = implode('_',$idList);
        $coll = sprintf("users.message_%s", $id);
        $mongodb = app()->get(Mongodb::class);
        $result = $mongodb->find(['_id' => ['$lt' => new ObjectId($messageId)]], $coll);
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
     * @return mixed
     */
    public static function getGroupMessage($fromId,$targetId, $messageId = null)
    {
        if(!$messageId){
            $messageId = null;
        }
        $coll = sprintf("groups.group_%s", $targetId);
        $mongodb = app()->get(Mongodb::class);
        $result = $mongodb->find(['_id' => ['$lt' => new ObjectId($messageId)]], $coll);
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