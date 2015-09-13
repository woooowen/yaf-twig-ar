<?php
class Bootstrap extends Yaf_Bootstrap_Abstract{

	public function _initComposerAutoload(Yaf_Dispatcher $dispatcher) {
        Yaf_Loader::import(APPLICATION_PATH . '/vendor/autoload.php');
    }

	public function _initErrorHandler(Yaf_Dispatcher $dispatcher) {
		Monolog\ErrorHandler::register(Log::getInstance('error')->getLogger());
	}

	public function _initPlugin(Yaf_Dispatcher $dispatcher) {
	}

	public function _initRoute(Yaf_Dispatcher $dispatcher) {
		//在这里注册自己的路由协议,默认使用简单路由
	}

	public function _initView(Yaf_Dispatcher $dispatcher){
        $config = Yaf_Application::app()->getConfig();
        $dispatcher->setView(new Twig(APPLICATION_PATH.'/application/views', $config->twig->toArray()));
    }

}
