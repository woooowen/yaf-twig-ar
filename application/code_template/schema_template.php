<?php
echo "<?php\n";
?>
return [
    'database' => '<?=$dbName?>',
    'table' => '<?=$name?>',
    'primaryKey' => '<?=$primaryKey?>',
    'autoIncrement' => '<?=$autoIncrement?>',
    'columns' => [
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
            ],
];
