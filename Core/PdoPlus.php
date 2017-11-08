<?php
namespace Leap\Core;

use PDO;
use PDOStatement;

class PdoPlus
{

    /**
     * The PDO connection itself.
     *
     * @var PDO
     *
     */
    protected $pdo;

    /**
     * Boolean to check if we have a connection
     *
     * @var boolean
     *
     */
    protected $connected = false;

    /**
     * The attributes for a lazy connection.
     *
     * @var array
     *
     */
    protected $attributes = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => true,
    ];
    protected $host;
    protected $username;
    protected $password;
    protected $dbName;
    /**
     * @var
     */
    public $result;

    public function __construct($host, string $username = null, string $password = null, string $database = null, array $attributes = [])
    {
        if ($host instanceof PDO) {
            /* use existing PDO object */
            $this->pdo       = $host;
            $this->connected = true;
        } else {
            /* lazy connection */
            $this->host       = $host;
            $this->username   = $username;
            $this->password   = $password;
            $this->dbName     = $database;
            $this->attributes = array_replace($this->attributes, $attributes);
        }
    }

    public function hasConnection(): bool
    {
        return $this->connected;
    }

    public function connect(): void
    {
        /* Return if we are already connected */
        if ($this->connected) {
            return;
        }
        $dsn             = 'mysql:host=' . $this->host . ';dbname=' . $this->dbName . ';charset=utf8';
        $this->pdo       = new PDO($dsn, $this->username, $this->password, $this->attributes);
        $this->connected = true;
    }

    public function run($sql, $data = []): PDOStatement
    {
        /* lazy connection */
        $this->connect();
        $stmt         = $this->pdo->prepare($sql);
        $this->result = $stmt->execute($data);
        return $stmt;
    }

    /* Make all PDO functions available */
    public function __call($method, $args)
    {
        /* lazy connection */
        $this->connect();
        return call_user_func_array([$this->pdo, $method], $args);
    }

    public function insert($table, $data = []): boolean
    {
        /* lazy connection */
        $this->connect();
        $set = "";
        foreach ($data as $field) {
            $set .= "`" . str_replace("`", "``", $field) . "`" . "=:$field, ";
        }
        $set  = substr($set, 0, -2);
        $stmt = $this->pdo->prepare("INSERT INTO $table SET $set");
        return $stmt->execute($data);
    }
}
