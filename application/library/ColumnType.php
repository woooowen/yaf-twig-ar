<?php
class ColumnType
{
	// types for $type
	const STRING	= 1;
	const INTEGER	= 2;
	const DECIMAL	= 3;

	public static $dbTypeMap = [
		    'tinyint'	=> self::INTEGER,
            'smallint'	=> self::INTEGER,
            'mediumint'	=> self::INTEGER,
            'int'		=> self::INTEGER,
            'bigint'	=> self::INTEGER,
            'decimal'	=> self::DECIMAL
        ];

    public static $pdoTypeMap = [
            'integer' => PDO::PARAM_INT,
            'string' => PDO::PARAM_STR,
            'double' => PDO::PARAM_STR,
        ];

	public static function cast($type, $value)
	{
		switch ($type)
		{
			case self::STRING:	return strval($value);
			case self::INTEGER:	return intval($value);
			case self::DECIMAL:	return floatval($value);
		}
		return $value;
	}

    public static function castAndPdoType($type, $value)
    {
        switch ($type)
		{
			case self::STRING:	return [strval($value), PDO::PARAM_STR];
			case self::INTEGER:	return [intval($value), PDO::PARAM_INT];
			case self::DECIMAL:	return [floatval($value), PDO::PARAM_STR];
		}
		return $value;
    }

    public static function getPdoType($data)
    {
        $type = gettype($data);
        return isset($pdoTypeMap[$type]) ? $pdoTypeMap[$type] : PDO::PARAM_STR;
    }

}
