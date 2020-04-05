<?php


namespace App\Http\Controllers;


use Mushroom\Application;
use Mushroom\Core\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class IpController
{

    protected $app;
    protected $server;

    public function __construct(Application $application,Server $server)
    {
        $this->app = $application;
        $this->server = $server;
    }

    public function getAreaCode(Request $request)
    {
        $ip = $request->get('ip');
        return $this->getIPAreaCode($ip);
    }

    public function onOpen(Request $request,Server $server)
    {
        $gfw_client = $this->app->getTable('gfw_client');
        $gfw_client->set($request->fd, ['fd' => $request->fd, 'auth' => 0]);
        $server->push($request->fd, ws_message('auth:', 401), true);
    }

    public function onMessage(Request $request, Frame $frame,Server $server)
    {
        $gfw_client = $this->app->getTable('gfw_client');
        if(!($client = $gfw_client->get($frame->fd))){
            $server->push($frame->fd,ws_message('未获取到认证信息,或连接已关闭!',500));
            return $server->close($frame->fd);
        }

        // 没有认证
        $message = de_message($frame->data);
        if($client['auth'] == 0){
            if($message->data == 'client'){
                $client['auth'] = 1; // 设置认证通过
                $gfw_client->set($frame->fd, $client);
                $gfw_node = $this->app->getTable('gfw_node');
                return ws_message( "auth success,目前在线".count($gfw_node).'个节点',200);
            }else{
                $server->disconnect($frame->fd,1000,ws_message('auth fail',403));// 认证失败，断开连接
            }
        }else{
            // 认证后交互内容
            $ip = trim($message->data);
            $table = $this->app->getTable('gfw');
            $id = date('YmdHi_').$frame->fd;
            if($table->exist($id)){
                return ws_message("当前连接已有任务，请稍后再添加。",503);
            }
            $table->set($id, [
                'ip'              => $ip,
                'fd'              => $frame->fd,
                'china_status'    => 0,
                'overseas_status' => 0,
            ]);
            $gfw_node = $this->app->getTable('gfw_node');
            foreach($gfw_node as $node){
                echo '推送给:'.$node['fd'].PHP_EOL;
                $this->pushToNode($node['fd'], $ip);
            }
            return ws_message("添加并推送成功:".$ip,200);
        }
        return ws_message("无效操作",400);
    }

    public function onClose(Request $request)
    {
        $gfw_client = $this->app->getTable('gfw_client');
        $gfw_client->del($request->fd);
    }

    protected function pushToNode($fd,$ip)
    {
        $this->server->push($fd, ws_message('ip',200,$ip));
    }

    public function getGuestIP(Request $request)
    {
        $cfIP = $request->headers('cf-connecting-ip');
        if(!empty($cfIP)){
            return $cfIP;
        }
        return $request->server->get('REMOTE_ADDR');
    }

    /**
     * 获取IP所在地区代码
     * @param $ip
     * @return string
     */
    function getIPAreaCode($ip)
    {
        $table = app()->getTable('apnic');
        $ipInt = ip2long($ip);
        if (count($table) == 0) {
            echo '初始化内存table' . PHP_EOL;
            $apnic = app()->getBasePath() . '/storage/app/delegated-apnic-latest';
            $handle = fopen($apnic, "r");
            while (!feof($handle)) {
                $line = fgets($handle);

                if (substr($line, 0, 1) == "#") {
                    unset($line);
                    continue;
                }

                $buffer = explode("|", $line);

                if (isset($buffer[2]) && $buffer[2] == 'ipv4' && isset($buffer[4])) {
                    $bufferIpInt = ip2long($buffer[3]);
                    $table->set($bufferIpInt . '_' . ($bufferIpInt + intval($buffer[4])), ['value' => trim($buffer[1])]);
                }
            }
            fclose($handle);
        }
        foreach ($table as $name => $item) {
            $buffer = explode("_", $name);
            if ($buffer[0] <= $ipInt && $buffer[1] > $ipInt) {
                return $item['value'];
            }
        }
        return "unknown";
    }
}