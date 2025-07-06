<?php

class RankingModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function obtenerRanking()
    {
        $sql = "SELECT u.id_usuario, u.nombre_usuario, u.foto_perfil_url, u.puntaje_acumulado
            FROM usuarios u
            JOIN usuario_rol ur ON u.id_usuario = ur.id_usuario
            WHERE ur.id_rol = 1
            ORDER BY u.puntaje_acumulado DESC
            LIMIT 10";
        return $this->database->query($sql);
    }

    public function obtenerPartidasJugadas()
    {
        $sql = "SELECT p.id_partida, u.nombre_usuario, p.fecha_inicio, p.fecha_fin, p.puntaje_final, u.id_usuario
            FROM partidas p
            JOIN usuarios u ON p.id_usuario = u.id_usuario
            JOIN usuario_rol ur ON u.id_usuario = ur.id_usuario
            WHERE ur.id_rol = 1
            ORDER BY p.puntaje_final DESC, p.fecha_fin DESC
            LIMIT 10";
        return $this->database->query($sql);
    }

}