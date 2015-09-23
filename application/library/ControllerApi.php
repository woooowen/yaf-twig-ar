<?php
class ControllerApi extends Yaf_Controller_Abstract
{
    public function initView()
    {
        $this->_view = new ViewJson();
    }
}
