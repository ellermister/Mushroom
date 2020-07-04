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
    protected $application;

    /**
     * @return Manager
     * Mongodb constructor.
     */
    public function __construct()
    {
        $this->application = app();

        $this->manager = new Manager($this->application->getConfig('mongodb.uri'));
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
     * 更新数据
     *
     * @param array $where
     * @param $document
     * @param $target
     * @param $options
     * @return \MongoDB\Driver\WriteResult
     */
    public function update(array $where,$document,$target,$options = [])
    {
        $bulk = new \MongoDB\Driver\BulkWrite;
        $bulk->update(
            $where,
            ['$set' => $document],
            array_merge(['multi' => false, 'upsert' => false], $options)
        );
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
     * @return array
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

    /**
     * 统计条件总数
     *
     * @param $filter
     * @param $target
     * @return mixed
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function count($filter, $target)
    {
        list($db, $coll) = explode('.', $target);
        $command = new \MongoDB\Driver\Command(["count" => $coll, "query" => $filter]);
        $result = $this->manager->executeCommand($db, $command);
        $res = current($result->toArray());
        $count = $res->n;
        return $count;
    }


}