<?php


namespace Mushroom\Core;


use Mushroom\Application;

class Process
{

    public function __construct( $port = null)
    {
        if(is_null($port)){
            $name = app()->get('port');
            var_dump($name);
//            var_dump($application);
//            $port =$application->get('listen.port');
        }
        echo "port".$port;
    }

    public function start()
    {
        echo '启动服务';
    }

}