<?php
/**
 * Created by PhpStorm.
 * User: marin
 * Date: 2017/4/25
 * Time: 22:56
 */

namespace core;


class asyncTaskManager
{
    protected $_callbacks = [];//实际回调
    protected $_pushCallBack = [];//原始回调
    protected $_callbackReturn = [];//回调返回值
    protected $_onComplete = null;//所有回调都执行完成之后执行的方法

    public function push($name, $callback = null)
    {
        if (!$callback) {
            $callback = function () {
                $args = func_get_args();
                return count($args) == 1 ? reset($args) : $args;
            };
        }

        $this->_pushCallBack[$name] = $callback;//加入到原始回调数组中
        $this->_callbacks[$name] = function () use ($name) {//加工成实际回调
            $args = func_get_args();
            $callbackResult = call_user_func_array($this->_pushCallBack[$name], $args);
            if (!$this->asynSysHandle($callbackResult, $name, $args)) {
                $this->_callbackReturn[$name] = $callbackResult;
                if (count($this->_callbackReturn) == count($this->_pushCallBack)) {
                    //todo 回调完成 这里开始执行回调完成之后的方法
                    if ($this->_onComplete) {
                        $callbackReturn = $this->_callbackReturn;
                        $this->_callbackReturn = [];
                        call_user_func($this->_onComplete, $this, $callbackReturn);
                    }
                }
            }
        };
        return $this->_callbacks[$name];
    }

    /**
     * 异步回调处理
     * @param $callbackReturn
     * @param $name
     * @param $args
     * @return bool
     */
    protected function asynSysHandle($callbackReturn, $name, $args)
    {
        if (!(is_array($callbackReturn) && reset($callbackReturn) == '__asyn__')) {
            return false;
        }
        $handleCode = $callbackReturn[1];
        //如果是301 表示需要重定向到其他处理函数
        if ($handleCode == 301) {
            $this->_pushCallBack[$name] = $callbackReturn[2];
            if (isset($callbackReturn[3])) {
                $args = array_merge($args, $callbackReturn[3]);
            }
            call_user_func_array($this->_callbacks[$name], $args);
        }
        return true;
    }

    /**
     * 取回调
     * @param $name
     * @return mixed|null
     */
    public function pop($name)
    {
        if (!isset($this->_callbacks[$name])) {
            $this->push($name);
        }
        return $this->_callbacks[$name] ? $this->_callbacks[$name] : null;
    }


    public function onComplete($callback)
    {
        $this->_onComplete = $callback;
    }

    public function resetCallBackTask()
    {
        $this->_pushCallBack = [];
        $this->_callbacks = [];
        $this->_callbackReturn = [];
    }
}