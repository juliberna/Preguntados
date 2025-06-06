<?php

class PerfilModel
{
  private $database;

  public function __construct($database)
  {
    $this->database = $database;
  }

  public function getDatos($id_usuario)
  {

    $resultado = $this->database->query("
        SELECT u.nombre_usuario, u.foto_perfil_url,
               p.nombre_pais, c.nombre_ciudad
        FROM usuarios u
        JOIN paises p ON u.id_pais = p.id_pais
        JOIN ciudades c ON u.id_ciudad = c.id_ciudad
        WHERE u.id_usuario = $id_usuario");

    return $resultado ?? [];

  }


}
