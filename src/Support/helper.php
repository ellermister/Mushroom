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