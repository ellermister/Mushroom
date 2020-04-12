<?php

use Swoole\Coroutine\Http\Client;
use Swoole\Timer;

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

/**
 * decode message
 * @param $text
 * @return object
 * @author ELLER
 */
function de_message($text)
{
    $message = json_decode($text);
    if(!$message){
        $message = (object)[];
    }
    if (!isset($message->code)) $message->code = null;
    if (!isset($message->message)) $message->message = null;
    if (!isset($message->data)) $message->data = null;
    return $message;
}

/**
 * Websocket Message
 * @param $message
 * @param $code
 * @param null $data
 * @return false|string
 * @author ELLER
 */
function ws_message($message, $code = 200, $data = null)
{
    $format = [
        'message' => $message,
        'code'    => $code,
        'data'    => $data
    ];
    return json_encode($format);
}
go(function(){
    while (true){
        $cli = new Client('192.168.32.135', 9502);
        $cli->set(['websocket_compression' => true]);
        $cli->upgrade('/ip/node');
        $timer = Swoole\Timer::tick(5000, function() use($cli){
            $cli->push('ping',WEBSOCKET_OPCODE_PING);
        });
        while($recv = $cli->recv()){
            if(intval($recv->opcode) == 10){
                //pong
                continue;
            }
            $message = de_message($recv->data);
            if($message->data == 'auth:'){
                $cli->push(ws_message('bind node',200,"node"));
            }else if($message->data == 'name:'){
                $cli->push(ws_message('bind area',200,"广东"));
            }else{
                echo $message->data.PHP_EOL;
                Swoole\Timer::after(1, function() use ($message,$cli) {
                    if(preg_match('/^\d+\.\d+\.\d+\.\d+$/is', $message->data)){
                        if(icmp_ping($message->data)){
                            $cli->push(ws_message("{$message->data},正常",200,['ip' => $message->data,'state' => 1]));
                            echo "{$message->data},1".PHP_EOL;
                        }else{
                            $cli->push(ws_message("{$message->data},不通",200,['ip' => $message->data,'state' => 0]));
                            echo "{$message->data},0".PHP_EOL;
                        }
                    }else{
                    }
                });

            }

        }
        Swoole\Timer::clear($timer);
        echo '网络连接中断'.PHP_EOL;
        sleep(5);// 断线重连
        echo '尝试重新连接'.PHP_EOL;
    }
});