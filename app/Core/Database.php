<?php
// app/Core/Database.php

namespace App\Core;

use PDO;
use PDOException;
use Exception;

class Database
{
    private static $instance = null;
    private $connection;
    private $config;
    
    // Query builder properties
    private $table;
    private $select = '*';
    private $where = [];
    private $joins = [];
    private $orderBy = [];
    private $groupBy = [];
    private $limit;
    private $offset;
    private $bindings = [];
    
    private function __construct()
    {
        $this->config = require __DIR__ . '/../../config/database.php';
        $this->connect();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection()
    {
        return $this->connection;
    }
    
    /**
     * Connect to database
     */
    private function connect()
    {
        try {
            $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['database']};charset={$this->config['charset']}";
            
            $this->connection = new PDO($dsn, $this->config['username'], $this->config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->config['charset']} COLLATE {$this->config['collation']}"
            ]);
            
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Start query builder with table
     */
    public function table($table)
    {
        $this->resetBuilder();
        $this->table = $table;
        return $this;
    }
    
    /**
     * Select columns
     */
    public function select($columns = '*')
    {
        $this->select = is_array($columns) ? implode(', ', $columns) : $columns;
        return $this;
    }
    
    /**
     * Add WHERE condition
     */
    public function where($column, $operator = '=', $value = null)
    {
        if ($value === null && $operator !== '=' && !in_array($operator, ['IS NULL', 'IS NOT NULL'])) {
            $value = $operator;
            $operator = '=';
        }
        
        $placeholder = $this->generatePlaceholder();
        $this->where[] = [
            'type' => 'AND',
            'condition' => "{$column} {$operator} {$placeholder}"
        ];
        
        if ($value !== null && !in_array($operator, ['IS NULL', 'IS NOT NULL'])) {
            $this->bindings[$placeholder] = $value;
        }
        
        return $this;
    }
    
    /**
     * Add OR WHERE condition
     */
    public function orWhere($column, $operator = '=', $value = null)
    {
        if ($value === null && $operator !== '=' && !in_array($operator, ['IS NULL', 'IS NOT NULL'])) {
            $value = $operator;
            $operator = '=';
        }
        
        $placeholder = $this->generatePlaceholder();
        $this->where[] = [
            'type' => 'OR',
            'condition' => "{$column} {$operator} {$placeholder}"
        ];
        
        if ($value !== null && !in_array($operator, ['IS NULL', 'IS NOT NULL'])) {
            $this->bindings[$placeholder] = $value;
        }
        
        return $this;
    }
    
    /**
     * Add WHERE IN condition
     */
    public function whereIn($column, $values)
    {
        $placeholders = [];
        foreach ($values as $value) {
            $placeholder = $this->generatePlaceholder();
            $placeholders[] = $placeholder;
            $this->bindings[$placeholder] = $value;
        }
        
        $this->where[] = [
            'type' => 'AND',
            'condition' => "{$column} IN (" . implode(', ', $placeholders) . ")"
        ];
        
        return $this;
    }
    
    /**
     * Add JOIN
     */
    public function join($table, $first, $operator = '=', $second = null)
    {
        if ($second === null) {
            $second = $operator;
            $operator = '=';
        }
        
        $this->joins[] = "INNER JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }
    
    /**
     * Add LEFT JOIN
     */
    public function leftJoin($table, $first, $operator = '=', $second = null)
    {
        if ($second === null) {
            $second = $operator;
            $operator = '=';
        }
        
        $this->joins[] = "LEFT JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }
    
    /**
     * Add ORDER BY
     */
    public function orderBy($column, $direction = 'ASC')
    {
        $this->orderBy[] = "{$column} {$direction}";
        return $this;
    }
    
    /**
     * Add GROUP BY
     */
    public function groupBy($column)
    {
        $this->groupBy[] = $column;
        return $this;
    }
    
    /**
     * Add LIMIT
     */
    public function limit($limit, $offset = null)
    {
        $this->limit = $limit;
        if ($offset !== null) {
            $this->offset = $offset;
        }
        return $this;
    }
    
    /**
     * Execute SELECT query and return all results
     */
    public function get()
    {
        $sql = $this->buildSelectSql();
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->fetchAll();
    }
    
    /**
     * Execute SELECT query and return first result
     */
    public function first()
    {
        $this->limit(1);
        $results = $this->get();
        return $results ? $results[0] : null;
    }
    
    /**
     * Find record by ID
     */
    public function find($id)
    {
        return $this->where('id', $id)->first();
    }
    
    /**
     * Insert record
     */
    public function insert($data)
    {
        $columns = array_keys($data);
        $placeholders = array_map(function($col) { return ":{$col}"; }, $columns);
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($data);
        
        return $this->connection->lastInsertId();
    }
    
    /**
     * Insert multiple records
     */
    public function insertBatch($data)
    {
        if (empty($data)) {
            return false;
        }
        
        $columns = array_keys($data[0]);
        $placeholders = array_map(function($col) { return ":{$col}"; }, $columns);
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->connection->prepare($sql);
        
        $this->connection->beginTransaction();
        try {
            foreach ($data as $row) {
                $stmt->execute($row);
            }
            $this->connection->commit();
            return true;
        } catch (Exception $e) {
            $this->connection->rollback();
            throw $e;
        }
    }
    
    /**
     * Update records
     */
    public function update($data)
    {
        $setClause = [];
        foreach ($data as $column => $value) {
            $placeholder = $this->generatePlaceholder();
            $setClause[] = "{$column} = {$placeholder}";
            $this->bindings[$placeholder] = $value;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause);
        
        if (!empty($this->where)) {
            $sql .= " WHERE " . $this->buildWhereClause();
        }
        
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($this->bindings);
    }
    
    /**
     * Delete records
     */
    public function delete()
    {
        if (empty($this->where)) {
            throw new Exception("DELETE queries must have WHERE conditions for safety");
        }
        
        $sql = "DELETE FROM {$this->table} WHERE " . $this->buildWhereClause();
        
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($this->bindings);
    }
    
    /**
     * Get count of records
     */
    public function count()
    {
        $this->select = 'COUNT(*) as count';
        $result = $this->first();
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Execute raw query
     */
    public function query($sql, $bindings = [])
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll();
    }
    
    /**
     * Execute raw statement (INSERT, UPDATE, DELETE)
     */
    public function statement($sql, $bindings = [])
    {
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($bindings);
    }
    
    /**
     * Start database transaction
     */
    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit()
    {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback()
    {
        return $this->connection->rollback();
    }
    
    /**
     * Build SELECT SQL
     */
    private function buildSelectSql()
    {
        $sql = "SELECT {$this->select} FROM {$this->table}";
        
        if (!empty($this->joins)) {
            $sql .= " " . implode(' ', $this->joins);
        }
        
        if (!empty($this->where)) {
            $sql .= " WHERE " . $this->buildWhereClause();
        }
        
        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY " . implode(', ', $this->groupBy);
        }
        
        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        }
        
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
            if ($this->offset !== null) {
                $sql .= " OFFSET {$this->offset}";
            }
        }
        
        return $sql;
    }
    
    /**
     * Build WHERE clause
     */
    private function buildWhereClause()
    {
        $conditions = [];
        
        foreach ($this->where as $index => $where) {
            if ($index === 0) {
                $conditions[] = $where['condition'];
            } else {
                $conditions[] = $where['type'] . ' ' . $where['condition'];
            }
        }
        
        return implode(' ', $conditions);
    }
    
    /**
     * Generate unique placeholder
     */
    private function generatePlaceholder()
    {
        return ':param_' . uniqid();
    }
    
    /**
     * Reset query builder
     */
    private function resetBuilder()
    {
        $this->table = null;
        $this->select = '*';
        $this->where = [];
        $this->joins = [];
        $this->orderBy = [];
        $this->groupBy = [];
        $this->limit = null;
        $this->offset = null;
        $this->bindings = [];
    }
    
    /**
     * Prevent cloning
     */
    public function __clone()
    {
        throw new Exception("Cannot clone singleton Database instance");
    }
    
    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton Database instance");
    }
}