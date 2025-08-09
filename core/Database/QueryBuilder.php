<?php

declare(strict_types=1);

namespace Plugs\Database;

use PDO;
use RuntimeException;
use InvalidArgumentException;

class QueryBuilder
{
    public PDO $pdo; // Made public for EloquentBuilder access
    private string $table = '';
    private array $selects = ['*'];
    private array $joins = [];
    private array $wheres = [];
    private array $groupBy = [];
    private array $orderBy = [];
    private array $bindings = [];
    private ?int $limitValue = null;
    private ?int $offsetValue = null;

    // SQL reserved words that need to be escaped
    private array $reservedWords = [
        'exists', 'order', 'group', 'where', 'select', 'from', 'join', 'update', 
        'delete', 'insert', 'into', 'values', 'table', 'column', 'index', 'key',
        'primary', 'foreign', 'references', 'constraint', 'default', 'null',
        'not', 'and', 'or', 'in', 'like', 'between', 'case', 'when', 'then',
        'else', 'end', 'if', 'count', 'sum', 'avg', 'min', 'max', 'distinct',
        'all', 'any', 'some', 'union', 'intersect', 'except', 'having'
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function escapeIdentifier(string $identifier): string
    {
        // Remove any existing backticks
        $identifier = trim($identifier, '`');
        
        // Check if it's a reserved word or contains special characters
        if (in_array(strtolower($identifier), $this->reservedWords) || 
            preg_match('/[^a-zA-Z0-9_.]/', $identifier)) {
            return "`{$identifier}`";
        }
        
        return $identifier;
    }

    public function table(string $table): self
    {
        $this->table = $this->escapeIdentifier($table);
        return $this;
    }

    public function select(array|string $columns = ['*']): self
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        
        // Escape column names except for * and aggregate functions
        $this->selects = array_map(function($column) {
            if ($column === '*' || preg_match('/\w+\s*\(\s*.*\s*\)/', $column)) {
                return $column; // Don't escape * or function calls
            }
            return $this->escapeIdentifier($column);
        }, $columns);
        
        return $this;
    }

    public function where(string $column, $operator = null, $value = null, string $boolean = 'and'): self
    {
        // If only 2 arguments are passed, assume operator is '='
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        // Validate operator
        $validOperators = ['=', '<', '>', '<=', '>=', '<>', '!=', 'like', 'not like', 'in', 'not in', 'between', 'not between'];
        if (!in_array(strtolower($operator), $validOperators)) {
            throw new InvalidArgumentException("Illegal operator: {$operator}");
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $this->escapeIdentifier($column),
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean
        ];

        $this->bindings[] = $value;

        return $this;
    }

    public function orWhere(string $column, $operator = null, $value = null): self
    {
        return $this->where($column, $operator, $value, 'or');
    }

    public function whereIn(string $column, array $values, string $boolean = 'and'): self
    {
        if (empty($values)) {
            throw new InvalidArgumentException('whereIn values cannot be empty');
        }

        $this->wheres[] = [
            'type' => 'in',
            'column' => $this->escapeIdentifier($column),
            'values' => $values,
            'boolean' => $boolean
        ];

        $this->bindings = array_merge($this->bindings, $values);

        return $this;
    }

    public function whereNotIn(string $column, array $values, string $boolean = 'and'): self
    {
        if (empty($values)) {
            throw new InvalidArgumentException('whereNotIn values cannot be empty');
        }

        $this->wheres[] = [
            'type' => 'not_in',
            'column' => $this->escapeIdentifier($column),
            'values' => $values,
            'boolean' => $boolean
        ];

        $this->bindings = array_merge($this->bindings, $values);

        return $this;
    }

    public function whereNull(string $column, string $boolean = 'and'): self
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $this->escapeIdentifier($column),
            'boolean' => $boolean
        ];

        return $this;
    }

    public function whereNotNull(string $column, string $boolean = 'and'): self
    {
        $this->wheres[] = [
            'type' => 'not_null',
            'column' => $this->escapeIdentifier($column),
            'boolean' => $boolean
        ];

        return $this;
    }

    public function join(string $table, string $first, ?string $operator = null, ?string $second = null, string $type = 'inner'): self
    {
        $this->joins[] = [
            'type' => $type,
            'table' => $this->escapeIdentifier($table),
            'first' => $this->escapeIdentifier($first),
            'operator' => $operator ?: '=',
            'second' => $this->escapeIdentifier($second ?: '')
        ];

        return $this;
    }

    public function leftJoin(string $table, string $first, ?string $operator = null, ?string $second = null): self
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    public function rightJoin(string $table, string $first, ?string $operator = null, ?string $second = null): self
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $direction = strtolower($direction);
        if (!in_array($direction, ['asc', 'desc'])) {
            throw new InvalidArgumentException("Order direction must be 'asc' or 'desc'");
        }

        $this->orderBy[] = [
            'column' => $this->escapeIdentifier($column), 
            'direction' => $direction
        ];
        return $this;
    }

    public function groupBy(string|array $groups): self
    {
        $groups = is_array($groups) ? $groups : func_get_args();
        $this->groupBy = array_merge($this->groupBy, array_map([$this, 'escapeIdentifier'], $groups));
        return $this;
    }

    public function limit(int $limit): self
    {
        if ($limit < 0) {
            throw new InvalidArgumentException('Limit must be non-negative');
        }
        $this->limitValue = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        if ($offset < 0) {
            throw new InvalidArgumentException('Offset must be non-negative');
        }
        $this->offsetValue = $offset;
        return $this;
    }

    public function get(): array
    {
        $sql = $this->buildSelectQuery();
        $stmt = $this->pdo->prepare($sql);
        
        if (!$stmt) {
            throw new RuntimeException('Failed to prepare SQL statement: ' . implode(', ', $this->pdo->errorInfo()));
        }
        
        $success = $stmt->execute($this->bindings);
        
        if (!$success) {
            throw new RuntimeException('Failed to execute SQL statement: ' . implode(', ', $stmt->errorInfo()));
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function first(): ?array
    {
        $originalLimit = $this->limitValue;
        $this->limit(1);
        
        try {
            $results = $this->get();
            return $results[0] ?? null;
        } finally {
            $this->limitValue = $originalLimit;
        }
    }

    public function count(string $column = '*'): int
    {
        $sql = $this->buildCountQuery($column);
        $stmt = $this->pdo->prepare($sql);
        
        if (!$stmt) {
            throw new RuntimeException('Failed to prepare SQL statement: ' . implode(', ', $this->pdo->errorInfo()));
        }
        
        $success = $stmt->execute($this->bindings);
        
        if (!$success) {
            throw new RuntimeException('Failed to execute SQL statement: ' . implode(', ', $stmt->errorInfo()));
        }
        
        return (int) $stmt->fetchColumn();
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function insert(array $data): bool
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Insert data cannot be empty');
        }

        $columns = array_map([$this, 'escapeIdentifier'], array_keys($data));
        $placeholders = array_fill(0, count($data), '?');

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->pdo->prepare($sql);
        
        if (!$stmt) {
            throw new RuntimeException('Failed to prepare insert statement: ' . implode(', ', $this->pdo->errorInfo()));
        }
        
        return $stmt->execute(array_values($data));
    }

    public function insertGetId(array $data): int|string
    {
        $success = $this->insert($data);
        
        if (!$success) {
            throw new RuntimeException('Insert operation failed');
        }
        
        return $this->pdo->lastInsertId();
    }

    public function update(array $data): int
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Update data cannot be empty');
        }

        $sets = [];
        $bindings = [];

        foreach ($data as $column => $value) {
            $sets[] = $this->escapeIdentifier($column) . " = ?";
            $bindings[] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);
        $sql .= $this->buildWhereClause();

        $stmt = $this->pdo->prepare($sql);
        
        if (!$stmt) {
            throw new RuntimeException('Failed to prepare update statement: ' . implode(', ', $this->pdo->errorInfo()));
        }
        
        $allBindings = array_merge($bindings, $this->bindings);
        $success = $stmt->execute($allBindings);
        
        if (!$success) {
            throw new RuntimeException('Update operation failed: ' . implode(', ', $stmt->errorInfo()));
        }

        return $stmt->rowCount();
    }

    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";
        $sql .= $this->buildWhereClause();

        $stmt = $this->pdo->prepare($sql);
        
        if (!$stmt) {
            throw new RuntimeException('Failed to prepare delete statement: ' . implode(', ', $this->pdo->errorInfo()));
        }
        
        $success = $stmt->execute($this->bindings);
        
        if (!$success) {
            throw new RuntimeException('Delete operation failed: ' . implode(', ', $stmt->errorInfo()));
        }

        return $stmt->rowCount();
    }

    private function buildSelectQuery(): string
    {
        if (empty($this->table)) {
            throw new RuntimeException('No table specified for query');
        }

        $sql = "SELECT " . implode(', ', $this->selects) . " FROM {$this->table}";

        // Add JOINs
        foreach ($this->joins as $join) {
            $sql .= " " . strtoupper($join['type']) . " JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }

        // Add WHERE clause
        $sql .= $this->buildWhereClause();

        // Add GROUP BY
        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY " . implode(', ', $this->groupBy);
        }

        // Add ORDER BY
        if (!empty($this->orderBy)) {
            $orderClauses = array_map(fn($order) => "{$order['column']} {$order['direction']}", $this->orderBy);
            $sql .= " ORDER BY " . implode(', ', $orderClauses);
        }

        // Add LIMIT and OFFSET
        if ($this->limitValue !== null) {
            $sql .= " LIMIT {$this->limitValue}";
        }

        if ($this->offsetValue !== null) {
            $sql .= " OFFSET {$this->offsetValue}";
        }

        return $sql;
    }

    private function buildCountQuery(string $column): string
    {
        if (empty($this->table)) {
            throw new RuntimeException('No table specified for query');
        }

        $column = $column === '*' ? $column : $this->escapeIdentifier($column);
        $sql = "SELECT COUNT({$column}) FROM {$this->table}";

        // Add JOINs
        foreach ($this->joins as $join) {
            $sql .= " " . strtoupper($join['type']) . " JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }

        // Add WHERE clause
        $sql .= $this->buildWhereClause();

        // Add GROUP BY
        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY " . implode(', ', $this->groupBy);
        }

        return $sql;
    }

    private function buildWhereClause(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $conditions = [];

        foreach ($this->wheres as $where) {
            $condition = match ($where['type']) {
                'basic' => "{$where['column']} {$where['operator']} ?",
                'in' => "{$where['column']} IN (" . implode(', ', array_fill(0, count($where['values']), '?')) . ")",
                'not_in' => "{$where['column']} NOT IN (" . implode(', ', array_fill(0, count($where['values']), '?')) . ")",
                'null' => "{$where['column']} IS NULL",
                'not_null' => "{$where['column']} IS NOT NULL",
                default => throw new RuntimeException("Unknown where type: {$where['type']}")
            };

            if (empty($conditions)) {
                $conditions[] = $condition;
            } else {
                $conditions[] = strtoupper($where['boolean']) . " " . $condition;
            }
        }

        return " WHERE " . implode(' ', $conditions);
    }

    public function toSql(): string
    {
        return $this->buildSelectQuery();
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    public function clone(): self
    {
        return clone $this;
    }

    // Reset query state
    public function reset(): self
    {
        $this->table = '';
        $this->selects = ['*'];
        $this->joins = [];
        $this->wheres = [];
        $this->groupBy = [];
        $this->orderBy = [];
        $this->bindings = [];
        $this->limitValue = null;
        $this->offsetValue = null;
        
        return $this;
    }
}