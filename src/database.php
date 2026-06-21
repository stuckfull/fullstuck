<?php

function fst_db_quote_ident($name, $connection = null) {
    $conn_name = $connection ?? fst_config('database.default', 'main');
    $db_config = fst_config("database.connections.{$conn_name}");
    $driver = strtolower($db_config['driver'] ?? 'sqlite');
    $q = ($driver === 'pgsql') ? '"' : '`';
    
    // [PATCH] Dukungan table.column
    if (str_contains($name, '.')) {
        $parts = explode('.', $name);
        $quoted_parts = array_map(function($p) use ($q) {
            return $q . str_replace($q, $q . $q, $p) . $q;
        }, $parts);
        return implode('.', $quoted_parts);
    }
    return $q . str_replace($q, $q . $q, $name) . $q;
}

// Sanitasi order_by agar aman dari SQL Injection
function _fst_sanitize_order_by($order_by, $connection = null) {
    $parts = array_map('trim', explode(',', $order_by));
    $safe_parts = [];
    foreach ($parts as $part) {
        // Format yang diizinkan: "column_name" atau "column_name ASC/DESC"
        if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_.]*)(\s+(ASC|DESC))?$/i', $part, $m)) {
            $safe_parts[] = fst_db_quote_ident($m[1], $connection) . (isset($m[3]) ? ' ' . strtoupper($m[3]) : '');
        }
    }
    return !empty($safe_parts) ? implode(', ', $safe_parts) : null;
}

function _fst_get_pdo($connection = null) {
    global $fst_pdo_pool;
    if (!isset($fst_pdo_pool)) $fst_pdo_pool = [];
    
    $conn_name = $connection ?? fst_config('database.default', 'main');
    
    if (!isset($fst_pdo_pool[$conn_name])) {
        $db_config = fst_config("database.connections.{$conn_name}");
        if (!$db_config) fst_abort(500, "Database connection '{$conn_name}' is not configured.");
        
        $driver = strtolower($db_config['driver'] ?? 'none');
        if ($driver === 'none') fst_abort(500, "Database is disabled for connection '{$conn_name}'.");
        
        try {
            $dsn = '';
            if ($driver === 'sqlite') {
                $path = $db_config['database_path'] ?? 'database.sqlite';
                $dsn = 'sqlite:' . FST_ROOT_DIR . '/' . ltrim($path, '/');
            } else if ($driver === 'mysql' || $driver === 'pgsql') {
                $host = $db_config['host'] ?? '127.0.0.1';
                $port = !empty($db_config['port']) ? ';port=' . $db_config['port'] : '';
                $dbname = $db_config['dbname'] ?? '';
                $dsn = "{$driver}:host={$host}{$port};dbname={$dbname}";
            } else {
                fst_abort(500, "Unsupported DB driver: {$driver}");
            }
            
            $user = $db_config['username'] ?? null;
            $pass = $db_config['password'] ?? null;
            $pdo_instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            // Injeksi PRAGMA performa tinggi khusus SQLite
            if ($driver === 'sqlite') {
                $pdo_instance->exec('PRAGMA journal_mode = WAL;');
                $pdo_instance->exec('PRAGMA busy_timeout = 5000;');
                $pdo_instance->exec('PRAGMA foreign_keys = ON;');
            }
            
            $fst_pdo_pool[$conn_name] = $pdo_instance;
        } catch (PDOException $e) {
            fst_abort(500, "Database Connection Failed [{$conn_name}]: " . (fst_is_safe_to_debug() ? $e->getMessage() : 'Error.'));
        }
    }
    
    return $fst_pdo_pool[$conn_name];
}

function fst_db_begin($connection = null) {
    return _fst_get_pdo($connection)->beginTransaction();
}

function fst_db_commit($connection = null) {
    return _fst_get_pdo($connection)->commit();
}

function fst_db_rollback($connection = null) {
    return _fst_get_pdo($connection)->rollBack();
}

function fst_db($mode, $sql, $params = [], $connection = null) {
    $pdo = _fst_get_pdo($connection);
    
    // [PATCH] Mencegah error PDO generik jika parameter bind berupa array
    foreach ($params as $k => $v) {
        if (is_array($v) || is_object($v)) {
            throw new Exception("Database Error: Parameter bind [{$k}] tidak boleh berupa array atau object.");
        }
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $normalizedSql = strtoupper(trim($sql));
    $isInsert = strpos($normalizedSql, 'INSERT') === 0;
    
    if (strtoupper($mode) === 'EXEC') {
        return [
            'affected_rows' => $stmt->rowCount(),
            'last_id' => $isInsert ? $pdo->lastInsertId() : null,
            'query_type' => strtok($normalizedSql, ' '),
            'success' => true
        ];
    }
    
    return match(strtoupper($mode)) { 
        'ROW' => ($r = $stmt->fetch()) !== false ? $r : null, 
        'SCALAR', 'ONE' => ($r = $stmt->fetchColumn()) !== false ? $r : null, 
        'ALL' => $stmt->fetchAll(), 
        default => $stmt->fetchAll() 
    };
}

function fst_db_select($table, $conditions = [], $options = []) {
    $conn = $options['connection'] ?? null;
    $columns = $options['select'] ?? '*';
    $t = fst_db_quote_ident($table, $conn);
    $sql = "SELECT {$columns} FROM {$t}";
    $params = [];
    if (!empty($conditions)) {
        $where = [];
        foreach ($conditions as $k => $v) {
            $where[] = fst_db_quote_ident($k, $conn) . " = ?";
            $params[] = $v;
        }
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    if (isset($options['order_by'])) {
        $safe_order = _fst_sanitize_order_by($options['order_by'], $conn);
        if ($safe_order) $sql .= " ORDER BY " . $safe_order;
    }
    if (isset($options['limit'])) $sql .= " LIMIT " . (int)$options['limit'];
    if (isset($options['offset'])) $sql .= " OFFSET " . (int)$options['offset'];
    
    $mode = $options['mode'] ?? 'ALL';
    return fst_db($mode, $sql, $params, $conn);
}

function fst_db_insert($table, $data, $options = []) {
    if (empty($data)) return false;
    $conn = $options['connection'] ?? null;
    $t = fst_db_quote_ident($table, $conn);
    $columns = array_map(fn($k) => fst_db_quote_ident($k, $conn), array_keys($data));
    $placeholders = array_fill(0, count($data), '?');
    $sql = "INSERT INTO {$t} (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ")";
    $res = fst_db('EXEC', $sql, array_values($data), $conn);
    return $res['last_id'];
}

function fst_db_update($table, $data, $conditions = [], $options = []) {
    if (empty($conditions)) throw new Exception("Database Error: UPDATE statement requires conditions to prevent accidental mass updates."); 
    if (empty($data)) return false;
    $conn = $options['connection'] ?? null;
    $t = fst_db_quote_ident($table, $conn);
    $set = [];
    $params = [];
    foreach ($data as $k => $v) {
        $set[] = fst_db_quote_ident($k, $conn) . " = ?";
        $params[] = $v;
    }
    $sql = "UPDATE {$t} SET " . implode(", ", $set);
    
    if (!empty($conditions)) {
        $where = [];
        foreach ($conditions as $k => $v) {
            $where[] = fst_db_quote_ident($k, $conn) . " = ?";
            $params[] = $v;
        }
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $res = fst_db('EXEC', $sql, $params, $conn);
    return $res['affected_rows'];
}

function fst_db_delete($table, $conditions, $options = []) {
    if (empty($conditions)) throw new Exception("Database Error: DELETE statement requires conditions to prevent accidental mass deletion."); 
    $conn = $options['connection'] ?? null;
    $t = fst_db_quote_ident($table, $conn);
    $where = [];
    $params = [];
    foreach ($conditions as $k => $v) {
        $where[] = fst_db_quote_ident($k, $conn) . " = ?";
        $params[] = $v;
    }
    $sql = "DELETE FROM {$t} WHERE " . implode(" AND ", $where);
    $res = fst_db('EXEC', $sql, $params, $conn);
    return $res['affected_rows'];
}

function fst_db_row($table, $conditions = [], $options = []) {
    $options['limit'] = 1;
    $options['mode'] = 'ROW';
    return fst_db_select($table, $conditions, $options);
}

function fst_db_exists($table, $conditions = [], $options = []) {
    $options['select'] = '1';
    $row = fst_db_row($table, $conditions, $options);
    return !empty($row);
}
?>
