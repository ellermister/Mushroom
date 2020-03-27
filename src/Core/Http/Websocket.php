<?php


namespace Mushroom\Core\Http;


use Mushroom\Core\Route;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class Websocket
{
    protected $host;
    protected $port;
    protected $route;

    protected $server;
    protected $app;

    public function __construct(Route $route)
    {
        $this->host = app()->get('listen.host');
        $this->port = app()->get('listen.port');
        $this->route = $route;
        $this->app = app();

    }

    /**
     * 触发启动
     * @author ELLER
     */
    public function start()
    {
        $this->createTable();
        $this->createService();
    }

    protected function createTable()
    {
        $memoryTable = $this->app->get('memory.table');
        $tableList = [];
        foreach ($memoryTable as $tableName => $item) {
            $table = new \Swoole\Table($item['size']);
            foreach ($item['column'] as $name => $property) {
                $table->column(trim($name), $property[0], $property[1]);
            }
            $table->create();
            $tableList[$tableName] = $table;
        }
        app()->set('memory.table.list', $tableList);
    }


    /**
     * 创建服务
     * @author ELLER
     */
    protected function createService()
    {
        $server = new \Swoole\Websocket\Server($this->host, $this->port);
        $server->on('open', function ($server, $req) {
            $connect = $server->connection_info($req->fd);
            $sessionId = $connect['reactor_id'] . '_' . $req->fd;
            $request = [
                'fd'     => $req->fd,
                'header' => $req->header,
                'server' => $req->server,
            ];
            $request = Request::createFromSwoole($req);
            app()->set(Request::class, $request);
            $this->storeSeesion($sessionId, $request);
            $result = $this->route->handleWithWebsocket($request->getPathInfo(), 'onOpen');
            if ($result == Route::ROUTE_REJECT) {
                $server->disconnect($req->fd, 1000, 'connect reject');
            } else {
                if ($result) $server->push($req->fd, $result);
            }
        });

        $server->on('message', function ($server, $frame) {
            $connect = $server->connection_info($frame->fd);
            $sessionId = $connect['reactor_id'] . '_' . $frame->fd;
            $req = $this->fetchSession($sessionId);
            app()->set(Request::class, $req);
            app()->set(Frame::class, $frame);
            $result = $this->route->handleWithWebsocket($req->getPathInfo(), 'onMessage');
            $result && $server->push($frame->fd, $result);
        });

        $server->on('close', function ($server, $fd) {
            $connect = $server->connection_info($fd);

            // http请求不存储会话状态，只有websocket存储且需要销毁
            if ($connect['websocket_status'] > 0) {
                // websocket
                $sessionId = $connect['reactor_id'] . '_' . $fd;
                $req = $this->fetchSession($sessionId);
                $this->destroySession($sessionId);
                app()->set(Request::class, $req);
                $this->route->handleWithWebsocket($req->getPathInfo(), 'onClose');
            } else {
                // http 无需处理
            }

        });

        $server->on("request", function ($request, $response) {
            // 创建请求头
            $requestObject = Request::createFromSwoole($request);
            app()->set(Request::class, $requestObject);

            // 创建响应体
            $responseObject = Response::createFromSwwole($response);
            app()->set(Response::class, $responseObject);

            // 存储session连接信息
//            $connect = $this->server->connection_info($request->fd);
//            $sessionId = $connect['reactor_id'] . '_' . $request->fd;
//            $this->storeSeesion($sessionId, $requestObject);

            $content = $this->route->handleWithHttp($requestObject->getPathInfo());
            if($content instanceof Response){
                $content->terminate();
            }else{
                $responseObject->setContent($content);
                $responseObject->terminate();
            }
        });

        echo '===================================' . PHP_EOL;
        echo 'Websocket服务启动完毕！' . PHP_EOL;
        echo '监听地址：' . $this->host . PHP_EOL;
        echo '监听端口：' . $this->port . PHP_EOL;
        $this->server = $server;
        app()->set(Server::class, $this->server);
        $this->server->start();
    }

    /**
     * 存储session
     * @param $fd
     * @param $data
     * @author ELLER
     */
    public function storeSeesion($fd, $data)
    {
        $path = app()->getBasePath() . '/storage/session/' . $fd;
        file_put_contents($path, serialize($data));
    }

    /**
     * @param $fd
     * @return Request
     * @author ELLER
     */
    public function fetchSession($fd)
    {
        $path = app()->getBasePath() . '/storage/session/' . $fd;
        return unserialize(file_get_contents($path));
    }

    /**
     * 销毁session
     * @param $fd
     * @author ELLER
     */
    public function destroySession($fd)
    {
        $path = app()->getBasePath() . '/storage/session/' . $fd;
        unlink($path);
    }

}