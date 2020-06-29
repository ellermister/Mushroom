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
     */
    public static function getRecentContact($userId)
    {
        $mongodb = app()->get(Mongodb::class);
        $coll = sprintf('recent_contact.user_%s',$userId);
        $result = $mongodb->find(['sort' => ['updated_at' => -1]], $coll);
        foreach($result as &$row){
//            $row = self::formatMessage($row, $fromId,'group');
        }
        return $result;
    }
}