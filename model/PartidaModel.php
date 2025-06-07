<?php

class PartidaModel
{

    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function crearPartida($id_usuario)
    {
        $stmt = $this->db->prepare("INSERT INTO partidas (id_usuario, fecha_inicio) VALUES (?, NOW())");
        $stmt->execute([$id_usuario]);
        return $this->db->getLastInsertId();
    }

    public function finalizarPartida($id_partida, $puntaje)
    {
        $stmt = $this->db->prepare("UPDATE partidas SET fecha_fin = NOW(), puntaje_final = ? WHERE id_partida = ?");
        $stmt->execute([$puntaje, $id_partida]);
    }

}