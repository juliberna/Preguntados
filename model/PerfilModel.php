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
        SELECT u.nombre_usuario, u.foto_perfil_url,u.latitud,u.longitud,
               p.nombre_pais, c.nombre_ciudad
        FROM usuarios u
        JOIN paises p ON u.id_pais = p.id_pais
        JOIN ciudades c ON u.id_ciudad = c.id_ciudad
        WHERE u.id_usuario = $id_usuario");

        return $resultado ?? [];

    }

    public function getCantidadPartidasJugadas($id_usuario)
    {
        $sql = "SELECT COUNT(*) AS total FROM partidas WHERE id_usuario = $id_usuario";
        $resultado = $this->database->query($sql);
        return $resultado[0]['total'] ?? 0;
    }

    public function getTotalPreguntasRespondidas($id_usuario)
    {
        $sql = "SELECT preguntas_entregadas FROM usuarios WHERE id_usuario = $id_usuario";
        $resultado = $this->database->query($sql);
        return $resultado[0]['preguntas_entregadas'] ?? 0;
    }

    public function getPorcentajeAcierto($id_usuario)
    {
        $sql = "SELECT preguntas_acertadas, preguntas_entregadas FROM usuarios WHERE id_usuario = $id_usuario";
        $resultado = $this->database->query($sql);

        $acertadas = $resultado[0]['preguntas_acertadas'] ?? 0;
        $entregadas = $resultado[0]['preguntas_entregadas'] ?? 0;

        if ($entregadas === 0) {
            return 0;
        }

        return round(($acertadas / $entregadas) * 100, 2);
    }

    public function getMayorPuntajePartida($id_usuario)
    {
        $sql = "SELECT MAX(puntaje_final) AS max_puntaje FROM partidas WHERE id_usuario = $id_usuario";
        $resultado = $this->database->query($sql);
        return $resultado[0]['max_puntaje'] ?? 0;
    }

    public function getCategoriasDestacadas($id_usuario)
    {
        $sql = "
        SELECT c.nombre, c.color
        FROM categoria c
        JOIN preguntas p ON p.id_categoria = c.id_categoria
        JOIN partida_pregunta pp ON pp.id_pregunta = p.id_pregunta
        JOIN partidas par ON par.id_partida = pp.id_partida
        WHERE par.id_usuario = $id_usuario
          AND pp.acerto = 1
        GROUP BY c.id_categoria
        ORDER BY COUNT(*) DESC
        LIMIT 3
    ";
        return $this->database->query($sql);
    }

    public function getPosicionRanking($id_usuario)
    {
        $sql = "
        SELECT COUNT(*) + 1 AS posicion
        FROM usuarios u
        JOIN usuario_rol ur ON u.id_usuario = ur.id_usuario
        WHERE ur.id_rol = 1
          AND u.puntaje_acumulado > (
              SELECT puntaje_acumulado
              FROM usuarios
              WHERE id_usuario = $id_usuario
          )
    ";

        $resultado = $this->database->query($sql);
        return $resultado[0]['posicion'] ?? null;
    }

}