<?php
class TableSchema
{
    public $dbName = '';
    public $name = '';
    public $primaryKey = '';
    public $autoIncrement = '';
    public $columns = [];

    private static $tables = [];

    public static function load($table)
    {
        if (!isset(self::$tables[$table])) {
            $schema_name = self::table2SchemaName($table);
            self::$tables[$table] = new $schema_name();
        }
        return self::$tables[$table];
    }

    public static function getColumns($table)
    {
        $schema = self::load($table);
        return $schema->columns;
    }

    public static function table2SchemaName($table) {
        return 'Schema_' . implode('', array_map('ucfirst', explode('_', $table)));
    }

    public static function db2DbName($db) {
        $config = ConnectionManager::getConfig($db);
        $dsn = $config['dsn'];
        preg_match_all('/dbname=(.*?);/', $dsn, $match);
        if (!$match) {
            throw new Exception("db {$db} has no name");
        }
        return $match[1][0];
    }

}
