<?php
class Schema_User extends TableSchema
{
    public $dbName = 'xp';
    public $name = 'user';
    public $primaryKey = 'id';
    public $autoIncrement = 'id';
    public $columns = [
				'id'  => ['type' => ColumnType::INTEGER, 'default' => 0, ],
				'name'  => ['type' => ColumnType::STRING, 'default' => '', ],
				'password'  => ['type' => ColumnType::STRING, 'default' => '', ],
				'phone'  => ['type' => ColumnType::INTEGER, 'default' => 0, ],
				'ctime'  => ['type' => ColumnType::INTEGER, 'default' => 0, ],
				'mtime'  => ['type' => ColumnType::INTEGER, 'default' => 0, ],
				'money'  => ['type' => ColumnType::DECIMAL, 'default' => 0, ],
            ];
}
