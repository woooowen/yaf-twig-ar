<?php
class RedisClient {
    private static $_config;
    private static $_clients;
    protected $redis;
    protected $host;
    protected $port;
    protected $timeout;
    protected $readTimeout;
    protected $connected = FALSE;
    protected $maxConnectRetries = 0;
    protected $connectFailures = 0;
    protected $subscribed = false;
    

    public function __construct($host = '127.0.0.1', $port = 6379, $timeout = null, $readTimeout = null , $retry = 0)
    {
        $this->host = strval($host);
        $this->port = intval($port);
        $this->timeout = $timeout;
        $this->readTimeout = $readTimeout;
        $this->maxConnectRetries = $retry;
    }

    public function __destruct()
    {
        $this->close();
    }
    
    public function isSubscribed()
    {
    	return $this->subscribed;
    }
    
    public function getHost()
    {
        return $this->host;
    }
    public function getPort()
    {
        return $this->port;
    }

    public function connect()
    {
        if ($this->connected) {
            return $this;
        }
        if ( ! $this->redis) {
            $this->redis = new Redis;
        }
        $result = $this->redis->connect($this->host, $this->port, $this->timeout);

        if (!$result) {
            $this->connectFailures++;
            if ($this->connectFailures <= $this->maxConnectRetries) {
                return $this->connect();
            }
            $failures = $this->connectFailures;
            $this->connectFailures = 0;
            throw new Exception("Connection to Redis failed after $failures failures." . (isset($errno) && isset($errstr) ? "Last Error : ({$errno}) {$errstr}" : ""));
        }

        $this->connectFailures = 0;
        $this->connected = TRUE;

        if ($this->readTimeout) {
            $this->redis->setOption(Redis::OPT_READ_TIMEOUT, $this->readTimeout);
        }
        return $this;
    }
    public function isConnected()
    {
        return $this->connected;
    }

    public function close()
    {
        if ($this->connected) {
            $this->redis->close();
        }
        $this->connected = FALSE;
    }

    public function pUnsubscribe()
    {
    	list($command, $channel, $subscribedChannels) = $this->__call('punsubscribe', func_get_args());
    	$this->subscribed = $subscribedChannels > 0;
    	return array($command, $channel, $subscribedChannels);
    }

    public function unsubscribe()
    {
    	list($command, $channel, $subscribedChannels) = $this->__call('unsubscribe', func_get_args());
    	$this->subscribed = $subscribedChannels > 0;
    	return array($command, $channel, $subscribedChannels);
    }

    public function __call($name, $args)
    {
        $this->connect();
        $name = strtolower($name);
        return call_user_func_array([$this->redis, $name], $args);
    }

    public static function getConfig($client)
    {
        if (!self::$_config) {
            self::$_config = (new Yaf_Config_Ini(CONF_PATH . '/redis.ini', Yaf_Application::app()->environ()))->toArray();
        }
        return self::$_config[$client];
    }

    public static function getClient($client) 
    {
		if (!isset(self::$_clients[$client])) {
            $config = self::getConfig($client);
			self::$_clients[$client] = new self($config['host'], $config['port'], $config['timeout'], $config['readtimeout'], $config['retry']);
        }
		return self::$_clients[$client];
    }
}
