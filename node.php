<?php

use Swoole\Coroutine\Http\Client;

/**
 * 獲取當前系統OS
 * @return string
 */
function current_os()
{
    if (strcasecmp(PHP_OS, 'WINNT') === 0) {
        //Windows NT
        return 'windows';
    } elseif (strcasecmp(PHP_OS, 'Linux') === 0) {
        //Linux
        return 'linux';
    }
    return PHP_OS;
}

/**
 * ICMP檢測端口通信狀態
 * @param $ip
 * @return bool
 */
function icmp_ping($ip)
{
    if (current_os() == 'windows') {
        $lastout = exec('ping -n 1 ' . $ip, $output);
        if (preg_match('/Average\s+=[^\d]+\d+ms/is', $lastout)) {
            return true;
        }
    } elseif (current_os() == 'linux') {
        $lastout = exec("ping -c 1 {$ip}", $outcome, $status);
        if (preg_match('/min\/avg\/max\/mdev\s+=\s+[\d\.]+\/([\d\.]+)\/[\d\.]+\/[\d\.]+ ms/is', $lastout, $result)) {
            return true;
        }
    }
    return false;
}

go(function(){
    $cli = new Client('192.168.32.135', 9502);
    $cli->set(['websocket_compression' => true]);
    $cli->upgrade('/ip/node');
    while($recv = $cli->recv()){
        if($recv->data == 'auth:'){
            $cli->push("node");
        }else{
            echo $recv->data.PHP_EOL;
            Swoole\Timer::after(1, function() use ($recv,$cli) {
                if(preg_match('/^\d+\.\d+\.\d+\.\d+$/is', $recv->data)){
                    if(icmp_ping($recv->data)){
                        $cli->push("{$recv->data},1");
                        echo "{$recv->data},1".PHP_EOL;
                    }else{
                        $cli->push("{$recv->data},0");
                        echo "{$recv->data},0".PHP_EOL;
                    }
                }else{
                }
            });

        }

    }
});