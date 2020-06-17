<?php


namespace Mushroom\Core\Http;


class Response
{
    protected $charset = 'utf-8';
    protected $contentType = 'text/plain';
    protected $headers = [];
    protected $content;
    protected $code = 200;
    protected $swooleResponse;

    /**
     * 创建响应体
     * @param $content
     * @return Response
     * @author ELLER
     */
    public static function createFromSwwole(\Swoole\Http\Response $swooleResponse)
    {
        $response = new static();
        $response->init($swooleResponse);
        return $response;
    }

    /**
     * 初始化
     * @param $content
     * @author ELLER
     */
    protected function init($swooleResponse)
    {
        $this->swooleResponse = $swooleResponse;
    }

    /**
     * 设置内容
     * @param $content
     * @return $this
     * @author ELLER
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * 设置编码类型
     * @param string $charset
     * @return $this
     * @author ELLER
     */
    public function setCharset($charset = 'utf-8')
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * 设置结果类型
     * @param $type
     * @return $this
     * @author ELLER
     */
    public function setContentType($type)
    {
        $this->contentType = $type;
        return $this;
    }

    /**
     * 设置header头
     *
     * @param $name
     * @param $value
     * @return $this
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * 设置状态码
     *
     * @param $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = intval($code);
        return $this;
    }


    /**
     * 响应终止
     * @author ELLER
     */
    public function terminate()
    {
        $this->swooleResponse->header("Content-Type", $this->contentType);
        foreach ($this->headers as $headerName => $headerValue) {
            $this->swooleResponse->header($headerName, $headerValue);
        }
        $this->swooleResponse->status($this->code);
        $this->swooleResponse->end($this->content);
    }

}