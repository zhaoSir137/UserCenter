<?php
/**
 * Created by PhpStorm.
 * User: marin
 * Date: 2017/4/27
 * Time: 23:04
 */

namespace core;

/**
 * 将数组转换成数据对象
 * Class ArrayToObject
 * @package core
 */
class ArrayToObject
{
    protected $_data = null;
    public function __construct($data)
    {
        $this->_data = $data;
    }

    public function __get($name)
    {
        $ret = isset($this->_data[$name])?$this->_data[$name] :null;
        return is_array($ret)?new self($ret):$ret;
    }

    public function toArray()
    {
        return $this ->_data;
    }
}