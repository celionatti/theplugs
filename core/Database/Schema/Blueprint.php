<?php

declare(strict_types=1);

namespace Plugs\Database\Schema;

use PDO;
use Plugs\Database\Schema\ColumnDefinition;
use Plugs\Database\Schema\ForeignKeyDefinition;
use Plugs\Database\Schema\Compilers\MySqlSchemaCompiler;
use Plugs\Database\Schema\Compilers\SQLiteSchemaCompiler;
use Plugs\Database\Schema\Compilers\PostgreSqlSchemaCompiler;

class Blueprint
{
    private string $table;
    private string $action;
    private array $commands = [];
    private array $columns = [];

    public function __construct(string $table, string $action = 'create')
    {
        $this->table = $table;
        $this->action = $action;
    }

    // Primary key methods
    public function id(string $column = 'id'): ColumnDefinition
    {
        return $this->bigIncrements($column);
    }

    public function increments(string $column): ColumnDefinition
    {
        return $this->addColumn('integer', $column, ['autoIncrement' => true, 'primary' => true]);
    }

    public function bigIncrements(string $column): ColumnDefinition
    {
        return $this->addColumn('bigInteger', $column, ['autoIncrement' => true, 'primary' => true]);
    }

    // String columns
    public function string(string $column, int $length = 255): ColumnDefinition
    {
        return $this->addColumn('string', $column, ['length' => $length]);
    }

    public function char(string $column, int $length = 255): ColumnDefinition
    {
        return $this->addColumn('char', $column, ['length' => $length]);
    }

    public function text(string $column): ColumnDefinition
    {
        return $this->addColumn('text', $column);
    }

    public function mediumText(string $column): ColumnDefinition
    {
        return $this->addColumn('mediumText', $column);
    }

    public function longText(string $column): ColumnDefinition
    {
        return $this->addColumn('longText', $column);
    }

    // Numeric columns
    public function integer(string $column): ColumnDefinition
    {
        return $this->addColumn('integer', $column);
    }

    public function bigInteger(string $column): ColumnDefinition
    {
        return $this->addColumn('bigInteger', $column);
    }

    public function tinyInteger(string $column): ColumnDefinition
    {
        return $this->addColumn('tinyInteger', $column);
    }

    public function smallInteger(string $column): ColumnDefinition
    {
        return $this->addColumn('smallInteger', $column);
    }

    public function mediumInteger(string $column): ColumnDefinition
    {
        return $this->addColumn('mediumInteger', $column);
    }

    public function decimal(string $column, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        return $this->addColumn('decimal', $column, ['precision' => $precision, 'scale' => $scale]);
    }

    public function float(string $column): ColumnDefinition
    {
        return $this->addColumn('float', $column);
    }

    public function double(string $column): ColumnDefinition
    {
        return $this->addColumn('double', $column);
    }

    // Date/Time columns
    public function date(string $column): ColumnDefinition
    {
        return $this->addColumn('date', $column);
    }

    public function dateTime(string $column): ColumnDefinition
    {
        return $this->addColumn('dateTime', $column);
    }

    public function time(string $column): ColumnDefinition
    {
        return $this->addColumn('time', $column);
    }

    public function timestamp(string $column): ColumnDefinition
    {
        return $this->addColumn('timestamp', $column);
    }

    public function timestamps(): void
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
    }

    public function softDeletes(): void
    {
        $this->timestamp('deleted_at')->nullable();
    }

    // Boolean columns
    public function boolean(string $column): ColumnDefinition
    {
        return $this->addColumn('boolean', $column);
    }

    // JSON columns
    public function json(string $column): ColumnDefinition
    {
        return $this->addColumn('json', $column);
    }

    // Foreign key methods
    public function foreignId(string $column): ColumnDefinition
    {
        return $this->bigInteger($column);
    }

    public function foreign(string|array $columns): ForeignKeyDefinition
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $foreign = new ForeignKeyDefinition($columns);
        $this->commands[] = ['type' => 'foreign', 'definition' => $foreign];
        return $foreign;
    }

    // Index methods
    public function primary(string|array $columns): void
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $this->commands[] = ['type' => 'primary', 'columns' => $columns];
    }

    public function unique(string|array $columns, ?string $name = null): void
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $this->commands[] = ['type' => 'unique', 'columns' => $columns, 'name' => $name];
    }

    public function index(string|array $columns, ?string $name = null): void
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $this->commands[] = ['type' => 'index', 'columns' => $columns, 'name' => $name];
    }

    // Drop methods
    public function dropColumn(string|array $columns): void
    {
        $columns = is_array($columns) ? $columns : [$columns];
        foreach ($columns as $column) {
            $this->commands[] = ['type' => 'dropColumn', 'column' => $column];
        }
    }

    public function dropPrimary(): void
    {
        $this->commands[] = ['type' => 'dropPrimary'];
    }

    public function dropUnique(string $name): void
    {
        $this->commands[] = ['type' => 'dropUnique', 'name' => $name];
    }

    public function dropIndex(string $name): void
    {
        $this->commands[] = ['type' => 'dropIndex', 'name' => $name];
    }

    public function dropForeign(string $name): void
    {
        $this->commands[] = ['type' => 'dropForeign', 'name' => $name];
    }

    private function addColumn(string $type, string $name, array $parameters = []): ColumnDefinition
    {
        $column = new ColumnDefinition($type, $name, $parameters);
        $this->columns[] = $column;
        return $column;
    }

    public function toSql(PDO $connection): string
    {
        $driver = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        $compiler = match ($driver) {
            'mysql' => new MySqlSchemaCompiler(),
            'sqlite' => new SQLiteSchemaCompiler(),
            'pgsql' => new PostgreSqlSchemaCompiler(),
            default => throw new \RuntimeException("Unsupported driver: {$driver}")
        };

        return $compiler->compile($this);
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getCommands(): array
    {
        return $this->commands;
    }
}