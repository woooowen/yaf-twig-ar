<?php
class Command
{
    public $db;
    public $pdoStatement;
    public $fetchMode = PDO::FETCH_ASSOC;

    private $_params = [];
    private $_sql = '';

    public function __construct($db, $sql = null) {
        $this->db = $db;
        $this->_sql = $sql;
    }

    public function getSql()
    {
        return $this->_sql;
    }

    public function setSql($sql)
    {
        if ($sql !== $this->_sql) {
            $this->cancel();
            $this->_sql = $sql;
            $this->_params = [];
        }
        return $this;
    }

    public function prepare()
    {
        if (!$this->pdoStatement) {
            $this->pdoStatement = $this->db->getPdo()->prepare($this->_sql);
        }
    }

    public function cancel()
    {
        $this->pdoStatement = null;
    }

    public function bindValues($values)
    {
        if (empty($values)) {
            return $this;
        }
        $this->_params = $values;
        return $this;
    }

    public function queryAll($fetchMode = null)
    {
        return $this->queryInternal('fetchAll', $fetchMode);
    }

    public function queryOne($fetchMode = null)
    {
        return $this->queryInternal('fetch', $fetchMode);
    }

    public function queryScalar()
    {
        return $this->queryInternal('fetchColumn', 0);
    }

    public function queryColumn()
    {
        return $this->queryInternal('fetchAll', PDO::FETCH_COLUMN);
    }

    public function insert($table, $columns)
    {
        $params = [];
        $sql = $this->db->getQueryBuilder()->insert($table, $columns, $params);
        $this->setSql($sql)->bindValues($params)->execute();
        return $this->pdoStatement->rowCount();
    }

    public function batchInsert($table, $rows)
    {
        $params = [];
        $sql = $this->db->getQueryBuilder()->batchInsert($table, $rows, $params);
        $this->setSql($sql)->bindValues($params)->execute();
        return $this->pdoStatement->rowCount();
    }

    public function update($table, $columns, $condition = [])
    {
        $params = [];
        $sql = $this->db->getQueryBuilder()->update($table, $columns, $condition, $params);
        $this->setSql($sql)->bindValues($params)->execute();
        return $this->pdoStatement->rowCount();
    }

    public function delete($table, $condition = [])
    {
        $params = [];
        $sql = $this->db->getQueryBuilder()->delete($table, $condition, $params);
        $this->setSql($sql)->bindValues($params)->execute();
        return $this->pdoStatement->rowCount();
    }

    protected function execute()
    {
        $start = microtime(true);
        $this->prepare();
        $this->pdoStatement->execute($this->_params);
        $end = microtime(true);
        Log::getInstance('mysql')->info($this->_sql, ['cost_time' => ($end - $start) * 1000, 'params' => $this->_params]);
    }

    protected function queryInternal($method, $fetchMode = null)
    {
        $this->execute();
        if ($fetchMode === null) {
            $fetchMode = $this->fetchMode;
        }
        $result = call_user_func_array([$this->pdoStatement, $method], (array) $fetchMode);
        $this->pdoStatement->closeCursor();
        return $result;
    }
}
