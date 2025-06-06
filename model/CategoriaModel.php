<?php

class CategoriaModel
{
  private $db;

  public function __construct($db)
  {
    $this->db = $db;
  }

  public function getCategoriaAleatoria()
  {
    $sql = "SELECT * FROM categoria ORDER BY RAND() LIMIT 1";
    $resultado = $this->db->query($sql);
    return $resultado[0] ?? null;
  }

  public function getCategorias()
  {
    $sql = "SELECT * FROM categoria";
    return $this->db->query($sql);
  }
}
