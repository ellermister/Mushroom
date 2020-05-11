<?php
/**
 * Created by PhpStorm.
 * User: ellermister
 * Date: 2020/5/10
 * Time: 14:17
 */

namespace Mushroom\Core;


use Mushroom\Application;

class Redis
{

    protected $redis;
    protected $application;

    public function __construct()
    {
        $this->application = app();
        $redis = new \Swoole\Coroutine\Redis();;
        $redis->connect($this->application->getConfig('redis.server'), $this->application->getConfig('redis.port'));
        $this->redis = $redis;
    }

//    public function get($name, $default = null)
//    {
//        return $this->redis->get($name) ?? $default;
//    }
//
//    public function lpush($name, $data)
//    {
//        return $this->redis->lPush($name, serialize($data));
//    }

    /**
     * @param $method
     * @param $arg
     * @return mixed
     */
    public function __call($method,$arg)
    {
        return call_user_func_array([&$this->redis, $method],$arg);
    }

}