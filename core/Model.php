<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Base Model
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * @description  Active Record style base model
 * @pattern      Active Record, Repository Pattern
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Core;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

abstract class Model
{
    /**
     * Table name
     */
    protected static string $table = '';
    
    /**
     * Primary key
     */
    protected static string $primaryKey = 'id';
    
    /**
     * Fillable fields (mass assignment protection)
     */
    protected static array $fillable = [];
    
    /**
     * Hidden fields (excluded from JSON)
     */
    protected static array $hidden = [];
    
    /**
     * Use soft deletes
     */
    protected static bool $softDeletes = false;
    
    /**
     * Model attributes
     */
    protected array $attributes = [];
    
    /**
     * Original attributes (for dirty checking)
     */
    protected array $original = [];
    
    /**
     * Constructor
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
        $this->original = $this->attributes;
    }
    
    /**
     * Get database connection
     */
    protected static function db(): Database
    {
        return Application::getInstance()->db();
    }
    
    /**
     * Fill model with attributes
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if (empty(static::$fillable) || in_array($key, static::$fillable)) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }
    
    /**
     * Magic getter
     */
    public function __get(string $name)
    {
        return $this->attributes[$name] ?? null;
    }
    
    /**
     * Magic setter
     */
    public function __set(string $name, $value): void
    {
        $this->attributes[$name] = $value;
    }
    
    /**
     * Magic isset
     */
    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }
    
    /**
     * Find by primary key
     */
    public static function find($id): ?static
    {
        $sql = sprintf(
            "SELECT * FROM `%s` WHERE `%s` = ?",
            static::$table,
            static::$primaryKey
        );
        
        if (static::$softDeletes) {
            $sql .= " AND deleted_at IS NULL";
        }
        
        $row = static::db()->selectOne($sql, [$id]);
        
        return $row ? new static($row) : null;
    }
    
    /**
     * Find by UUID
     */
    public static function findByUuid(string $uuid): ?static
    {
        $sql = sprintf("SELECT * FROM `%s` WHERE uuid = ?", static::$table);
        
        if (static::$softDeletes) {
            $sql .= " AND deleted_at IS NULL";
        }
        
        $row = static::db()->selectOne($sql, [$uuid]);
        
        return $row ? new static($row) : null;
    }
    
    /**
     * Find or fail
     */
    public static function findOrFail($id): static
    {
        $model = static::find($id);
        
        if (!$model) {
            throw new \RuntimeException(static::class . " not found with ID: {$id}");
        }
        
        return $model;
    }
    
    /**
     * Find by column
     */
    public static function findBy(string $column, $value): ?static
    {
        $sql = sprintf("SELECT * FROM `%s` WHERE `%s` = ?", static::$table, $column);
        
        if (static::$softDeletes) {
            $sql .= " AND deleted_at IS NULL";
        }
        
        $sql .= " LIMIT 1";
        
        $row = static::db()->selectOne($sql, [$value]);
        
        return $row ? new static($row) : null;
    }
    
    /**
     * Get all records
     */
    public static function all(array $columns = ['*']): array
    {
        $columnStr = $columns === ['*'] ? '*' : '`' . implode('`, `', $columns) . '`';
        $sql = sprintf("SELECT %s FROM `%s`", $columnStr, static::$table);
        
        if (static::$softDeletes) {
            $sql .= " WHERE deleted_at IS NULL";
        }
        
        $rows = static::db()->select($sql);
        
        return array_map(fn($row) => new static($row), $rows);
    }
    
    /**
     * Where query builder
     */
    public static function where(string $column, $operator, $value = null): QueryBuilder
    {
        return (new QueryBuilder(static::class))->where($column, $operator, $value);
    }
    
    /**
     * Create new record
     */
    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        
        // Generate UUID if not provided
        if (!isset($model->uuid)) {
            $model->uuid = static::db()->uuid();
        }
        
        $model->save();
        
        return $model;
    }
    
    /**
     * Save model (insert or update)
     */
    public function save(): bool
    {
        // Check if exists
        $pk = static::$primaryKey;
        
        if (isset($this->attributes[$pk]) && $this->attributes[$pk]) {
            return $this->update();
        }
        
        return $this->insert();
    }
    
    /**
     * Insert new record
     */
    protected function insert(): bool
    {
        $data = $this->attributes;
        unset($data[static::$primaryKey]);
        
        $id = static::db()->insert(static::$table, $data);
        $this->attributes[static::$primaryKey] = $id;
        $this->original = $this->attributes;
        
        return true;
    }
    
    /**
     * Update existing record
     */
    protected function update(): bool
    {
        $pk = static::$primaryKey;
        $id = $this->attributes[$pk];
        
        $data = $this->getDirty();
        
        if (empty($data)) {
            return true; // Nothing to update
        }
        
        static::db()->update(static::$table, $data, [$pk => $id]);
        $this->original = $this->attributes;
        
        return true;
    }
    
    /**
     * Get dirty (changed) attributes
     */
    public function getDirty(): array
    {
        $dirty = [];
        
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }
        
        return $dirty;
    }
    
    /**
     * Delete record
     */
    public function delete(): bool
    {
        $pk = static::$primaryKey;
        
        if (static::$softDeletes) {
            $this->attributes['deleted_at'] = date('Y-m-d H:i:s');
            return $this->save();
        }
        
        static::db()->delete(static::$table, [$pk => $this->attributes[$pk]]);
        
        return true;
    }
    
    /**
     * Force delete (even with soft deletes)
     */
    public function forceDelete(): bool
    {
        $pk = static::$primaryKey;
        static::db()->delete(static::$table, [$pk => $this->attributes[$pk]]);
        return true;
    }
    
    /**
     * Restore soft deleted record
     */
    public function restore(): bool
    {
        if (!static::$softDeletes) {
            return false;
        }
        
        $this->attributes['deleted_at'] = null;
        return $this->save();
    }
    
    /**
     * Refresh from database
     */
    public function refresh(): self
    {
        $pk = static::$primaryKey;
        $id = $this->attributes[$pk];
        
        $row = static::db()->selectOne(
            sprintf("SELECT * FROM `%s` WHERE `%s` = ?", static::$table, $pk),
            [$id]
        );
        
        if ($row) {
            $this->attributes = $row;
            $this->original = $row;
        }
        
        return $this;
    }
    
    /**
     * Convert to array
     */
    public function toArray(): array
    {
        $array = $this->attributes;
        
        // Remove hidden fields
        foreach (static::$hidden as $key) {
            unset($array[$key]);
        }
        
        return $array;
    }
    
    /**
     * Convert to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Check if record exists
     */
    public function exists(): bool
    {
        $pk = static::$primaryKey;
        return isset($this->attributes[$pk]) && $this->attributes[$pk] > 0;
    }
    
    /**
     * Get primary key value
     */
    public function getId()
    {
        return $this->attributes[static::$primaryKey] ?? null;
    }
    
    /**
     * Get all attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}

/**
 * Simple Query Builder
 */
class QueryBuilder
{
    private string $modelClass;
    private array $wheres = [];
    private array $bindings = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;
    
    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
    }
    
    public function where(string $column, $operator, $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->wheres[] = "`{$column}` {$operator} ?";
        $this->bindings[] = $value;
        
        return $this;
    }
    
    public function whereIn(string $column, array $values): self
    {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = "`{$column}` IN ({$placeholders})";
        $this->bindings = array_merge($this->bindings, $values);
        
        return $this;
    }
    
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = "`{$column}` {$direction}";
        return $this;
    }
    
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }
    
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }
    
    public function get(): array
    {
        $class = $this->modelClass;
        $table = $class::$table ?? '';
        
        $sql = "SELECT * FROM `{$table}`";
        
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }
        
        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        }
        
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }
        
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }
        
        $rows = Application::getInstance()->db()->select($sql, $this->bindings);
        
        return array_map(fn($row) => new $class($row), $rows);
    }
    
    public function first(): ?object
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }
    
    public function count(): int
    {
        $class = $this->modelClass;
        $table = $class::$table ?? '';
        
        $sql = "SELECT COUNT(*) as count FROM `{$table}`";
        
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }
        
        $result = Application::getInstance()->db()->selectOne($sql, $this->bindings);
        
        return (int) ($result['count'] ?? 0);
    }
    
    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $total = $this->count();
        
        $this->limit($perPage);
        $this->offset(($page - 1) * $perPage);
        
        return [
            'data'  => $this->get(),
            'total' => $total,
            'page'  => $page,
            'per_page' => $perPage
        ];
    }
}
