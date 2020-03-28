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
        $server->push($request->fd,'auth:',true);
    }

    public function onMessage(Request $request, Frame $frame,Server $server)
    {
        $gfw_node = $this->app->getTable('gfw_node');
        if(!($client = $gfw_node->get($frame->fd))){
            $server->push($frame->fd,'未获取到认证信息,或连接已关闭!');
            return $server->close($frame->fd);
        }
        if($client['auth'] == 0){
            if($frame->data == 'node'){
                $client['auth'] = 1;
                $gfw_node->set($frame->fd, $client);
                $server->push($frame->fd, 'auth success');
                return 'name:';
            }else{
                $server->disconnect($frame->fd,1000,'auth fail');
            }
        }elseif(empty($client['name'])){
            $client['name'] = $frame->data;
            $gfw_node->set($frame->fd, $client);
            return "node name:".$frame->data;
        }else{
            list($ip,$state) = explode(',',$frame->data);
            $table = $this->app->getTable('gfw');
            foreach($table as $id => $item){
                if($item['ip'] == $ip){
                    $item['china_status'] = $state;
                    $table->set($id, $item);
                    echo "推送给{$item['fd']} {$ip},检测结果:{$state}";
                    if($server->exist($item['fd'])){
                        $nodeName = $client['name'];
                        $server->push($item['fd'], "[{$nodeName}]{$ip},检测结果:{$state}");
                    }
                    $table->del($id);
                }
            }
            return "结果响应成功,内存表总数:".$table->count();
        }
        return "无效操作";
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