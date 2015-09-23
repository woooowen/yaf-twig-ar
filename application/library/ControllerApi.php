<?php
class ControllerApi extends Yaf_Controller_Abstract
{
    public function init()
    {
        $this->_view = new ViewJson();
    }

    public function assign($name, $value)
    {
        $this->_view->assign($name, $value);
    }
}
