<?php

declare(strict_types=1);

namespace Plugs\Database;

use PDO;
use RuntimeException;
use InvalidArgumentException;

class QueryBuilder
{
    private PDO $pdo;
    private string $table = '';
    private array $selects = ['*'];
    private array $joins = [];
    private array $wheres = [];
    private array $groupBy = [];
    private array $orderBy = [];
    private array $bindings = [];
    private ?int $limitValue = null;
    private ?int $offsetValue = null;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function select(array|string $columns = ['*']): self
    {
        $this->selects = is_array($columns) ? $columns : func_get_args();
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
        if (!in_array(strtolower($operator), ['=', '<', '>', '<=', '>=', '<>', '!=', 'like', 'not like', 'in', 'not in', 'between', 'not between'])) {
            throw new InvalidArgumentException('Illegal operator');
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
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
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean
        ];

        $this->bindings = array_merge($this->bindings, $values);

        return $this;
    }

    public function whereNotIn(string $column, array $values, string $boolean = 'and'): self
    {
        $this->wheres[] = [
            'type' => 'not_in',
            'column' => $column,
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
            'column' => $column,
            'boolean' => $boolean
        ];

        return $this;
    }

    public function whereNotNull(string $column, string $boolean = 'and'): self
    {
        $this->wheres[] = [
            'type' => 'not_null',
            'column' => $column,
            'boolean' => $boolean
        ];

        return $this;
    }

    public function join(string $table, string $first, ?string $operator = null, ?string $second = null, string $type = 'inner'): self
    {
        $this->joins[] = [
            'type' => $type,
            'table' => $table,
            'first' => $first,
            'operator' => $operator ?: '=',
            'second' => $second
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
        $this->orderBy[] = ['column' => $column, 'direction' => strtolower($direction)];
        return $this;
    }

    public function groupBy(string|array $groups): self
    {
        $this->groupBy = is_array($groups) ? $groups : func_get_args();
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limitValue = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offsetValue = $offset;
        return $this;
    }

    public function get(): array
    {
        $sql = $this->buildSelectQuery();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->fetchAll();
    }

    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    public function count(string $column = '*'): int
    {
        $sql = $this->buildCountQuery($column);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);
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

        $columns = array_keys($data);
        $placeholders = array_fill(0, count($data), '?');

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(array_values($data));
    }

    public function insertGetId(array $data): int|string
    {
        $this->insert($data);
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
            $sets[] = "{$column} = ?";
            $bindings[] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);
        $sql .= $this->buildWhereClause();

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge($bindings, $this->bindings));

        return $stmt->rowCount();
    }

    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";
        $sql .= $this->buildWhereClause();

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);

        return $stmt->rowCount();
    }

    private function buildSelectQuery(): string
    {
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
        $bindCount = 0;

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
}
