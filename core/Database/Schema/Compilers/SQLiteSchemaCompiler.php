<?php

declare(strict_types=1);

namespace Plugs\Database\Schema\Compilers;

use Plugs\Database\Schema\Blueprint;
use Plugs\Database\Schema\ColumnDefinition;
use Plugs\Database\Schema\Compilers\SchemaCompiler;

class SQLiteSchemaCompiler extends SchemaCompiler
{
    protected array $typeMap = [
        'bigInteger' => 'INTEGER',
        'integer' => 'INTEGER',
        'mediumInteger' => 'INTEGER',
        'smallInteger' => 'INTEGER',
        'tinyInteger' => 'INTEGER',
        'string' => 'TEXT',
        'char' => 'TEXT',
        'text' => 'TEXT',
        'mediumText' => 'TEXT',
        'longText' => 'TEXT',
        'decimal' => 'REAL',
        'float' => 'REAL',
        'double' => 'REAL',
        'boolean' => 'INTEGER',
        'date' => 'DATE',
        'dateTime' => 'DATETIME',
        'time' => 'TIME',
        'timestamp' => 'DATETIME',
        'json' => 'TEXT',
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
        
        if ($column->getParameter('primary')) {
            $sql .= ' PRIMARY KEY';
        }
        
        if ($column->getParameter('autoIncrement')) {
            $sql .= ' AUTOINCREMENT';
        }
        
        if (!$column->getParameter('nullable', false)) {
            $sql .= ' NOT NULL';
        }
        
        if ($column->getParameter('default') !== null) {
            $sql .= ' DEFAULT ' . $this->formatDefault($column->getParameter('default'));
        }
        
        return $sql;
    }

    protected function getColumnType(ColumnDefinition $column): string
    {
        return $this->typeMap[$column->getType()] ?? $column->getType();
    }

    protected function compileDropColumn(Blueprint $blueprint, array $command): string
    {
        // SQLite doesn't support DROP COLUMN directly
        // This would require recreating the table
        throw new \RuntimeException('SQLite does not support dropping columns. You need to recreate the table.');
    }
}