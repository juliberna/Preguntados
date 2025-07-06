<?php

class Database
{

    private $conn;

    function __construct($servername, $username, $dbname, $password)
    {
        $this->conn = new Mysqli($servername, $username, $password, $dbname) or die("Error de conexion " . mysqli_connect_error());

        $this->conn->set_charset("utf8mb4");
        $this->conn->query("SET time_zone = '-03:00'");
    }

    public function query($sql)
    {
        $result = $this->conn->query($sql);

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function execute($sql)
    {
        $this->conn->query($sql);
    }

    public function prepare($sql)
    {
        return $this->conn->prepare($sql);
    }

    public function getLastInsertId()
    {
        return $this->conn->insert_id;
    }

    function __destruct()
    {
        $this->conn->close();
    }

    public function escapeLike($string)
    {
        return $this->conn->real_escape_string($string);
    }
}