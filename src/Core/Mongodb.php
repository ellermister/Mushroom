<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2020/6/27
 * Time: 17:23
 */

namespace Mushroom\Core;


use MongoDB\BSON\ObjectId;
use MongoDB\Driver\Manager;

class Mongodb
{

    protected $manager;

    /**
     * @return Manager
     * Mongodb constructor.
     */
    public function __construct()
    {
        $this->manager = new Manager("mongodb://192.168.75.1:27017");
        return $this->manager;
    }

    /**
     * 插入数据
     *
     * @param $document
     * @param $target
     * @return \MongoDB\Driver\WriteResult
     */
    public function insert($document,$target)
    {
        $bulk = new \MongoDB\Driver\BulkWrite;
        $_id= $bulk->insert($document);
        $writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);
        $result = $this->manager->executeBulkWrite($target, $bulk, $writeConcern);
        return $result;
    }

    /**
     * 获取数据
     *
     * @param $filter
     * @param $target
     * @param array $_options
     * @return \MongoDB\Driver\Cursor
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function find($filter,$target,$_options = [])
    {
        $options = [
//            'projection' => ['_id' => 0],
//            'sort' => ['x' => -1],
            'limit' => 100
        ];
        $options = array_merge($options, $_options);
        $query = new \MongoDB\Driver\Query($filter, $options);
        $cursor = $this->manager->executeQuery($target, $query);
        return $cursor->toArray();
    }

}