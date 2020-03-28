<?php

namespace App\Http\Controllers;

use Mushroom\Core\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class InboxController
{

    public function onOpen()
    {
        return '用户打开一个邮箱';
    }

    public function onClose()
    {
        return '用户关闭一个邮箱';
    }

    public function onMessage(Request $request, Frame $frame, Server $server)
    {
        $i=0;
        while($i<=10){
            $server->push($request->fd, date('Y-m-d H:i:s').'_'.$frame->data);
            sleep(3);
            $i++;
        }
        $time1 = time();
        $i = 0;
        while ($i < 1) {
            $code = $this->getIPAreaCode($frame->data);
            $i++;
        }
        $time2 = time();
        $time = $time2 - $time1;

        return '用户获取了一堆邮件,客户ID：' . $request->getFd() . ',用户请求内容:' . $frame->data . '，结果码:' . $code . ',耗时:' . $time.',count:'.$i.',内存大小 :'.app()->get('table')->memorySize;
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
        if(count($table) == 0){
            echo '初始化内存table'.PHP_EOL;
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
                    $table->set($bufferIpInt.'_'.($bufferIpInt + intval($buffer[4])), ['value' => trim($buffer[1])]);
                }
            }
            fclose($handle);
        }
        foreach($table as $name =>  $item){
            $buffer = explode("_", $name);
            if($buffer[0] <= $ipInt && $buffer[1] >= $ipInt){
                return $item['value'];
            }
        }
        return "未知";
    }

}