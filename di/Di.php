<?php

namespace di;

class Di implements \ArrayAccess
{
    /*
     * 已绑定的服务列表
     * 为二维数组。一个类名(name)下还有一个class键描述类的具体信息，一个shared键描述该类是否为共享服务
     * 该class描述类的信息可以是匿名函数，可以是字符串(带命名空间的类地址)
     */
    private $_bindings = array();

    /*
     * 已实例化的服务列表
     * 其结构为一个类名（name）对应一个该类的实例化（value）
     */
    private $_instances = array();

    /**
     * 获取服务
     * @param $name
     * @param array $param
     * @return mixed|null|object
     */
    public function get($name, $param = array())
    {
        //该类已存在与已实例化的服务列表
        if (isset($this->_instances[$name])) {
            return $this->_instances[$name];
        }

        //该类在绑定服务列表中不存在
        if (!isset($this->_bindings[$name])) {
            return null;
        }

        //对象注册的具体内容
        $concrete = $this->_bindings[$name]['class'];

        //如果描述类信息为匿名函数
        if ($concrete instanceof \Closure) {
            $obj = call_user_func_array($concrete, $param);
        }

        //如果该描述类的信息为字符串
        if (is_string($concrete)) {
            //如果没有参数
            if (!$param) {
                $obj = new $concrete;
            } else {
                //如果有参数，则使用反射进行类的实例化
                $class = new \ReflectionClass($concrete);
                $obj = $class->newInstanceArgs($param);
            }
        }

        //如果为共享服务，且已有该类的实例化
        if ($this->_bindings[$name]['shared'] === true && $obj) {
            $this->_instances[$name] = $obj;
        }

        return $obj;
    }

    /**
     * 注册服务
     * @param $name
     * @param $class
     * @param bool $shared
     */
    private function _registerService($name, $class, $shared = false)
    {
        $this->remove($name);

        //如果此描述信息为一个实例化的类并且不为匿名函数
        if (!($class instanceof \Closure) && is_object($class)) {
            $this->_instances[$name] = $class;
        } else {
            $this->_bindings[$name] = array("class" => $class, "shared" => $shared);
        }
    }

    /**
     * 检测类是否绑定
     * @param $name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->_instances[$name]) or isset($this->_bindings[$name]);
    }

    /**
     * 卸载服务
     * @param $name
     */
    public function remove($name)
    {
        unset($this->_instances[$name], $this->_bindings[$name]);
    }

    /**
     * 设置服务
     * @param $name
     * @param $class
     */
    public function set($name, $class)
    {
        $this->_registerService($name, $class);
    }

    /**
     * 设置共享服务
     * @param $name
     * @param $class
     */
    public function setShared($name, $class)
    {
        $this->_registerService($name, $class, true);
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        //以$di[$name]方式获取服务
        return $this->get($offset);
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        //$di[$name]=$value方式注册服务
        $this->set($offset, $value);
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        //以unset($di[$name])方式卸载服务
        $this->remove($offset);
    }
}