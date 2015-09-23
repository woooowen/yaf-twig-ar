<?php
class ControllerPage extends Yaf_Controller_Abstract
{
    public function initView()
    {
        $config = Yaf_Application::app()->getConfig();
        $this->_view = new ViewTwig(APPLICATION_PATH.'/application/views', $config->twig->toArray());
    }



}
