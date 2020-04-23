<?php


namespace App\Http\Controllers;


use Mushroom\Application;
use Mushroom\Core\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class IPNodeController
{
    protected $app;

    public function __construct(Application $application)
    {
        $this->app = $application;
    }

    public function getAreaCode(Request $request)
    {
        $ip = $request->get('ip');
        return $this->getIPAreaCode($ip);
    }

    public function onOpen(Request $request,Server $server)
    {
        $gfw_node = $this->app->getTable('gfw_node');
        $gfw_node->set($request->fd, ['fd' => $request->fd, 'auth' => 0]);
        $server->push($request->fd,ws_message('auth:',401,'auth:'),true);
    }

    public function onMessage(Request $request, Frame $frame,Server $server)
    {

         = $this->app->getTable('gfw_node');
        if(!($client = $gfw_node->get($frame->fd))){
            $server->push($frame->fd,ws_message('未获取到认证信息,或连接已关闭!',403));
            return $server->close($frame->fd);
        }
        $message = de_message($frame->data);
        var_dump($message);
        if($client['auth'] == 0){
            if($message->data == 'node'){
                $client['auth'] = 1;
                $gfw_node->set($frame->fd, $client);
                $server->push($frame->fd, ws_message('auth success',200));
                return ws_message('name:',401,'name:');
            }else{
                $server->disconnect($frame->fd,1000,ws_message('auth fail',403));
            }
        }elseif(empty($client['name'])){
            // 确认节点名称绑定成功，回显。
            $client['name'] = $message->data;
            $gfw_node->set($frame->fd, $client);
            return ws_message("node name:".$message->data,200);
        }else{
            $ip = $message->data->ip;
            $state = $message->data->state;
            $table = $this->app->getTable('gfw');
            foreach($table as $id => $item){
                if($item['ip'] == $ip){
                    $item['china_status'] = $state;
                    $table->set($id, $item);
                    echo "推送给{$item['fd']} {$ip},检测结果:{$state}";
                    if($server->exist($item['fd'])){
                        $nodeName = $client['name'];
                        $server->push($item['fd'], ws_message('CHECK_RESULT',200,['ip' => $ip,'state' => $state]));
                    }
                    $table->del($id);
                }
            }
            return ws_message("结果响应成功,内存表总数:".$table->count());
        }
        return ws_message("无效操作",400);
    }

    public function onClose(Request $request)
    {
        $gfw_node = $this->app->getTable('gfw_node');
        $gfw_node->del($request->fd);
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
            if ($buffer[0] <= $ipInt && $buffer[1] >= $ipInt) {
                return $item['value'];
            }
        }
        return "未知";
    }
}