<?php


namespace Mushroom\Core\Database;



class Model extends ArrayModel
{

    protected $_build = null;
    protected $table = "";

    private function build()
    {
        if (!$this->_build) {
            $this->_build = new Build($this, get_called_class(), $this->table);
        }
        return $this->_build;
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this, $name) === false) {
            return $this->build()->$name(...$arguments);
        } else {
            throw new DbException('call method ' . $name . ' fail , is not public method');
        }
    }

    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }

    public function toArray()
    {
        $obj = get_object_vars($this);
        foreach ($obj as &$v) {
            if (is_object($v)) {
                $v = $v->toArray();
            }
        }
        return $obj;
    }
}