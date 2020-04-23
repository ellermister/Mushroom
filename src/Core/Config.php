<?php


namespace Mushroom\Core;


use Mushroom\Application;

class Config
{
    protected $app;
    protected $path;
    protected $cache = [];

    public function __construct(Application $application)
    {
        $this->app = $application;
        $this->path = $this->app->getBasePath() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
    }

    /**
     * 获取配置
     * 支持通过.进行获取子级配置（eg: database.host）
     *
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function get($name, $default = null)
    {
        if (strpos($name, '.') === false) {
            if (is_file($this->path . $name . '.php')) {
                $this->cache[$name] = $this->loadFile($name);
                return $this->cache[$name];
            }
            return $default;
        } else {
            // 多级配置读取
            $names = explode('.', $name);
            $name = array_shift($names);
            if (is_file($this->path . $name . '.php')) {
                $this->cache[$name] = $this->loadFile($name);
                return $this->treeFetch($this->cache[$name], $names, $default);
            }
            return $default;
        }
    }

    /**
     * 递归获取配置
     *
     * @param $arr
     * @param array $names
     * @param null $default
     * @return null
     */
    protected function treeFetch(&$arr, array $names, $default = null)
    {
        $current = array_shift($names);
        if ($current == null) {
            return $arr;
        }
        if (count($names) > 0) {
            if (isset($arr[$current]) && is_array($arr[$current])) {
                return $this->treeFetch($arr[$current], $names);
            }
            return $default;
        } else {
            if (isset($arr[$current])) {
                return $arr[$current];
            }
            return $default;
        }
    }

    /**
     * 从文件获取配置
     *
     * @param $file
     * @return mixed|null
     */
    protected function loadFile($file)
    {
        if (is_file($this->path . $file . '.php')) {
            return include($this->path . $file . '.php');
        }
        return null;
    }

}