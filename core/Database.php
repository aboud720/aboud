<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Database Connection Manager
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * @description  Secure PDO wrapper with prepared statements only
 * @security     
 *   - Prepared statements prevent SQL injection
 *   - Emulated prepares disabled for true protection
 *   - Connection pooling ready
 *   - Query logging for debugging
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Core;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

class Database
{
    /**
     * Singleton instance
     */
    private static ?Database $instance = null;
    
    /**
     * PDO connection
     */
    private \PDO $pdo;
    
    /**
     * Configuration
     */
    private array $config;
    
    /**
     * Query log for debugging
     */
    private array $queryLog = [];
    
    /**
     * Transaction nesting level
     */
    private int $transactionLevel = 0;
    
    /**
     * Private constructor
     */
    private function __construct(array $config)
    {
        $this->config = $config;
        $this->connect();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance(array $config = []): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }
    
    /**
     * Establish database connection
     */
    private function connect(): void
    {
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $this->config['driver'],
            $this->config['host'],
            $this->config['port'],
            $this->config['database'],
            $this->config['charset']
        );
        
        try {
            $this->pdo = new \PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
        } catch (\PDOException $e) {
            // Log error without exposing credentials
            error_log("Database connection failed: " . $e->getMessage());
            throw new \RuntimeException('Database connection failed');
        }
    }
    
    /**
     * Get PDO instance
     */
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }
    
    /**
     * Execute a query with prepared statement
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @return \PDOStatement
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $startTime = microtime(true);
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            // Log query in debug mode
            if (APP_DEBUG) {
                $this->queryLog[] = [
                    'sql' => $sql,
                    'params' => $params,
                    'time' => microtime(true) - $startTime
                ];
            }
            
            return $stmt;
        } catch (\PDOException $e) {
            error_log("Query failed: {$sql} - " . $e->getMessage());
            throw new \RuntimeException('Database query failed');
        }
    }
    
    /**
     * Select single row
     */
    public function selectOne(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }
    
    /**
     * Select all rows
     */
    public function select(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }
    
    /**
     * Insert row and return ID
     */
    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $this->escapeIdentifier($table),
            implode('`, `', array_map([$this, 'escapeIdentifier'], $columns)),
            implode(', ', $placeholders)
        );
        
        $this->query($sql, array_values($data));
        
        return (int) $this->pdo->lastInsertId();
    }
    
    /**
     * Update rows
     */
    public function update(string $table, array $data, array $where): int
    {
        $setParts = [];
        $params = [];
        
        foreach ($data as $column => $value) {
            $setParts[] = '`' . $this->escapeIdentifier($column) . '` = ?';
            $params[] = $value;
        }
        
        $whereParts = [];
        foreach ($where as $column => $value) {
            $whereParts[] = '`' . $this->escapeIdentifier($column) . '` = ?';
            $params[] = $value;
        }
        
        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE %s',
            $this->escapeIdentifier($table),
            implode(', ', $setParts),
            implode(' AND ', $whereParts)
        );
        
        return $this->query($sql, $params)->rowCount();
    }
    
    /**
     * Delete rows
     */
    public function delete(string $table, array $where): int
    {
        $whereParts = [];
        $params = [];
        
        foreach ($where as $column => $value) {
            $whereParts[] = '`' . $this->escapeIdentifier($column) . '` = ?';
            $params[] = $value;
        }
        
        $sql = sprintf(
            'DELETE FROM `%s` WHERE %s',
            $this->escapeIdentifier($table),
            implode(' AND ', $whereParts)
        );
        
        return $this->query($sql, $params)->rowCount();
    }
    
    /**
     * Escape identifier (table/column name)
     */
    private function escapeIdentifier(string $identifier): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $identifier);
    }
    
    /**
     * Begin transaction (supports nesting)
     */
    public function beginTransaction(): bool
    {
        if ($this->transactionLevel === 0) {
            $this->pdo->beginTransaction();
        } else {
            $this->pdo->exec("SAVEPOINT trans_{$this->transactionLevel}");
        }
        
        $this->transactionLevel++;
        return true;
    }
    
    /**
     * Commit transaction
     */
    public function commit(): bool
    {
        $this->transactionLevel--;
        
        if ($this->transactionLevel === 0) {
            return $this->pdo->commit();
        }
        
        return true;
    }
    
    /**
     * Rollback transaction
     */
    public function rollback(): bool
    {
        $this->transactionLevel--;
        
        if ($this->transactionLevel === 0) {
            return $this->pdo->rollBack();
        }
        
        $this->pdo->exec("ROLLBACK TO SAVEPOINT trans_{$this->transactionLevel}");
        return true;
    }
    
    /**
     * Execute callback in transaction
     */
    public function transaction(callable $callback)
    {
        $this->beginTransaction();
        
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Get query log
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }
    
    /**
     * Check if table exists
     */
    public function tableExists(string $table): bool
    {
        $sql = "SHOW TABLES LIKE ?";
        return $this->selectOne($sql, [$table]) !== null;
    }
    
    /**
     * Generate UUID
     */
    public function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }
}
