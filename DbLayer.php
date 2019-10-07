<?php

namespace DbLayer;

//TODO сделать все запросы на prepare если хотим остаться с сервером

class DbLayer
{
    private $username;
    private $password;
    private $host;
    private $dbName;

    public function __construct($username, $password, $host, $dbName)
    {
        $this->dbName = $dbName;
        $this->host = $host;
        $this->password = $password;
        $this->username = $username;
    }

    public function connect()
    {
        $host = $this->host;
        $db = $this->dbName;
        $user = $this->username;
        $pass = $this->password;
        $charset = 'utf8';
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $opt = $opt = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new \PDO($dsn, $user, $pass, $opt);
        return $pdo;
    }

    public function select($whereStatement, $table='user')
    {
        $dbh = $this->connect();
        $sql = "SELECT * FROM " . $table . " WHERE userId = '{$whereStatement}' LIMIT 1";
        $stmt = $dbh->query($sql);
        $dbh = null;
        return $stmt->fetch();
    }

    public function update($par, $val, $userId, $table='user')
    {
        $dbh = $this->connect();
        $sql = "UPDATE {$table} SET {$par} = '{$val}' where userId = '{$userId}';";
        $stmt = $dbh->query($sql);
        $dbh = null;
    }

    public function insertNewUserId($userId)
    {
        $dbh = $this->connect();
        $sql = "INSERT INTO user VALUES (NULL, '{$userId}', NULL,NULL, NULL, NULL, NULL);";
        $stmt = $dbh->query($sql);
        $dbh = null;

    }

    public function deleteUserById($userId, $table='user')
    {
        $dbh = $this->connect();
        $sql = "DELETE FROM {$table} where userId = '{$userId}';";
        $stmt = $dbh->query($sql);
        $dbh = null;

    }
}
