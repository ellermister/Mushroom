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