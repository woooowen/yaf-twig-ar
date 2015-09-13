<?php
class Query
{
    public $sql = '';
    public $modelClass = '';
    public $from = '';
    public $select = [];
    public $selectOption = '';
    public $selectLockOption = '';
    public $groupBy = [];
    public $params = [];
    public $asArray = true;
    public $where = [];
    public $limit = null;
    public $offset = null;
    public $orderBy = [];
    public $indexBy = '';
    public $distinct = false;
    public $having = [];

    public function __construct(Connection $db, $table, $modelClass = '')
    {
        $this->db = $db;
        $this->from = $table;
        $this->modelClass = $modelClass;
    }

    public function all()
    {
        $rows = $this->createCommand()->queryAll();
        return $this->populate($rows);
    }

    public function populate($rows)
    {
        if (empty($rows)) {
            return [];
        }
        $models = $this->createModels($rows);
        return $models;
    }

    public function one()
    {
        $row = $this->createCommand()->queryOne();
        if ($row !== false) {
            $models = $this->populate([$row]);
            return reset($models) ?: ($this->asArray() ? [] : null);
        } else {
            return $this->asArray ? []: null;
        }
    }

    public function createCommand()
    {
        if (!$this->sql) {
            list ($this->sql, $this->params) = $this->db->getQueryBuilder()->build($this);
        }
        return $this->db->createCommand($this->sql, $this->params);
    }

    public function addParams($params) {
        $this->params = $params;
        return $this;
    }

    public function scalar()
    {
        return $this->createCommand()->queryScalar();
    }

    public function column()
    {
        $rows = $this->createCommand()->queryAll();
        if (!$this->indexBy) {
            return $rows;
        }
        $results = [];
        foreach ($rows as $row) {
            $results[$row[$this->indexBy]] = reset($row);
        }
        return $results;
    }

    public function count($q = '*')
    {
        return $this->queryScalar("COUNT($q)");
    }

    public function sum($q)
    {
        return $this->queryScalar("SUM($q)");
    }

    public function average($q)
    {
        return $this->queryScalar("AVG($q)");
    }

    public function min($q)
    {
        return $this->queryScalar("MIN($q)");
    }

    public function max($q)
    {
        return $this->queryScalar("MAX($q)");
    }

    public function exists()
    {
        $this->select = '1';
        $this->limit = 1;
        return $this->createCommand()->queryScalar() !== false;
    }

    protected function queryScalar($select)
    {
        $this->select = [$select];
        return $this->createCommand()->queryScalar();

    }

    public function select($columns, $option = '', $lock_option = '')
    {
        $this->select = $columns;
        $this->selectOption = $option;
        $this->selectLockOption = $lock_option;
        return $this;
    }


    public function distinct($value = true)
    {
        $this->distinct = $value;
        return $this;
    }

    public function from($table)
    {
        $this->from = $table;
        return $this;
    }

    public function where($condition)
    {
        $this->where = $condition;
        return $this;
    }

    public function groupBy($columns)
    {
        $this->groupBy = $columns;
        return $this;
    }


    public function having($condition)
    {
        $this->having = $condition;
        return $this;
    }

    public function asArray($value = true)
    {
        $this->asArray = $value;
        return $this;
    }

    private function createModels($rows)
    {
        $models = [];
        if ($this->asArray) {
            if (!$this->indexBy) {
                return $rows;
            }
            foreach ($rows as $row) {
                $key = $row[$this->indexBy];
                $models[$key] = $row;
            }
        } else {
            if (!class_exists($this->modelClass)) {
                throw new Exception(__CLASS__ . ' createModel {$this->modelClass} not exists');
            }
            $class = $this->modelClass;
            if (!$this->indexBy) {
                foreach ($rows as $row) {
                    $model = new $class;
                    $class::populateRecord($model, $row);
                    $models[] = $model;
                }
            } else {
                foreach ($rows as $row) {
                    $model = new $class;
                    $class::populateRecord($model, $row);
                    $key = $model->{$this->indexBy};
                    $models[$key] = $model;
                }
            }
        }
        return $models;
    }

    public function indexBy($column)
    {
        $this->indexBy = $column;
        return $this;
    }

    public function orderBy($columns)
    {
        $this->orderBy = $columns;
        return $this;
    }

    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }
}
