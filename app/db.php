<?php
/**
 * Tiny PDO wrapper. Works with MySQL (cPanel) and SQLite (local testing).
 * Always use ? placeholders. LIMIT/OFFSET values must be cast to int and
 * written into the SQL directly.
 */

function db()
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    $c = cfg('db');
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    if (($c['driver'] ?? 'mysql') === 'sqlite') {
        $path = $c['sqlite_path'];
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }
        $pdo = new PDO('sqlite:' . $path, null, null, $options);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
    } else {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], (int) ($c['port'] ?? 3306), $c['name']);
        $pdo = new PDO($dsn, $c['user'], $c['pass'], $options);
        $pdo->exec("SET time_zone = '+00:00'");
    }
    return $pdo;
}

function db_driver()
{
    return cfg('db.driver', 'mysql') === 'sqlite' ? 'sqlite' : 'mysql';
}

function q($sql, array $params = [])
{
    $st = db()->prepare($sql);
    $st->execute(array_values($params));
    return $st;
}

function one($sql, array $params = [])
{
    $row = q($sql, $params)->fetch();
    return $row ?: null;
}

function all($sql, array $params = [])
{
    return q($sql, $params)->fetchAll();
}

function val($sql, array $params = [])
{
    $v = q($sql, $params)->fetchColumn();
    return $v === false ? null : $v;
}

function insert($table, array $data)
{
    $cols = array_keys($data);
    $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')';
    q($sql, array_values($data));
    return (int) db()->lastInsertId();
}

function update($table, array $data, $where, array $params = [])
{
    $sets = [];
    foreach (array_keys($data) as $col) {
        $sets[] = $col . ' = ?';
    }
    $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE ' . $where;
    return q($sql, array_merge(array_values($data), $params))->rowCount();
}

function is_installed()
{
    return is_file(ROOT . '/storage/installed.lock');
}

/** Builds "col IN (?, ?, ?)" for an array of values. */
function in_placeholders(array $values)
{
    return implode(', ', array_fill(0, max(1, count($values)), '?'));
}
