<?php
class QueryBuilder
{
    protected $conditionBuilders = [
        '<' => 'buildLessCondition',
        '<=' => 'buildLessEqualCondition',
        '>' => 'buildGreaterCondition',
        '>=' => 'buildGreaterEqualCondition',
        '!=' => 'buildNotEqualCondition',
        'between' => 'buildBetweenCondition',
        'not between' => 'buildNotBetweenCondition',
        'in' => 'buildInCondition',
        'not in' => 'buildInCondition',
        'like' => 'buildLikeCondition',
        'not like' => 'buildLikeCondition',
    ];


    public function __construct($connection)
    {
        $this->db = $connection;
    }

    public function build($query, $params = [])
    {
        $params = empty($params) ? $query->params : array_merge($params, $query->params);
        $clauses = [
            $this->buildSelect($query->select, $params, $query->distinct, $query->selectOption),
            $this->buildFrom($query->from),
            $this->buildWhere($query->where, $params),
            $this->buildGroupBy($query->groupBy),
            $this->buildHaving($query->having, $params),
            $this->buildOrderBy($query->orderBy),
            $this->buildLimitAndOffset($query->limit, $query->offset),
        ];

        $sql = implode(' ', array_filter($clauses));
        return [$sql, $params];
    }

    public function insert($table, $columns, &$params)
    {
        $columnSchemas = TableSchema::getColumns($table);
        $params = [];
        foreach ($columns as $name => $value) {
            $params[] = ColumnType::cast($columnSchemas[$name]['type'], $value);
        }
        return 'INSERT INTO ' . $this->quoteMeta($table)
            . ' (' . implode(', ', [[$this, 'quoteMeta'], array_keys($columns)]) . ')'
            . ' VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')';
    }

    public function batchInsert($table, $rows, &$params)
    {
        $columnSchemas = TableSchema::getColumns($table);
        $params = [];
        if (!$rows) {
            return '';
        }
        $columns = array_keys(reset($rows));
        foreach ($rows as $row) {
            foreach ($row as $column => $value) {
                $params[] = ColumnType::cast($column, $value);
            }
        }
        return 'INSERT INTO ' . $this->quoteMeta($table)
            . ' (' . implode(', ', array_map([$this, 'quoteMeta'], $columns))
            . ') VALUES ' . implode(', ', array_fill(0, count($rows), '(' . implode(', ', array_fill(0, count($columns), '?')) . ')'));
    }

    protected function quoteMeta($str)
    {
        return "`{$str}`";
    }

    public function update($table, $columns, $condition, &$params)
    {
        $params = $part_params = [];
        $columnSchemas = TableSchema::getColumns($table);
        foreach ($columns as $name => $value) {
            $params[] = ColumnType::cast($columnSchemas[$name]['type'], $value);
        }
        $sql = 'UPDATE ' . $this->quoteMeta($table . ' SET '
               . implode(', ', array_map(function($v) {$v = $this->quoteMeta($v); $return "{$v} = ?";}, array_keys($columns)));
        $where = $this->buildWhere($condition, $part_params);
        $params = array_merge($params, $part_params);
        return $where === '' ? $sql : $sql . ' ' . $where;
    }

    public function delete($table, $condition, &$params)
    {
        $sql = 'DELETE FROM ' . $this->quoteMeta($table);
        $where = $this->buildWhere($condition, $params);
        return $where === '' ? $sql : $sql . ' ' . $where;
    }

    public function buildSelect($columns, $distinct = false, $selectOption = '', $lockOption= '')
    {
        $select = $distinct ? 'SELECT DISTINCT' : 'SELECT';
        if ($selectOption !== null) {
            $select .= ' ' . $selectOption;
        }
        if (empty($columns)) {
            return $select . ' * ';
        }
        $select =  $select . ' ' . implode(', ', array_map([$this, 'quoteMeta'], $columns));
        return $lockOption ? $select . ' '. $lockOption : $select;
    }

    public function buildFrom($table)
    {
        if (empty($table)) {
            return '';
        }
        $table = $this->quoteMeta($table);
        return 'FROM ' . $table;
    }

    public function buildWhere($condition, &$params)
    {
        if (!$condition) {
            return '';
        }
        $where = $this->buildCondition($condition, $params);
        return $where === '' ? '' : 'WHERE ' . $where;
    }

    public function buildGroupBy($columns)
    {
        return empty($columns) ? '' : 'GROUP BY ' . $this->buildColumns($columns);
    }

    public function buildHaving($condition, &$params)
    {
        if (!$condition) {
            return '';
        }
        $having = $thiNots->buildCondition($condition, $params);
        return $having === '' ? '' : 'HAVING ' . $having;
    }


    public function buildOrderBy($columns)
    {
        if (empty($columns)) {
            return '';
        }
        $orders = [];
        foreach ($columns as $name => $direction) {
            $name = $this->quoteMeta($name);
            $orders[] = "{$name} {$direction}";
        }
        return 'ORDER BY ' . implode(', ', $orders);
    }


    public function buildLimitAndOffset($limit, $offset)
    {
        $limit = intval($limit);
        $offset = intval($offset);
        if ($limit) {
            return "LIMIT {$offset}, {$limit}";
        } else {
            return $offset ? 'OFFSET ' . $offset : '';
        }
    }

    public function buildColumns(array $columns)
    {
        return implode(', ', array_map([$this, 'quoteMeta'], $columns));
    }

    public function buildCondition($condition, &$params)
    {
        $params = $part_params = $parts = [];
        foreach ($condition as $column => $value) {
            if (!is_array($value)) {
                $column = $this->quoteMeta($column);
                $parts[] = "{$column} = ?";
                $params[] = $value;
            } else {
                $operator = array_shift($value);
                $method = $this->conditionBuilders[$operator];
                $operand = trim($this->$method($column, $value, $part_params));
                if ($operand) {
                    $parts[] = $operand;
                    if (!is_array($part_params)) {
                        $params[] = $part_params;
                    } else {
                        $params = array_merge($part_params);
                    }
                }
            }
        }
        if (!$parts) {
            return [];
        }
        return count($parts) === 1 ? $parts[0] : '(' . implode(') AND (', $parts) . ')';
    }

    public function buildNotEqualCondition($column, $operands, &$params)
    {
        $params = $operands[0];
        return $this->buildRelationalCondition('!=', $column);
    }

    public function buildGreaterEqualCondition($column, $operands, &$params)
    {
        $params = $operands[0];
        return $this->buildRelationalCondition('>=', $column);
    }
    public function buildLessEqualCondition($column, $operands, &$params)
    {
        $params = $operands[0];
        return $this->buildRelationalCondition('<=', $column);
    }
    public function buildGreaterCondition($column, $operands, &$params)
    {
        $params = $operands[0];
        return $this->buildRelationalCondition('>', $column);
    }
    public function buildLessCondition($column, $operands, &$params)
    {
        $params = $operands[0];
        return $this->buildRelationalCondition('<', $column);
    }

    protected function buildRelationalCondition($relation, $column)
    {
        $column = $this->quoteMeta($column);
        return "{$column} {$relation} ?";
    }

    public function buildBetweenCondition($column, $operands, &$params)
    {
        $column = $this->quoteMeta($column);
        $params = array_values($operands);
        return "{$column} BETWEEN ? AND ?";
    }

    public function buildNotBetweenCondition($column, $operands, &$params)
    {
        $column = $this->quoteMeta($column);
        $params = array_values($operands);
        return "{$column} NOT BETWEEN ? AND ?";
    }

    public function buildInCondition($column, $operands, &$params)
    {
        return $this->buildIsOrNotInCondition(true, $column, $operands, $params);
    }

    public function buildNotInCondition($column, $operands, &$params)
    {
        return $this->buildIsOrNotInCondition(false, $column, $operands, $params);
    }

    protected function buildIsOrNotInCondition($in, $column, $operands, &$params)
    {
        $operand = $operands[0];
        if (empty($operand)) {
            return '';
        }
        $column = $this->quoteMeta($column);
        $params = array_values($operand);
        if (count($operand) === 1) {
            $operator = $in ? '=' : '!=';
            return "{$column} {$operator} ?";
        } else {
            $operator = $in ? 'IN' : 'NOT IN';
            return "{$column} {$operator} (" . implode(', ', array_fill(0, count($operand), '?')) . ')';
        }
    }

    public function buildLikeCondition($column, $operands, &$params)
    {
        $column = $this->quoteMeta($column);
        $params = $operands[0];
        return "{$column} LIKE ?";
    }

    public function buildNotLikeCondition($column, $operands, &$params)
    {
        $column = $this->quoteMeta($column);
        $params = $operands[0];
        return "{$column} NOT LIKE ?";
    }

}
