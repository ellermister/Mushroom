<?php


namespace Mushroom\Core;


class Route
{
    protected $rules = ['websocket' => [], 'http' => []];
    const ROUTE_REJECT = 0x01;
    const ROUTE_ACCEPT = 0x02;

    public function __construct()
    {
        $this->rules['websocket'] = include(app()->getBasePath() . '/routes/websocket.php');
        $this->rules['http'] = include(app()->getBasePath() . '/routes/http.php');
    }

    public function handleWithWebsocket($uri, $action)
    {
        if(!in_array($action, ['onOpen','onMessage','onClose'])){
            return static::ROUTE_REJECT;
        }
        foreach($this->rules['websocket'] as $rule => $target){
            if ($rule == $uri){
                return $this->call($target, $action);
            }
        }
        return static::ROUTE_REJECT;
    }

    public function handleWithHttp($uri)
    {
        foreach($this->rules['http'] as $rule => $target){
            if ($rule == $uri){
                list($controller,$action) = explode('@',$target);
                return $this->call($controller, $action);
            }
        }
        return null;
    }

    public function call($controller,$method)
    {
        $class = 'App\\Http\\Controllers\\'.$controller;
        $object = app()->make($class);
        return app()->call([$object,$method]);
    }
}