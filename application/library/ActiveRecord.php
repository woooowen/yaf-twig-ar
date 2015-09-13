<?php
class ActiveRecord implements ArrayAccess, JsonSerializable
{
    const EVENT_BEFORE_INSERT = 'beforeInsert';
    const EVENT_BEFORE_UPDATE = 'beforeUpdate';
    const EVENT_BEFORE_DELETE = 'beforeDelete';
    const EVENT_AFTER_INSERT = 'afterInsert';
    const EVENT_AFTER_UPDATE = 'afterUpdate';
    const EVENT_AFTER_DELETE = 'afterDelete';

    public static $table_name = '';
    public static $name = '';
    public static $db_conf = '';

    protected $_fields = [];
    protected $_old_fields = [];
    protected $_events = [];

    public function __construct()
    {
        $this->_fields = array_fill_keys(array_keys(static::getColumns()), null);
    }

    public static function getTableSchema()
    {
        return TableSchema::load(static::$table_name);
    }

    public static function getTableName()
    {
        return static::$table_name;
    }

    public static function getDbName()
    {
        return static::$name;
    }

    public static function getColumns() {
        $schema = static::getTableSchema();
        return $schema->columns;
    }

    public static function getPrimaryKey()
    {
        $schema = static::getTableSchema();
        return $schema->primaryKey;
    }

    public static function getDb()
    {
        return ConnectionManager::getConnection(static::$db_conf);
    }

    public static function findBySql($sql, $params = [], $asArray = false, $columns = [])
    {
        $query = new Query(static::getDb(), static::getTableName(), get_called_class());
        $query->sql = $sql;
        if ($columns) {
            $query->select($columns);
        }
        return $query->addParams($params)->asArray($asArray)->all();
    }


    protected static function findByCondition($condition)
    {
        $query = new Query(static::getDb(), static::getTableName(), get_called_class());
        return $query->where($condition);
    }



    public static function updateAll($fields, $condition = [])
    {
        $command = static::getDb()->createCommand();
        $command->update(static::getTableName(), $fields, $condition);
        return $command->execute();
    }

    public static function deleteAll($condition = [])
    {
        $command = static::getDb()->createCommand();
        $command->delete(static::getTableName(), $condition);
        return $command->execute();
    }


    public static function populateRecord($record, $row)
    {
        $columns = static::getTableSchema()->columns;
        foreach ($row as $name => $value) {
            if (isset($columns[$name])) {
                $row[$name] = ColumnType::cast($columns[$name]['type'], $value);
                $record->_fields[$name] = $value;
            }
        }
        $record->_old_fields = $record->_fields;
    }

    public function getLastInsertID()
    {
        if (static::getDb()->isActive) {
            return static::getDb()->pdo->lastInsertId();
        } else {
            throw new Exception('DB Connection is not active.');
        }
    }

    public function setDefaultValue()
    {
        foreach (static::getTableSchema()->columns as $column => $define) {
            if (!is_null($define['default']) && is_null($this->_fields[$column])) {
                $this->_fields[$column] = $define['default'];
            }
        }
        return $this;
    }

    public function insert(array $fields = [])
    {
        if (!$this->beforeSave(true)) {
            return false;
        }
        foreach ($fields as $name => $value) {
            $this->setField($name, $value);
        }
        $values = $this->getDirtyFields();
        $command = static::getDb()->createCommand()->insert(static::getTableName(), $values);
        if (!$command->execute()) {
            return false;
        }
        $schema = static::getTableSchema();
        $pk = $schema->primaryKey;
        if (!isset($this->_fields[$pk])) {
            if ($schema->autoIncrement) {
                $value_of_pk = $this->getLastInsertID();
                $this->setField($pk, ColumnType::cast($schema->columns[$pk]['type'], $value_of_pk));
            } else {
                throw Exception('primary key is not set');
            }
        }
        $this->setDefaultValue();
        $this->afterSave(true);
        $this->_old_fields = $this->_fields;
        return true;
    }

    public static function findOne($condition, $orderBy = [], $asArray = false, $columns = [])
    {
        $query = static::findByCondition($condition);
        if ($columns) {
            $query->select($columns);
        }
        if ($orderBy) {
            $query->orderBy($orderBy);
        }
        $query->asArray($asArray)->offset(0)->limit(1);
        return $query->one();
    }

    public static function findAll($condition, $orderBy = [], $offset = null, $limit = null, $asArray = false, $columns = [])
    {
        $query = static::findByCondition($condition);
        if ($columns) {
            $query->select($columns);
        }
        if ($orderBy) {
            $query->orderBy($orderBy);
        }
        if (!is_null($limit)) {
            $limit = intval($limit);
            $query->limit($limit);
        }
        if (!is_null($offset)) {
            $limit = intval($offset);
            $query->offset($offset);
        }
        return $query->asArray($asArray)->all();
    }

    public function __get($name)
    {
        return $this->_fields[$name];
    }

    public function __set($name, $value)
    {
        $this->setField($name, $value);
    }

    public function setField($name, $value)
    {
        if (array_key_exists($name, $this->_fields)) {
            $this->_fields[$name] = $value;
        }
    }

    public function getDirtyFields()
    {
        $dirty_fields = [];
        if (!$this->_old_fields) {
            foreach ($this->_fields as $name => $value) {
                if (!is_null($value)) {
                    $dirty_fields[$name] = $value;
                }
            }
        } else {
            foreach ($this->_fields as $name => $value) {
                if (!is_null($value) && (!array_key_exists($name, $this->_old_fields) || $value !== $this->_old_fields[$name])) {
                    $dirty_fields[$name] = $value;
                }
            }
        }
        return $dirty_fields;
    }

    public function save(array $fields = [])
    {
        if ($this->isNewRecord()) {
            return $this->insert($fields);
        } else {
            return $this->update($fields) !== false;
        }
    }


    public function update(array $fields = [])
    {
        if (!$this->beforeSave(false)) {
            return false;
        }
        foreach ($fields as $name => $value) {
            $this->setField($name, $value);
        }
        $values = $this->getDirtyFields();
        if (empty($values)) {
            return 0;
        }
        $pk = static::getPrimaryKey();
        $rows = static::updateAll($values, [$pk => $this->_old_fields[$pk]]);
        $this->afterSave(false);
        return $rows;
    }

    public function delete()
    {
        $result = false;
        if ($this->beforeDelete()) {
            $pk = static::getPrimaryKey();
            $result = static::deleteAll([$pk => $this->_fields[$pk]]);
            $this->_old_fields = null;
            $this->afterDelete();
        }

        return $result;
    }

    public function isNewRecord()
    {
        return empty($this->_old_fields);
    }

    public function beforeSave($insert)
    {
        $this->trigger($insert ? static::EVENT_BEFORE_INSERT : static::EVENT_BEFORE_UPDATE);
        return true;
    }

    public function afterSave($insert)
    {
        $this->trigger($insert ? static::EVENT_AFTER_INSERT : static::EVENT_AFTER_UPDATE);
    }

    public function beforeDelete()
    {
        $this->trigger(static::EVENT_BEFORE_DELETE);
        return true;
    }

    public function afterDelete()
    {
        $this->trigger(static::EVENT_AFTER_DELETE);
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->_fields);
    }

    public function offsetUnset($offset)
    {
        $this->_fields[$offset] = null;
    }

    public function offsetGet($offset)
    {
        return $this->_fields[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->setField($offset, $value);
    }
    
    public function rewind()
    {
        return reset($this->_fields);
    }

    public function current()
    {
        return current($this->_fields);
    }

    public function key()
    {
        return key($this->_fields);
    }

    public function next()
    {
        return next($this->_fields);
    }

    public function valid()
    {
        return key($this->_fields) !== null;
    }

    public function jsonSerialize()
    {
        return json_encode($this->_fields);
    }

    public function trigger($event)
    {
        if (isset($this->_events[$event])) {
            foreach ($this->_events[$event] as $callback) {
                call_user_func_array($callback[0], $callback[1]);
            }
        }
    }

    public function register($event, Callabel $closure, $args)
    {
        if (isset($this->_events[$event])) {
            $this->_events[$event][] = [$closure, $args];
        } else {
            $this->_events[$event] = [[$closure, $args]];
        }
    }
}
