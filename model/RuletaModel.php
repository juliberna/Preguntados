<?php

class RuletaModel
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function getCategorias() {
        $sql = "SELECT id_categoria, nombre, foto_categoria FROM categoria ORDER BY id_categoria ASC";
        return $this->db->query($sql);
    }

    public function getCategoriaAleatoria()
    {
        $sql = "SELECT * FROM categoria ORDER BY RAND() LIMIT 1";
        $resultado = $this->db->query($sql);
        return $resultado[0] ?? null;
    }
}