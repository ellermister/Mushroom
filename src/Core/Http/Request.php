<?php


namespace Mushroom\Core\Http;
use Swoole\Http\Request as SwooleRequest;
use Mushroom\Support\Arr;

class Request
{

    /**
     * swoole客户端ID
     *
     * @var int
     */
    public $fd;

    /**
     * Custom parameters.
     *
     * @var ParameterBag
     */
    public $attributes;

    /**
     * Request body parameters ($_POST).
     *
     * @var ParameterBag
     */
    public $request;

    /**
     * Query string parameters ($_GET).
     *
     * @var ParameterBag
     */
    public $query;

    /**
     * Server and execution environment parameters ($_SERVER).
     *
     * @var ServerBag
     */
    public $server;

    /**
     * Uploaded files ($_FILES).
     *
     * @var ParameterBag
     */
    public $files;

    /**
     * Cookies ($_COOKIE).
     *
     * @var ParameterBag
     */
    public $cookies;

    /**
     * Headers (taken from the $_SERVER).
     *
     * @var HeaderBag
     */
    public $headers;

    /**
     * @var string|resource|false|null
     */
    protected $content;

    /**
     * @var array
     */
    protected $languages;

    /**
     * @var array
     */
    protected $charsets;

    /**
     * @var array
     */
    protected $encodings;

    /**
     * @var array
     */
    protected $acceptableContentTypes;

    /**
     * @var string
     */
    protected $pathInfo;

    /**
     * @var string
     */
    protected $requestUri;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var string
     */
    protected $format;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var bool|null
     */
    protected $protoSSL;

    public static function createFromSwoole(SwooleRequest $swooleRequest)
    {
        $request = new static;
        $query = $swooleRequest->get ?? [];
        $requestBody = $swooleRequest->post ?? [];
        $cookie = $swooleRequest->cookie ?? [];
        $files = $swooleRequest->files ?? [];
        $server = $swooleRequest->server;
        $server = array_change_key_case($server,true);
        $request->initialize($query, $requestBody, [], $cookie, $files, $server);
        $request->headers = new  HeaderBag($swooleRequest->header ?? []);
        $request->fd = $swooleRequest->fd;
        return $request;
    }

    /**
     * 设置参数到请求实例中
     *
     * @param array                $query      The GET parameters
     * @param array                $request    The POST parameters
     * @param array                $attributes The request attributes (parameters parsed from the PATH_INFO, ...)
     * @param array                $cookies    The COOKIE parameters
     * @param array                $files      The FILES parameters
     * @param array                $server     The SERVER parameters
     * @param string|resource|null $content    The raw body data
     */
    public function initialize(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null)
    {
        $this->request = new ParameterBag($request);
        $this->query = new ParameterBag($query);
        $this->attributes = new ParameterBag($attributes);
        $this->cookies = new ParameterBag($cookies);
        $this->files = new ParameterBag($files);// new FileBag($files) 暂不支持文件
        $this->server = new ServerBag($server);
        $this->headers = new HeaderBag($this->server->getHeaders());

        $this->content = $content;
        $this->languages = null;
        $this->charsets = null;
        $this->encodings = null;
        $this->acceptableContentTypes = null;
        $this->pathInfo = null;
        $this->requestUri = null;
        $this->baseUrl = null;
        $this->basePath = null;
        $this->method = null;
        $this->format = null;
    }

    /**
     * 获取请求的所有数据和文件
     *
     * @param  array|mixed  $keys
     * @return array
     */
    public function all($keys = null)
    {
        $input = array_replace_recursive($this->input(), $this->allFiles());
        if (! $keys) {
            return $input;
        }

        $results = [];

        foreach (is_array($keys) ? $keys : func_get_args() as $key) {
            Arr::set($results, $key, Arr::get($input, $key));
        }

        return $results;
    }

    /**
     * 获取所有输入表单
     *
     * @param null $key
     * @param null $default
     * @return array|null
     * @author ELLER
     */
    public function input($key = null, $default = null)
    {
        return Arr::get(
            $this->getInputSource()->all() + $this->query->all(), $key, $default
        );
    }

    /**
     * 获取所有文件
     * @return array
     * @author ELLER
     */
    public function allFiles()
    {
        return $this->files->all();
    }

    /**
     * 获取PATH路径
     *
     * @return mixed|string
     * @author ELLER
     */
    public function path()
    {
        $pattern = trim($this->getPathInfo(), '/');

        return $pattern == '' ? '/' : $pattern;
    }

    /**
     * 获取PATH信息
     *
     * @return string
     * @author ELLER
     */
    public function getPathInfo()
    {
        if (null === $this->pathInfo) {
            $this->pathInfo = $this->preparePathInfo();
        }

        return $this->pathInfo;
    }

    /**
     * 解析path路径
     *
     * @return mixed|string
     * @author ELLER
     */
    protected function preparePathInfo()
    {
        if('' !== $pathInfo = $this->server->get('PATH_INFO')){
            return $pathInfo;
        }
        if('' !== $pathInfo = $this->server->get('REQUEST_URI')){
            return $pathInfo;
        }
        return "/";
    }

    /**
     * 获取用户代理信息(UserAgent)
     *
     * @return string
     */
    public function userAgent()
    {
        return $this->headers->get('User-Agent');
    }

    /**
     * 获取GET请求中的Query参数
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return Arr::get($this->query->all(), $key, $default);
    }

    /**
     * 设置JSON payload到请求
     *
     * @param  ParameterBag  $json
     * @return $this
     */
    public function setJson($json)
    {
        $this->json = $json;

        return $this;
    }

    /**
     * 获取所有请求内容和文件数据
     *
     * @return array
     */
    public function toArray()
    {
        return $this->all();
    }

    /**
     * 判断给定的键(偏移量)是否存在
     *
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return Arr::has(
            $this->all() + $this->route()->parameters(),
            $offset
        );
    }

    /**
     * 获取给定的键(偏移量)对应的值
     *
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    /**
     * 设置给定的键(偏移量)予以值
     *
     * @param  string  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->getInputSource()->set($offset, $value);
    }

    /**
     * 移除给定的键(偏移量)
     *
     * @param  string  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->getInputSource()->remove($offset);
    }

    /**
     * 检查给定的键是否设置
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return ! is_null($this->__get($key));
    }

    /**
     * 获取给定的键的值
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        $all = $this->all();
        if(isset($all[$key])){
            return $all[$key];
        }
        return $this->route($key);
    }

    /**
     * 获取这个请求类型的数据
     *
     * @return ParameterBag
     */
    protected function getInputSource()
    {
        if ($this->isJson()) {
            return $this->json();
        }

        return in_array($this->getRealMethod(), ['GET', 'HEAD']) ? $this->query : $this->request;
    }

    /**
     * 获取请求的预期请求方式
     *
     * 如果设置了 `X-HTTP-Method-Override` 且请求方式为POST
     * 则认为这是预期的请求方式，适应一些请求类型被当做HEADER重写的场景
     *
     * @return string The request method
     *
     * @see getRealMethod()
     */
    public function getMethod()
    {
        if (null === $this->method) {
            $this->method = strtoupper($this->server->get('REQUEST_METHOD', 'GET'));

            if ('POST' === $this->method) {
                if ($method = $this->headers->get('X-HTTP-METHOD-OVERRIDE')) {
                    $this->method = strtoupper($method);
                }
            }
        }

        return $this->method;
    }

    /**
     * 获取当前预期的请求方式
     *
     * @return string
     * @author ELLER
     */
    public function method()
    {
        return $this->getMethod();
    }

    /**
     * 获取真实的请求类型
     *
     * @return string The request method
     * @see getMethod()
     */
    public function getRealMethod()
    {
        return strtoupper($this->server->get('REQUEST_METHOD', 'GET'));
    }

    /**
     * 判断请求是否发送的json
     *
     * @return bool
     */
    public function isJson()
    {
        foreach(['/json', '+json'] as $needle){
            if(mb_strpos($this->headers('CONTENT_TYPE'), $needle) !== false){
                return true;
            }
        }
        return false;
    }

    /**
     * 获取请求中的标头
     *
     * @param  string  $key
     * @param  string|array|null  $default
     * @return string|array|null
     */
    public function headers($key = null, $default = null)
    {
        return $this->headers->get($key, $default);
    }

    /**
     * 获取处理请求的路由
     *
     * @param  string|null  $param
     * @param  mixed   $default
     * @return object|string
     */
    public function route($param = null, $default = null)
    {
        // 待实现从路由获取解析的参数值
        $route = null;// call_user_func($this->getRouteResolver());

        if (is_null($route) || is_null($param)) {
            return $route;
        }

        return $route->parameter($param, $default);
    }

    /**
     * 获取请求的JSON数据
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return ParameterBag|mixed
     */
    public function json($key = null, $default = null)
    {
        if (! isset($this->json)) {
            $this->json = new ParameterBag((array) json_decode($this->getContent(), true));
        }

        if (is_null($key)) {
            return $this->json;
        }

        return Arr::get($this->json->all(), $key, $default);
    }

    /**
     * 获取请求的Body内容
     *
     * @return string
     *
     * @throws \LogicException
     */
    public function getContent()
    {
        if (null === $this->content || false === $this->content) {
            $this->content = $this->origin->rawContent();
        }

        return $this->content;
    }

    /**
     * 获取请求的URL
     *
     * @return string
     */
    public function url()
    {
        return rtrim(preg_replace('/\?.*/', '', $this->getUri()), '/');
    }

    /**
     * 获取完整URL
     *
     * @return string
     */
    public function fullUrl()
    {
        $query = $this->getQueryString();

        $question = $this->getBaseUrl().$this->getPathInfo() === '/' ? '/?' : '?';

        return $query ? $this->url().$question.$query : $this->url();
    }

    /**
     * 为请求生成标准的URI(URL)
     *
     * @return string A normalized URI (URL) for the Request
     *
     * @see getQueryString()
     */
    public function getUri()
    {
        if (null !== $qs = $this->getQueryString()) {
            $qs = '?'.$qs;
        }

        return $this->getSchemeAndHttpHost().$this->getBaseUrl().$this->getPathInfo().$qs;
    }

    /**
     * 获取QueryString
     *
     * @return string|null
     * @author ELLER
     */
    public function getQueryString()
    {
        $qs = static::normalizeQueryString($this->server->get('QUERY_STRING'));

        return '' === $qs ? null : $qs;
    }

    /**
     * 将QueryString标准化
     *
     * It builds a normalized query string, where keys/value pairs are alphabetized,
     * have consistent escaping and unneeded delimiters are removed.
     *
     * @param string $qs Query string
     *
     * @return string A normalized query string for the Request
     */
    public static function normalizeQueryString($qs)
    {
        if ('' == $qs) {
            return '';
        }

        parse_str($qs, $qs);
        ksort($qs);

        return http_build_query($qs, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * 获取请求方式及HOST
     *
     * @return string The scheme and HTTP host
     */
    public function getSchemeAndHttpHost()
    {
        return $this->getScheme().'://'.$this->getHttpHost();
    }

    /**
     * 获取请求的方式HTTP/HTTPS
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    /**
     * 检查请求是否是安全传输
     * 如果是Nginx转发 需要在标头加入"X-Forwarded-Proto"
     *
     * The "X-Forwarded-Proto" header must contain the protocol: "https" or "http".
     *
     * @return bool
     */
    public function isSecure()
    {
        if(is_bool($this->protoSSL)){
            return $this->protoSSL ? true : false;
        }
        if($proto = $this->headers('X-Forwarded-Proto')){
            return strtolower(trim($proto)) == "https" ? true : false;
        }
        return false;
    }

    /**
     * 设置SSL启用标志
     * @author ELLER
     */
    public function enableSSLFlag()
    {
        $this->protoSSL = true;
    }

    /**
     * 返回请求的HOST
     *
     * @return string
     */
    public function getHttpHost()
    {
        $scheme = $this->getScheme();
        $port = $this->getPort();

        if (('http' == $scheme && 80 == $port) || ('https' == $scheme && 443 == $port)) {
            return $this->getHost();
        }

        return $this->getHost().':'.$port;
    }

    /**
     * 获取请求连接的端口
     * 优先从Header中Host提取端口号，若不存在则返回服务器监听端口
     *
     * @return mixed
     * @author ELLER
     */
    public function getPort()
    {
        if(!$host = $this->headers->get('host')){
            return $this->server->get('server_port');
        }

        if ('[' === $host[0]) {
            $pos = strpos($host, ':', strrpos($host, ']'));
        } else {
            $pos = strrpos($host, ':');
        }

        if (false !== $pos) {
            return (int) substr($host, $pos + 1);
        }
        return 'https' === $this->getScheme() ? 443 : 80;
    }

    /**
     * @return array|string|null
     * @author ELLER
     */
    public function getHost()
    {
        $host = $this->headers('host');
        $host = strtolower(preg_replace('/:\d+$/', '', trim($host)));
        return $host;
    }

    /**
     * 返回此请求的根URL
     *
     * The base URL never ends with a /.
     *
     * This is similar to getBasePath(), except that it also includes the
     * script filename (e.g. index.php) if one exists.
     *
     * @return string The raw URL (i.e. not urldecoded)
     */
    public function getBaseUrl()
    {
        if (null === $this->baseUrl) {
            $this->baseUrl = $this->prepareBaseUrl();
        }

        return $this->baseUrl;
    }

    /**
     * Prepares the base path.
     *
     * @return string base path
     */
    protected function prepareBasePath()
    {
        $baseUrl = $this->getBaseUrl();
        if (empty($baseUrl)) {
            return '';
        }

        $filename = basename($this->server->get('SCRIPT_FILENAME'));
        if (basename($baseUrl) === $filename) {
            $basePath = \dirname($baseUrl);
        } else {
            $basePath = $baseUrl;
        }

        if ('\\' === \DIRECTORY_SEPARATOR) {
            $basePath = str_replace('\\', '/', $basePath);
        }

        return rtrim($basePath, '/');
    }

    /**
     * 解析请求访问的根路径
     * 一般在二级目录才会出现
     * Swoole无需处理，进程单独监听端口的就是跟路径。
     *
     * @return string
     * @author ELLER
     */
    public function prepareBaseUrl()
    {
        return "";
    }

    /**
     * 获取客户端连接ID
     *
     * @return int
     * @author ELLER
     */
    public function getFd()
    {
        return $this->fd;
    }

}