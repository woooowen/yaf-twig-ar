<?php
class ControllerPage extends Yaf_Controller_Abstract
{
    public function init()
    {
        $config = Yaf_Application::app()->getConfig();
        $this->_view = new ViewTwig(APPLICATION_PATH.'/application/views', $config->twig->toArray());
    }

    public function assign($name, $value)
    {
        $this->_view->assign($name, $value);
    }
}
