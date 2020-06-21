<?php


namespace Mushroom\Core\Database;

use Swoole\Coroutine\MySQL;

class Connect
{
    use ConfigTrait;

    private $config = [];

    // 数据库配置连接标志
    private $key;

    private $transaction_id = null;
    private static $resource = null;

    public function __construct($key = "db_master")
    {
        $this->key    = $key;
//        $this->config = self::$conf[$key];
    }

    /**
     * 获取数据库连接标志
     * @return string
     * @author ELLER
     */
    public function getKey()
    {
        return $this->key;
    }

    public function transactionId($id)
    {
        $this->transaction_id = $id;
    }

    /**
     * 执行SQL
     * @param $sql
     * @param array $data
     * @return array|bool
     * @throws DbException
     * @author ELLER
     */
    protected function execute($sql, $data = [])
    {
        try{
            $resources = $this->getRes();
            $res = $resources->prepare($sql);
            if(!$res){
                var_dump($resources->error);
                throw new DbException(json_encode([$resources->errno, $resources->error]));
                return false;
            }else{
                return [$res->execute($data), $res];
            }
        } catch (\Throwable $e) {
            throw new DbException(json_encode(['info' => $e->getMessage(), 'sql' => $sql]), $e->getCode());
        }
    }

    /**
     * 获取MySQL链接
     * @return MySQL|null
     * @author ELLER
     */
    protected function getRes()
    {
        self::$resource = null;
        if (self::$resource == null) {
            $httpConfig = app()->getConfig('database');
            $swoole_mysql = new MySQL();
            $httpConfig['servers'][$this->key]['fetch_mode'] = true;
            try {
                $connectRes = $swoole_mysql->connect($httpConfig['servers'][$this->key]);

            } catch (\Exception $exception) {
                var_dump($exception->getMessage());
                throw new $exception;
            }
            if(!$connectRes){
                var_dump('mysql connect fail!');
                var_dump($swoole_mysql->connect_error);
            }
            self::$resource = $swoole_mysql;
        }
        return self::$resource;
    }

    /**
     * 获取一条数据
     * @param $sql
     * @param $data
     * @return mixed
     * @throws DbException
     * @author ELLER
     */
    public function find($sql, $data = [])
    {
        // 兼容swoole4.4的fetch单条问题
        // 参见：https://wiki.swoole.com/wiki/page/942.html
        if(strpos($sql," limit ") == false){
            $sql = rtrim($sql);
            if(substr($sql, -1,1) == ";"){
                $sql = rtrim($sql, ";");
            }
            $sql.= " LIMIT 1";
        }
        list($result, $res) = $this->execute($sql, $data);
        return $result ? $res->fetch() : $result;
    }

    /**
     * 获取所有数据
     * @param $sql
     * @param $data
     * @return array|bool
     * @throws DbException
     * @author ELLER
     */
    public function findAll($sql, $data = [])
    {
        list($result, $res) = $this->execute($sql, $data);
        return $result ? $res->fetchAll() : $result;
    }

    /**
     * 执行SQL语句
     * @param $sql
     * @param array $data
     * @param bool $lastId 是否获取插入ID | 影响条数
     * @return mixed
     * @throws DbException
     * @author ELLER
     */
    public function exec($sql, $data = [], $lastId = false)
    {
        list($result, $res) = $this->execute($sql, $data);
        if (isset($res->error) && !empty($res->error)) {
            throw new DbException($res->error, $res->errno);
        }
        if($lastId){
            $r = $res->insert_id;
        }else{
            $r = $res->affected_rows;
        }
        return $r;
    }
}