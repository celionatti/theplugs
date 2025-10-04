<?php

declare(strict_types=1);

namespace Plugs\Database\Eloquent;

use JsonSerializable;
use Plugs\Database\QueryBuilder;
use Plugs\Illusion\Carbon\Carbon;
use Plugs\Database\DatabaseConfig;
use Plugs\Database\Traits\ModelHelpers;
use Plugs\Database\Traits\HasRelationships;
use Plugs\Database\Eloquent\EloquentBuilder;
use Plugs\Database\Eloquent\Relations\HasOneRelation;
use Plugs\Exceptions\Database\ModelNotFoundException;
use Plugs\Database\Eloquent\Relations\HasManyRelation;
use Plugs\Database\Eloquent\Relations\BelongsToRelation;
use Plugs\Database\Eloquent\Relations\BelongsToManyRelation;

abstract class Model implements JsonSerializable
{
    use ModelHelpers;
    use HasRelationships;

    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $guarded = ['*'];
    protected array $hidden = [];
    protected array $casts = [];
    protected array $dates = ['created_at', 'updated_at'];
    protected bool $timestamps = true;
    protected ?string $deletedAt = null;
    // protected ?string $deletedAt = 'deleted_at';
    protected array $attributes = [];
    protected array $original = [];
    protected array $relations = [];
    protected bool $exists = false;
    protected bool $wasRecentlyCreated = false;

    // Event hooks
    protected static array $events = [
        'creating',
        'created',
        'updating',
        'updated',
        'saving',
        'saved',
        'deleting',
        'deleted',
        'restoring',
        'restored'
    ];

    protected static array $eventCallbacks = [];

    public function __construct(array $attributes = [])
    {
        $this->syncOriginal();
        $this->fill($attributes);
    }

    // Static factory methods
    public static function create(array $attributes = []): static
    {
        $model = new static();
        $model->fill($attributes);
        $model->save();
        return $model;
    }

    public static function find($id): ?static
    {
        return static::query()->where(static::make()->getKeyName(), '=', $id)->first();
    }

    public static function findOrFail($id): static
    {
        $model = static::find($id);
        if (!$model) {
            throw new ModelNotFoundException("Model not found with ID: {$id}");
        }
        return $model;
    }

    public static function where(string $column, $operator = null, $value = null): EloquentBuilder
    {
        return static::query()->where($column, $operator, $value);
    }

    public static function whereIn(string $column, array $values): EloquentBuilder
    {
        return static::query()->whereIn($column, $values);
    }

    public static function all(): array
    {
        return static::query()->get();
    }

    public static function query(): EloquentBuilder
    {
        $model = new static();
        $builder = new QueryBuilder(DatabaseConfig::getConnection());
        $builder->table($model->getTable());

        $eloquentBuilder = new EloquentBuilder($builder, $model);

        // Apply soft delete scope if the model uses soft deletes
        if ($model->usesSoftDeletes()) {
            $eloquentBuilder->whereNull($model->getDeletedAtColumn());
        }

        return $eloquentBuilder;
    }

    public static function make(array $attributes = []): static
    {
        return new static($attributes);
    }

    // Instance methods
    public function save(): bool
    {
        if ($this->fireEvent('saving') === false) {
            return false;
        }

        // Determine if this is an insert or update based on primary key existence
        $keyName = $this->getKeyName();
        $keyValue = $this->getAttribute($keyName);

        // If no primary key value or it's null/empty, it's an insert
        $saved = (!$keyValue || !$this->exists) ? $this->performInsert() : $this->performUpdate();

        if ($saved) {
            $this->fireEvent('saved');
            $this->syncOriginal();
        }

        return $saved;
    }

    public function update(array $attributes = []): bool
    {
        if (!$this->exists) {
            return false;
        }

        // Fill the new attributes if provided
        if (!empty($attributes)) {
            $this->fill($attributes);
        }

        // Only proceed if there are actual changes
        if (!$this->isDirty()) {
            return true; // Consider no changes as a successful "update"
        }

        return $this->save();
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        if ($this->fireEvent('deleting') === false) {
            return false;
        }

        if ($this->usesSoftDeletes()) {
            return $this->performSoftDelete();
        }

        return $this->performDelete();
    }

    public function forceDelete(): bool
    {
        if ($this->usesSoftDeletes()) {
            return $this->performDelete();
        }

        return $this->delete();
    }

    public function restore(): bool
    {
        if (!$this->usesSoftDeletes() || !$this->trashed()) {
            return false;
        }

        if ($this->fireEvent('restoring') === false) {
            return false;
        }

        $this->setAttribute($this->getDeletedAtColumn(), null);
        $result = $this->save();

        if ($result) {
            $this->fireEvent('restored');
        }

        return $result;
    }

    public function trashed(): bool
    {
        return $this->usesSoftDeletes() && !is_null($this->getAttribute($this->getDeletedAtColumn()));
    }

    // Attribute handling
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    public function setAttribute(string $key, $value): static
    {
        if ($this->hasSetMutator($key)) {
            $method = 'set' . str_replace('_', '', ucwords($key, '_')) . 'Attribute';
            $this->$method($value);
            return $this;
        }

        if (in_array($key, $this->dates) && $value !== null) {
            $value = $this->asDateTime($value);
        }

        $this->attributes[$key] = $value;

        return $this;
    }

    public function getAttribute(string $key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->getAttributeValue($key);
        }

        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }

        if (method_exists($this, $key)) {
            return $this->getRelationValue($key);
        }

        return null;
    }

    public function getAttributeValue(string $key)
    {
        $value = $this->attributes[$key] ?? null;

        if ($this->hasGetMutator($key)) {
            $method = 'get' . str_replace('_', '', ucwords($key, '_')) . 'Attribute';
            return $this->$method($value);
        }

        if (isset($this->casts[$key])) {
            return $this->castAttribute($key, $value);
        }

        if (in_array($key, $this->dates) && $value !== null) {
            return $this->asDateTime($value);
        }

        return $value;
    }

    public function getOriginal(?string $key = null)
    {
        return $key ? ($this->original[$key] ?? null) : $this->original;
    }

    public function isDirty(?string $key = null): bool
    {
        if ($key !== null) {
            // Check if this specific key is dirty
            if (!array_key_exists($key, $this->attributes)) {
                return false;
            }

            $current = $this->attributes[$key];
            $original = $this->original[$key] ?? null;

            // Convert both values to strings for comparison if they're different types
            // but handle null values specially
            if ($current === null && $original === null) {
                return false;
            }

            if ($current === null || $original === null) {
                return $current !== $original;
            }

            // For non-null values, do strict comparison first
            if ($current === $original) {
                return false;
            }

            // If strict comparison fails, try string comparison for different types
            return (string)$current !== (string)$original;
        }

        // Check if any attribute is dirty
        foreach ($this->attributes as $key => $value) {
            if ($this->isDirty($key)) {
                return true;
            }
        }

        return false;
    }

    public function getChanges(): array
    {
        $changes = [];
        foreach ($this->attributes as $key => $value) {
            if ($this->isDirty($key)) {
                $changes[$key] = $value;
            }
        }
        return $changes;
    }

    public function getDirty(): array
    {
        return $this->getChanges();
    }

    // Relationships
    public function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): HasOneRelation
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $localKey = $localKey ?: $this->getKeyName();

        return new HasOneRelation($related, $this, $foreignKey, $localKey);
    }

    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): HasManyRelation
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $localKey = $localKey ?: $this->getKeyName();

        return new HasManyRelation($related, $this, $foreignKey, $localKey);
    }

    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): BelongsToRelation
    {
        $foreignKey = $foreignKey ?: $this->guessBelongsToForeignKey($related);
        $ownerKey = $ownerKey ?: (new $related)->getKeyName();

        return new BelongsToRelation($related, $this, $foreignKey, $ownerKey);
    }

    public function belongsToMany(
        string $related,
        ?string $table = null,
        ?string $foreignPivotKey = null,
        ?string $relatedPivotKey = null
    ): BelongsToManyRelation {
        $table = $table ?: $this->joiningTable($related);
        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();
        $relatedPivotKey = $relatedPivotKey ?: (new $related)->getForeignKey();

        return new BelongsToManyRelation($related, $this, $table, $foreignPivotKey, $relatedPivotKey);
    }

    private function getRelationValue(string $key)
    {
        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }

        $relation = $this->$key();
        $this->relations[$key] = $relation->getResults();

        return $this->relations[$key];
    }

    // Casting
    protected function castAttribute(string $key, $value)
    {
        $castType = $this->casts[$key];

        if ($value === null) {
            return $value;
        }

        return match ($castType) {
            'int', 'integer' => (int) $value,
            'real', 'float', 'double' => (float) $value,
            'string' => (string) $value,
            'bool', 'boolean' => (bool) $value,
            'object' => json_decode($value, false),
            'array', 'json' => json_decode($value, true),
            'collection' => collect(json_decode($value, true)),
            'date' => $this->asDate($value),
            'datetime' => $this->asDateTime($value),
            'timestamp' => $this->asTimestamp($value),
            default => $value
        };
    }

    // Helper methods
    public function getTable(): string
    {
        return $this->table ?: strtolower(str_replace('\\', '', static::class)) . 's';
    }

    public function getKeyName(): string
    {
        return $this->primaryKey;
    }

    public function getKey()
    {
        return $this->getAttribute($this->getKeyName());
    }

    public function getForeignKey(): string
    {
        return str_replace('\\', '', strtolower(static::class)) . '_id';
    }

    protected function guessBelongsToForeignKey(string $related): string
    {
        $name = class_basename($related);
        return strtolower($name) . '_id';
    }

    protected function joiningTable(string $related): string
    {
        $models = [
            strtolower(class_basename(static::class)),
            strtolower(class_basename($related))
        ];

        sort($models);

        return implode('_', $models);
    }

    public function usesSoftDeletes(): bool
    {
        return $this->deletedAt !== null && $this->deletedAt !== '';
    }

    public function getDeletedAtColumn(): string
    {
        return $this->deletedAt;
    }

    protected function isFillable(string $key): bool
    {
        if (in_array($key, $this->fillable)) {
            return true;
        }

        if ($this->isGuarded($key)) {
            return false;
        }

        return empty($this->fillable) && !str_starts_with($key, '_');
    }

    protected function isGuarded(string $key): bool
    {
        return in_array($key, $this->guarded) || $this->guarded === ['*'];
    }

    protected function hasGetMutator(string $key): bool
    {
        $method = 'get' . str_replace('_', '', ucwords($key, '_')) . 'Attribute';
        return method_exists($this, $method);
    }

    protected function hasSetMutator(string $key): bool
    {
        $method = 'set' . str_replace('_', '', ucwords($key, '_')) . 'Attribute';
        return method_exists($this, $method);
    }

    protected function asDateTime($value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof \DateTime) {
            return Carbon::instance($value);
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp($value);
        }

        return Carbon::parse($value);
    }

    protected function asDate($value): Carbon
    {
        return $this->asDateTime($value)->startOfDay();
    }

    protected function asTimestamp($value): int
    {
        return $this->asDateTime($value)->getTimestamp();
    }

    // Database operations
    protected function performInsert(): bool
    {
        if ($this->fireEvent('creating') === false) {
            return false;
        }

        if ($this->timestamps) {
            $this->updateTimestamps();
        }

        $builder = new QueryBuilder(DatabaseConfig::getConnection());
        $id = $builder->table($this->getTable())->insertGetId($this->getInsertableAttributes());

        $this->setAttribute($this->getKeyName(), $id);
        $this->exists = true;
        $this->wasRecentlyCreated = true;

        $this->fireEvent('created');

        return true;
    }

    protected function performUpdate(): bool
    {
        if ($this->fireEvent('updating') === false) {
            return false;
        }

        $dirty = $this->getDirty();

        if (empty($dirty)) {
            $this->fireEvent('updated');
            return true;
        }

        if ($this->timestamps) {
            $this->updateTimestamps();
            $dirty = $this->getDirty(); // Refresh dirty attributes after updating timestamps
        }

        $builder = new QueryBuilder(DatabaseConfig::getConnection());
        $affected = $builder->table($this->getTable())
            ->where($this->getKeyName(), $this->getKey())
            ->update($dirty);

        if ($affected > 0) {
            $this->fireEvent('updated');
            return true;
        }

        return false;
    }

    protected function performSoftDelete(): bool
    {
        $this->setAttribute($this->getDeletedAtColumn(), $this->freshTimestamp());
        $result = $this->save();

        if ($result) {
            $this->fireEvent('deleted');
        }

        return $result;
    }

    protected function performDelete(): bool
    {
        $builder = new QueryBuilder(DatabaseConfig::getConnection());
        $affected = $builder->table($this->getTable())
            ->where($this->getKeyName(), $this->getKey())
            ->delete();

        if ($affected > 0) {
            $this->exists = false;
            $this->fireEvent('deleted');
        }

        return $affected > 0;
    }

    protected function getInsertableAttributes(): array
    {
        $attributes = [];

        foreach ($this->attributes as $key => $value) {
            // Skip internal model properties and primary key for auto-increment
            if (
                !in_array($key, ['exists', 'wasRecentlyCreated']) &&
                !($key === $this->getKeyName() && empty($value)) // Allow primary key if it has a value
            ) {
                $attributes[$key] = $value;
            }
        }

        return $attributes;
    }

    protected function updateTimestamps(): void
    {
        $time = $this->freshTimestamp();

        if (!$this->isDirty('updated_at')) {
            $this->setAttribute('updated_at', $time);
        }

        if (!$this->exists && !$this->isDirty('created_at')) {
            $this->setAttribute('created_at', $time);
        }
    }

    protected function freshTimestamp(): Carbon
    {
        return Carbon::now();
    }

    public function fresh(): ?static
    {
        if (!$this->exists) {
            return null;
        }

        return static::find($this->getKey());
    }

    protected function syncOriginal(): void
    {
        $this->original = $this->attributes;
    }

    public function syncOriginalAttribute(string $attribute): static
    {
        $this->original[$attribute] = $this->attributes[$attribute] ?? null;
        return $this;
    }

    public function syncOriginalAttributes(array $attributes): static
    {
        foreach ($attributes as $attribute) {
            $this->syncOriginalAttribute($attribute);
        }
        return $this;
    }

    // Debug methods
    public function debugAttributes(): array
    {
        return [
            'attributes' => $this->attributes,
            'original' => $this->original,
            'dirty' => $this->getDirty(),
            'exists' => $this->exists,
            'key' => $this->getKey()
        ];
    }

    // Event handling
    protected function fireEvent(string $event): bool
    {
        // Check for registered callbacks only - no direct method calls
        $callbacks = static::$eventCallbacks[static::class][$event] ?? [];
        foreach ($callbacks as $callback) {
            if ($callback($this) === false) {
                return false;
            }
        }

        return true;
    }

    public static function creating(callable $callback): void
    {
        static::registerEvent('creating', $callback);
    }

    public static function created(callable $callback): void
    {
        static::registerEvent('created', $callback);
    }

    public static function updating(callable $callback): void
    {
        static::registerEvent('updating', $callback);
    }

    public static function updated(callable $callback): void
    {
        static::registerEvent('updated', $callback);
    }

    public static function saving(callable $callback): void
    {
        static::registerEvent('saving', $callback);
    }

    public static function saved(callable $callback): void
    {
        static::registerEvent('saved', $callback);
    }

    public static function deleting(callable $callback): void
    {
        static::registerEvent('deleting', $callback);
    }

    public static function deleted(callable $callback): void
    {
        static::registerEvent('deleted', $callback);
    }

    public static function restoring(callable $callback): void
    {
        static::registerEvent('restoring', $callback);
    }

    public static function restored(callable $callback): void
    {
        static::registerEvent('restored', $callback);
    }

    protected static function registerEvent(string $event, callable $callback): void
    {
        static::$eventCallbacks[static::class][$event][] = $callback;
    }

    // Array/JSON conversion
    public function toArray(): array
    {
        $attributes = [];

        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->hidden)) {
                $attributes[$key] = $this->getAttributeValue($key);
            }
        }

        foreach ($this->relations as $key => $relation) {
            if (!in_array($key, $this->hidden)) {
                $attributes[$key] = is_array($relation) ?
                    array_map(fn($model) => $model instanceof Model ? $model->toArray() : $model, $relation) : ($relation instanceof Model ? $relation->toArray() : $relation);
            }
        }

        return $attributes;
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    // Additional methods needed by EloquentBuilder
    public function newInstance(array $attributes = []): static
    {
        return new static($attributes);
    }

    public function setRawAttributes(array $attributes, bool $sync = false): static
    {
        $this->attributes = $attributes;

        if ($sync) {
            $this->syncOriginal();
        }

        return $this;
    }

    public function setExisting(bool $exists = true): static
    {
        $this->exists = $exists;
        return $this;
    }

    // Magic methods
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function __isset(string $key): bool
    {
        return $this->getAttribute($key) !== null;
    }

    public function __unset(string $key): void
    {
        unset($this->attributes[$key], $this->relations[$key]);
    }

    public function __toString(): string
    {
        return $this->toJson();
    }
}
