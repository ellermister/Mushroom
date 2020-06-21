<?php
/**
 * @return |null
 * @author ELLER
 */
function app()
{
    return \Mushroom\Application::getInstance();
}

if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param mixed $value
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

/**
 * 返回模板文件
 * @param $file
 * @param array $_data
 * @return \Mushroom\Core\Http\Response
 * @author ELLER
 */
function view($file, $_data = [])
{
    extract($_data);
    ob_start();
    include(app()->getBasePath() . '/resources/views/' . $file . '.php');
    $html = ob_get_clean();
    return response()->setContent($html)->setContentType('text/html');
}

/**
 * 获取响应结果对象
 * @param $content
 * @return \Mushroom\Core\Http\Response
 * @author ELLER
 */
function response($content = null)
{
    return app()->get(\Mushroom\Core\Http\Response::class)->setContent($content);
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
/**
 * Json Message
 * @param $message
 * @param $code
 * @param null $data
 * @return false|string
 * @author ELLER
 */
function js_message($message, $code = 200, $data = null)
{
    $format = [
        'message' => $message,
        'code'    => $code,
        'data'    => $data
    ];
    $responseObject = app()->get(\Mushroom\Core\Http\Response::class);
    $responseObject->setContent(json_encode($format));
    $responseObject->setContentType('application/json');
    return $responseObject;
}

/**
 * decode message
 *
 * @param $text
 * @return object
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
 * 增加环境变量配置获取
 *
 * @param $name
 * @param null $default
 * @return |null
 */
if(!function_exists('env')){
    function env($name, $default = null)
    {
        static $envRaw = null;

        $env = app()->getBasePath() . DIRECTORY_SEPARATOR . '.env';
        if (is_file($env)) {
            if ($envRaw == null) {
                $envRaw = file_get_contents($env);
            }
            if (preg_match('/' . $name . '\s*\=\s*"?(\S+)"?/is', $envRaw, $matches)) {
                return $matches[1] ?? $default;
            }
        }
        return $default;
    }
}


/**
 * 通信加密2
 */
function net_encrypt_data($plaintext, $key, $cipher = 'RC4'){
    $key = substr(strtoupper(md5($key).sha1($key)),0,64);
    $ciphertext = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_NO_PADDING);
    $ciphertext = safe_base64_encode($ciphertext);
    return $ciphertext;
}

/**
 * 通信解密2
 */
function net_decrypt_data($ciphertext, $key, $cipher = 'RC4'){
    $key = substr(strtoupper(md5($key).sha1($key)),0,64);
    $plaintext = openssl_decrypt(safe_base64_decode($ciphertext), $cipher, $key, OPENSSL_NO_PADDING);
    return $plaintext;
}

/**
 * safe base64 encode
 */
function safe_base64_encode($text){
    return str_replace(['+','/','='], ['-','_',''], base64_encode($text));
}

/**
 * safe base64 decode
 */
function safe_base64_decode($text){
    $data = str_replace(['-','_'], ['+','/'], $text);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    $data = base64_decode($data);
    return $data;
}

function make_random_string($strlen = 32){
    $char = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = "";
    for ($i = 0; $i < $strlen; $i++){
        $string .= $char[mt_rand(0, strlen($char) - 1)];
    }
    return $string;
}

/**
 * 密码哈希
 *
 * @param $text
 * @return bool|string
 */
function bcrypt($text)
{
    return password_hash($text, PASSWORD_BCRYPT);
}