<?php
define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../'));
define('CONF_PATH', APPLICATION_PATH . '/conf/');
$application = new Yaf_Application( APPLICATION_PATH . "/conf/application.ini");
$application->bootstrap();
$application->execute("main", $argc,  $argv);

function main($argc, $argv) {
    if ($argc < 3) {
        echo 'usage : php ',  __FILE__, ' db tablename', "\n";
        exit;
    }
    $db = $argv[1];
    $name = $argv[2];
    $dbName = TableSchema::db2Dbname($db);
    $conn = ConnectionManager::getConnection($db);
    $command = $conn->createCommand('desc ' . $name);
    $result = $command->queryAll();
    $columns = [];
    $primaryKey = '';
    $autoIncrement = '';
    foreach ($result as $define) {
        $columnName = $define['Field'];
        if ($define['Extra'] === 'auto_increment') {
            $autoIncrement = $columnName;
        }
        if ($define['Key'] === 'PRI') {
            $primaryKey = $columnName;
        }
        preg_match('/(.*?)\(/u', $define['Type'], $match);
        if ($match) {
            $type = $match[1];
        } else {
            $type = $define['Type'];
        }
        if (strpos($type, 'int') !== false) {
            $typeName = 'ColumnType::INTEGER';
            $type = ColumnType::INTEGER;
        } elseif ($type === 'decimal') {
            $typeName = 'ColumnType::DECIMAL';
            $type = ColumnType::DECIMAL;
        } else {
            $typeName = 'ColumnType::STRING';
            $type = ColumnType::STRING;
        }
        $columns[$columnName] = ['type' => $typeName, 'default' => ColumnType::cast($type, $define['Default'])];
    }
    ob_start();
    require(APPLICATION_PATH . '/application/code_template/schema_template.php');
    $content = ob_get_clean();
    $dst = APPLICATION_PATH . '/application/schemas/' . $name . '.php';
    file_put_contents($dst, $content);
}
