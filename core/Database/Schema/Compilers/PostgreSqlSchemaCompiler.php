<?php

declare(strict_types=1);

namespace Plugs\Database\Schema\Compilers;

use Plugs\Database\Schema\Blueprint;
use Plugs\Database\Schema\ColumnDefinition;
use Plugs\Database\Schema\Compilers\SchemaCompiler;

class PostgreSqlSchemaCompiler extends SchemaCompiler
{
    protected array $typeMap = [
        'bigInteger' => 'BIGINT',
        'integer' => 'INTEGER',
        'mediumInteger' => 'INTEGER',
        'smallInteger' => 'SMALLINT',
        'tinyInteger' => 'SMALLINT',
        'string' => 'VARCHAR',
        'char' => 'CHAR',
        'text' => 'TEXT',
        'mediumText' => 'TEXT',
        'longText' => 'TEXT',
        'decimal' => 'DECIMAL',
        'float' => 'REAL',
        'double' => 'DOUBLE PRECISION',
        'boolean' => 'BOOLEAN',
        'date' => 'DATE',
        'dateTime' => 'TIMESTAMP',
        'time' => 'TIME',
        'timestamp' => 'TIMESTAMP',
        'json' => 'JSONB',
    ];

    public function compile(Blueprint $blueprint): string
    {
        return match ($blueprint->getAction()) {
            'create' => $this->compileCreateTable($blueprint),
            'alter' => $this->compileAlterTable($blueprint),
            default => throw new \InvalidArgumentException("Unknown action: {$blueprint->getAction()}")
        };
    }

    protected function compileColumn(ColumnDefinition $column): string
    {
        $sql = $column->getName() . ' ';
        
        if ($column->getParameter('autoIncrement')) {
            $sql .= $column->getType() === 'bigInteger' ? 'BIGSERIAL' : 'SERIAL';
        } else {
            $sql .= $this->getColumnType($column);
        }
        
        if (!$column->getParameter('nullable', false)) {
            $sql .= ' NOT NULL';
        }
        
        if ($column->getParameter('default') !== null && !$column->getParameter('autoIncrement')) {
            $sql .= ' DEFAULT ' . $this->formatDefault($column->getParameter('default'));
        }
        
        return $sql;
    }

    protected function getColumnType(ColumnDefinition $column): string
    {
        $type = $this->typeMap[$column->getType()] ?? $column->getType();
        
        return match ($column->getType()) {
            'string', 'char' => $type . '(' . $column->getParameter('length', 255) . ')',
            'decimal' => $type . '(' . $column->getParameter('precision', 8) . ', ' . $column->getParameter('scale', 2) . ')',
            default => $type
        };
    }

    protected function formatDefault($value): string
    {
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        
        return parent::formatDefault($value);
    }
}