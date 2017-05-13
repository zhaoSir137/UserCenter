<?php
/**
 * Created by PhpStorm.
 * User: marin
 * Date: 2017/5/12
 * Time: 21:57
 */

namespace net;


class Relink
{
    protected  $_client = null;
    protected  $_onReLink = null;
    protected  $_reTime = 0;
    protected  $_maxTime = 0;
    protected $_tick = false;

    protected $_reLinkIndex = 0;//Redis重连的次数

    public function __construct($client,callable $onReLink,$maxTime=3600000)
    {
        $this ->_client = $client;
        $this ->_onReLink = $onReLink;
        $this ->_maxTime = $maxTime;
    }

    public function OnReLink()
    {
        if($this->_reTime < $this ->_maxTime){
            if($this->_reTime){
                swoole_timer_tick($this->_reTime,[$this,'ReLink']);
                $this ->updateReTime();
            }else{
                $this->updateReTime();
                $this->ReLink();
            }
            return true;
        }
        return false;
    }

    public function ReLink()
    {
        call_user_func($this->_onReLink,$this->_client);
    }

    public function updateReTime()
    {
        $this->_reTime = 10000;//10秒之后重连
        $this->_reLinkIndex++;//重试次数
    }


}