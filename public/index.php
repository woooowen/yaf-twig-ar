<?php
define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/..'));
define('CONF_PATH', APPLICATION_PATH . '/conf/');
$application = new Yaf_Application( APPLICATION_PATH . "/conf/application.ini");
if ($application->environ() === 'dev') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
$application->bootstrap()->run();
