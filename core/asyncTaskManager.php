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

    public function push($name,$callback=null)
    {
        if(!$callback){
            $callback = function(){
              $args = func_get_args();
              return count($args)==1?reset($args):$args;
            };
        }

        $this ->_pushCallBack[$name] = $callback;
        $this ->_callbacks[$name] = function()use($name){
            $args = func_get_args();
            $callbackResult=call_user_func_array($this->_pushCallBack,$args);
            if(!$this ->asynSysHandle($callbackResult,$name,$args)){
                $this ->_callbackReturn[$name] = $callbackResult;
                if(count($this->_callbackReturn) == count($this->_pushCallBack)){
                    //todo 回调完成 这里开始执行回调完成之后的方法
                }
            }
        };

    }

    /**
     * 异步回调处理
     */
    protected function asynSysHandle($callbackReturn,$name,$args)
    {
        if(!(is_array($callbackReturn) && reset($callbackReturn) == '__asyn__')){
            return false;
        }
        $handleCode = $callbackReturn[1];
        if($handleCode == 301){
            $this ->_pushCallBack[$name] = $callbackReturn[2];
            if(isset($callbackReturn[3])){
                $args = array_merge($args,$callbackReturn[3]);
            }
            call_user_func_array($this->_callbacks[$name],$args);
        }
    }

    public function pop($name)
    {
        if(!isset($this->_callbacks[$name])){
            $this ->push($name);
        }
        return $this ->_callbacks[$name]?$this->_callbacks[$name]:null;
    }
}