<?php
class Log
{
    private $_name;
    private $_logger;
    private static $_config = [];
    private static $_instances = [];

    protected function __construct($config)
    {
        $this->_name = $config['name'];
        $this->_logger = new Monolog\Logger($this->_name);
        $this->_logger->pushHandler(new Monolog\Handler\RotatingFileHandler($config['filename']));
    }

    public static function getInstance($name)
    {
        if (!isset(self::$_instances[$name])) {
            $config = self::getConfig($name);
            self::$_instances[$name] = new self($config);
        }
        return self::$_instances[$name];

    }

    public function getLogger()
    {
        return $this->_logger;
    }

    public function __call($func, $args)
    {
        var_dump($func);
        return call_user_func_array([$this->_logger, $func], $args);
    }
    public static function getConfig($name)
    {
        if (!self::$_config) {
            self::$_config = (new Yaf_Config_Ini(CONF_PATH . '/log.ini', Yaf_Application::app()->environ()))->toArray();
        }
        return self::$_config[$name];
    }
}