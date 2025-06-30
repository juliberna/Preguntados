<?php

class AdminModel{

    private $database;

    public function __construct($database){
        $this->database = $database;
    }

    public function obtenerDistribucionPorGenero($desde, $hasta)
    {
        $sql = "SELECT u.id_sexo, COUNT(*)
                FROM usuarios u JOIN sexo s ON u.id_sexo = s.id_sexo
                WHERE u.fecha_registro BETWEEN '$desde' AND '$hasta'
                GROUP BY u.id_sexo";
        return $this->database->query($sql);
    }

    public function obtenerDistribucionPorRangoEdad($desde, $hasta)
    {
        $sql = "SELECT 
                    CASE
                        WHEN(YEAR(CURDATE()) - anio_nacimiento) < 18 THEN 'Menor'
                        WHEN(YEAR(CURDATE()) - anio_nacimiento) BETWEEN 18 AND 60 THEN 'Mediana edad'
                        ELSE 'Mayor'
                    END AS rangoEdad,  
                    COUNT(*) AS cantidad
                    FROM usuarios
                    WHERE u.fecha_registro BETWEEN '$desde' AND '$hasta'
                    GROUP BY rangoEdad";
        return $this->database->query($sql);
    }

    public function obtenerUsuariosPorPaisPorFecha($desde, $hasta)
    {
        $sql = "SELECT p.nombre_pais, COUNT(*) AS cantidad
        FROM usuarios u JOIN paises p ON u.id_pais = p.id_pais
        WHERE u.fecha_registro BETWEEN '$desde' AND '$hasta'
        GROUP BY u.id_pais";
        return $this->database->query($sql);
    }

    public function obtenerTotalUsuariosPorFecha($desde, $hasta)
    {
        $sql = "SELECT COUNT(*) AS cantidad
                FROM usuarios u JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
                WHERE ur.id_rol = 1 AND u.fecha_registro BETWEEN '$desde' AND '$hasta'";
        return $this->database->query($sql);
    }

    public function obtenerPartidasJugadasPorFecha($desde, $hasta)
    {
        $sql = "SELECT COUNT(*) AS cantidad
                FROM partidas p
                WHERE p.fecha_inicio BETWEEN '$desde' AND '$hasta'";
        return $this->database->query($sql);
    }

    public function obtenerPreguntasActivasPorFecha($desde, $hasta)
    {
        $sql = "SELECT COUNT(*) AS cantidad
                FROM preguntas p
                WHERE p.estado = 'activa' AND p.fecha_inicio BETWEEN '$desde' AND '$hasta'";
        return $this->database->query($sql);
    }

    public function obtenerPromedioDePreguntasRespondidasCorrectamente($desde, $hasta)
    {
        $sql = "SELECT COUNT(*) AS totalRespuestas, SUM(pp.acerto) AS totalAciertos, ROUND(SUM(pp.acerto)/COUNT(*) * 100, 1) AS porcentaje_correctas
                FROM partida_pregunta pp JOIN partidas p ON pp.id_partida = p.id_partida
                WHERE p.fecha_inicio BETWEEN '$desde' AND '$hasta'
        ";
        return $this->database->query($sql);
    }


}