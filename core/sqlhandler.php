<?php
namespace Leap\Core;

class SQLHandler
{
    public $pdo;
    public $result;

    public function setDb($pdo)
    {
        if ($pdo) {
            $this->pdo = $pdo;
        } else {
            $this->pdo = SQLHandler::connect();
        }
    }

    public function hasConnection()
    {
        return is_object($this->pdo);
    }

    public static function connect()
    {
        $db_conf = config('database');
        if ($db_conf['db_type'] == "mysql") {
            if (!isset($db_conf['db_host']) || !isset($db_conf['db_user']) || !isset($db_conf['db_pass']) || !isset($db_conf['db_name'])) {
                return 0;
            }
            $opt = array(
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => true,
            );
            $dsn = 'mysql:host=' . $db_conf['db_host'] . ';dbname=' . $db_conf['db_name'] . ';charset=utf8';
            return new \PDO($dsn, $db_conf['db_user'], $db_conf['db_pass'], $opt);
        } else {
            return -1;
        }
    }

    public function run($sql, $data = [])
    {
        $stmt         = $this->pdo->prepare($sql);
        $this->result = $stmt->execute($data);
        return $stmt;
    }

    public function __call($method, $args)
    {
        return call_user_func_array(array($this->pdo, $method), $args);
    }

    public function insert($table, $data = [])
    {
        $set = "";
        foreach ($data as $field) {
            $set .= "`" . str_replace("`", "``", $field) . "`" . "=:$field, ";
        }
        $set  = substr($set, 0, -2);
        $stmt = $this->pdo->prepare("INSERT INTO $table SET $set");
        $stmt->execute($data);
    }
}
