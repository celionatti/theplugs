<?php

declare(strict_types=1);

namespace Plugs\Database\Schema\Compilers;

use Plugs\Database\Schema\Blueprint;
use Plugs\Database\Schema\ColumnDefinition;
use Plugs\Database\Schema\Compilers\SchemaCompiler;

class MySqlSchemaCompiler extends SchemaCompiler
{
    protected array $typeMap = [
        'bigInteger' => 'BIGINT',
        'integer' => 'INT',
        'mediumInteger' => 'MEDIUMINT',
        'smallInteger' => 'SMALLINT',
        'tinyInteger' => 'TINYINT',
        'string' => 'VARCHAR',
        'char' => 'CHAR',
        'text' => 'TEXT',
        'mediumText' => 'MEDIUMTEXT',
        'longText' => 'LONGTEXT',
        'decimal' => 'DECIMAL',
        'float' => 'FLOAT',
        'double' => 'DOUBLE',
        'boolean' => 'TINYINT(1)',
        'date' => 'DATE',
        'dateTime' => 'DATETIME',
        'time' => 'TIME',
        'timestamp' => 'TIMESTAMP',
        'json' => 'JSON',
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
        $sql = $column->getName() . ' ' . $this->getColumnType($column);
        
        if ($column->getParameter('unsigned')) {
            $sql .= ' UNSIGNED';
        }
        
        if (!$column->getParameter('nullable', false)) {
            $sql .= ' NOT NULL';
        }
        
        if ($column->getParameter('autoIncrement')) {
            $sql .= ' AUTO_INCREMENT';
        }
        
        if ($column->getParameter('default') !== null) {
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

    protected function compileCreateTable(Blueprint $blueprint): string
    {
        $sql = parent::compileCreateTable($blueprint);
        $sql .= ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        return $sql;
    }
}