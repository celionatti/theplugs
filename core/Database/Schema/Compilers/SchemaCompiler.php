<?php

declare(strict_types=1);

namespace Plugs\Database\Schema\Compilers;

use Plugs\Database\Schema\Blueprint;
use Plugs\Database\Schema\ColumnDefinition;

abstract class SchemaCompiler
{
    protected array $typeMap = [];

    abstract public function compile(Blueprint $blueprint): string;
    abstract protected function compileColumn(ColumnDefinition $column): string;
    abstract protected function getColumnType(ColumnDefinition $column): string;

    protected function compileCreateTable(Blueprint $blueprint): string
    {
        $sql = "CREATE TABLE {$blueprint->getTable()} (\n";
        
        $columnDefinitions = [];
        foreach ($blueprint->getColumns() as $column) {
            $columnDefinitions[] = '    ' . $this->compileColumn($column);
        }
        
        $sql .= implode(",\n", $columnDefinitions);
        
        // Add primary key constraints
        foreach ($blueprint->getCommands() as $command) {
            if ($command['type'] === 'primary') {
                $columns = implode(', ', $command['columns']);
                $sql .= ",\n    PRIMARY KEY ({$columns})";
            }
        }
        
        $sql .= "\n)";
        
        return $sql;
    }

    protected function compileAlterTable(Blueprint $blueprint): string
    {
        $statements = [];
        
        // Add columns
        foreach ($blueprint->getColumns() as $column) {
            $statements[] = "ALTER TABLE {$blueprint->getTable()} ADD COLUMN " . $this->compileColumn($column);
        }
        
        // Process commands
        foreach ($blueprint->getCommands() as $command) {
            $statements[] = $this->compileCommand($blueprint, $command);
        }
        
        return implode(";\n", array_filter($statements));
    }

    protected function compileCommand(Blueprint $blueprint, array $command): string
    {
        $method = 'compile' . ucfirst($command['type']);
        
        if (method_exists($this, $method)) {
            return $this->$method($blueprint, $command);
        }
        
        return '';
    }

    protected function compileIndex(Blueprint $blueprint, array $command): string
    {
        $columns = implode(', ', $command['columns']);
        $name = $command['name'] ?? $this->generateIndexName($blueprint->getTable(), $command['columns'], 'index');
        
        return "CREATE INDEX {$name} ON {$blueprint->getTable()} ({$columns})";
    }

    protected function compileUnique(Blueprint $blueprint, array $command): string
    {
        $columns = implode(', ', $command['columns']);
        $name = $command['name'] ?? $this->generateIndexName($blueprint->getTable(), $command['columns'], 'unique');
        
        return "CREATE UNIQUE INDEX {$name} ON {$blueprint->getTable()} ({$columns})";
    }

    protected function compileForeign(Blueprint $blueprint, array $command): string
    {
        $foreign = $command['definition'];
        $localColumns = implode(', ', $foreign->getColumns());
        $referencedColumns = implode(', ', $foreign->getReferencedColumns());
        
        $sql = "ALTER TABLE {$blueprint->getTable()} ADD CONSTRAINT ";
        $sql .= $this->generateForeignKeyName($blueprint->getTable(), $foreign->getColumns());
        $sql .= " FOREIGN KEY ({$localColumns}) REFERENCES {$foreign->getReferencedTable()} ({$referencedColumns})";
        
        if ($foreign->getOnDelete()) {
            $sql .= " ON DELETE {$foreign->getOnDelete()}";
        }
        
        if ($foreign->getOnUpdate()) {
            $sql .= " ON UPDATE {$foreign->getOnUpdate()}";
        }
        
        return $sql;
    }

    protected function compileDropColumn(Blueprint $blueprint, array $command): string
    {
        return "ALTER TABLE {$blueprint->getTable()} DROP COLUMN {$command['column']}";
    }

    protected function compileDropIndex(Blueprint $blueprint, array $command): string
    {
        return "DROP INDEX {$command['name']}";
    }

    protected function compileDropUnique(Blueprint $blueprint, array $command): string
    {
        return "DROP INDEX {$command['name']}";
    }

    protected function compileDropForeign(Blueprint $blueprint, array $command): string
    {
        return "ALTER TABLE {$blueprint->getTable()} DROP CONSTRAINT {$command['name']}";
    }

    protected function generateIndexName(string $table, array $columns, string $type): string
    {
        return strtolower($table . '_' . implode('_', $columns) . '_' . $type);
    }

    protected function generateForeignKeyName(string $table, array $columns): string
    {
        return strtolower($table . '_' . implode('_', $columns) . '_foreign');
    }

    protected function formatDefault($value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        
        if (is_numeric($value)) {
            return (string) $value;
        }
        
        return "'" . addslashes($value) . "'";
    }
}