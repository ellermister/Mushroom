<?php


namespace Mushroom\Core;

use DI\Container as DIContainer;
use DI\ContainerBuilder;
use DI\NotFoundException;
use Mushroom\Application;

class Container
{
    protected $container = null;
    protected $instances = [];

    public function __construct($path)
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions([Config::class => \DI\create(Config::class)->constructor(\DI\get(Application::class))]);
        $builder->addDefinitions($path . '/config/app.php');
        $builder->addDefinitions($path . '/config/server.php');
        $this->container = $builder->build();
    }

    public function get($name)
    {
        try {
            return $this->container->get($name);
        } catch (NotFoundException $exception) {
            return null;
        }
    }

    /**
     * @param string $name
     * @param $value
     * @author ELLER
     */
    public function set(string $name, $value)
    {
        $this->container->set($name, $value);
    }

    /**
     * @param $name
     * @param array $param
     * @return mixed
     * @throws NotFoundException
     * @throws \DI\DependencyException
     * @author ELLER
     */
    public function make($name, $param = [])
    {
        return $this->container->make($name, $param);
    }

    /**
     * @param $callable
     * @param array $parameters
     * @return mixed
     * @author ELLER
     */
    public function call($callable, $parameters = [])
    {
        return $this->container->call($callable, $parameters);
    }
}