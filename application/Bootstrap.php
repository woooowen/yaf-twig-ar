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
	}

}
