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
            WHERE ur.id_rol = 1 AND u.puntaje_acumulado > 0
            ORDER BY u.puntaje_acumulado DESC";
        return $this->database->query($sql);
    }

    public function obtenerPartidasJugadas()
    {
        $sql = "
                SELECT id_partida, nombre_usuario, fecha_inicio, fecha_fin, puntaje_final, id_usuario
                FROM (
                    SELECT 
                        p.id_partida,
                        u.nombre_usuario,
                        p.fecha_inicio,
                        p.fecha_fin,
                        p.puntaje_final,
                        u.id_usuario,
                        ROW_NUMBER() OVER (
                            PARTITION BY u.id_usuario 
                            ORDER BY p.puntaje_final DESC, p.fecha_fin DESC
                        ) AS rn
                    FROM partidas p
                    JOIN usuarios u ON p.id_usuario = u.id_usuario
                    JOIN usuario_rol ur ON u.id_usuario = ur.id_usuario
                    WHERE ur.id_rol = 1 AND p.fecha_fin IS NOT NULL
                ) AS sub
                WHERE rn = 1
                ORDER BY puntaje_final DESC, fecha_fin DESC
                LIMIT 10;
        ";
        
        return $this->database->query($sql);
    }

}