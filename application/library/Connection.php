<?php
class Connection
{
    public $dsn;
    public $username;
    public $password;
    public $attributes;
    public $pdo;
    public $pdoClass;
    public $enableSavepoint = true;
    public $serverRetryInterval = 600;

    public function isConnected()
    {
        return $this->pdo !== null;
    }

    public function __construct($dsn, $username, $password)
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->open();
    }

    public function open()
    {
        if ($this->isConnected()) {
            return;
        }
        $this->pdo = new PDO($this->dsn, $this->username, $this->password, $this->attributes);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('SET NAMES utf8');
    }

    public function close()
    {
        if ($this->pdo !== null) {
            $this->pdo = null;
        }
    }

    public function createCommand($sql = null, $params = [])
    {
        $command = new Command($this, $sql);
        return $command->bindValues($params);
    }

    public function transaction(callable $callback)
    {
        $this->beginTransaction();
        try {
            $result = call_user_func($callback, $this);
            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }

        return $result;
    }

    public function getQueryBuilder()
    {
        return new QueryBuilder($this);
    }

    public function getPdo()
    {
        $this->open();
        return $this->pdo;
    }

    public function beginTransaction()
    {
        $this->open();
        $this->pdo->beginTransaction();
        return $this;
    }

    public function commit()
    {
        $this->pdo->commit();
        return $this;
    }

    public function rollBack()
    {
        $this->pdo->rollBack();
        return $this;
    }

}
