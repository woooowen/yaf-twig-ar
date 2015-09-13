<?php
echo "<?php\n";
?>
class <?= $schema_name?> extends TableSchema
{
    public $dbName = '<?=$dbName?>';
    public $name = '<?=$name?>';
    public $primaryKey = '<?=$primaryKey?>';
    public $autoIncrement = '<?=$autoIncrement?>';
    public $columns = [
<?php
foreach ($columns as $column => $defines) {
    echo "\t\t\t\t'", $column, "'  => [";
    foreach ($defines as $k => $v) {
        echo "'{$k}' => ";
        if ($k !== 'default') {
            echo "{$v}, ";
            continue;
        }
        if (gettype($v) === 'integer' || gettype($v) === 'double') {
            echo "{$v}, ";
        } elseif ($v === 'NULL') {
            echo 'null, ';
        } else {
            echo "'{$v}', ";
        }
    }
    echo "],\n";
}
?>
            ];
}
