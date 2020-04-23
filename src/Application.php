<?php


namespace Mushroom;


use Mushroom\Core\Config;
use Mushroom\Core\Container;
use Mushroom\Core\Process;
use Swoole\Table;

class Application extends Container
{
    protected $basePath;
    protected static $instance = null;

    public function __construct($path = null)
    {
        parent::__construct($path);
        $this->basePath = $path;
        $this->set('app', $this);
        $this->set(self::class, $this);
        include __DIR__ . '/Support/helper.php';

        $rand = rand(1000, 9999);
        echo 'randid:' . $rand . PHP_EOL;;
        $this->set('app_id', $rand);
        self::setInstance($this);;
    }

    /**
     * 获取实例
     * @return self
     * @author ELLER
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            ECHO 'ERROR: app实例为空;' . PHP_EOL;
            static::$instance = new static;
        }
        return static::$instance;
    }

    /**
     * 设置实例
     * @param Container|null $container
     * @return Container
     * @author ELLER
     */
    public static function setInstance(Container $container = null)
    {
        return static::$instance = $container;
    }

    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * 获取内存表
     * @param $name
     * @return Table|null
     * @author ELLER
     */
    public function getTable($name)
    {
        $list = $this->get('memory.table.list');
        return $list[$name] ?? null;
    }

    /**
     * 获取配置操作类
     *
     * @return Config|null
     */
    public function getConfig($name = null, $default = null)
    {
        if ($name != null) {
            $config = $this->get(Config::class);
            if (!$config) {
                return $default;
            }
            return $config->get($name, $default);
        }
        return $this->get(Config::class);
    }
}