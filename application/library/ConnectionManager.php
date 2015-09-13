<?php
class ConnectionManager
{
	private static $_connections = [];
    private static $_config = null;

	public static function getConnection($db)
	{
		if (!isset(self::$_connections[$db])) {
            $_config = self::getConfig($db);
			self::$_connections[$db] = new Connection($_config['dsn'], $_config['user'], $_config['password']);
        }
		return self::$_connections[$db];
	}

	public static function dropConnection($db)
	{
		if (isset(self::$_connections[$db])) {
            unset(self::$_connections[$db]);
        }
	}

    public static function getConfig($db)
    {
        if (!self::$_config) {
            self::$_config = (new Yaf_Config_Ini(CONF_PATH . '/db.ini', Yaf_Application::app()->environ()))->toArray();
        }
        return self::$_config[$db];
    }
}
