<?php

class PreguntaModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /*
     * 1. Dificultad adecuada según su nivel (ratio: correctas / entregadas)
     * 2. Haya sido entregada al menos 5 veces
     * 3. El usuario no la vio (usuario_pregunta)
     * 4. Sea de esa categoria
     */
    public function obtenerPregunta($id_usuario, $id_categoria)
    {
        // Por ahora solo devuelvo la pregunta que coincida con esa categoria
        $sql = "SELECT * FROM preguntas WHERE id_categoria = $id_categoria";
        $resultado = $this->db->query($sql);
        return $resultado[0];
    }

    public function obtenerRespuestas($id_pregunta)
    {
        $sql = "SELECT * FROM respuestas WHERE id_pregunta = $id_pregunta";
        $resultado = $this->db->query($sql);
        return $resultado;
    }

    public function validarRespuesta($id_respuesta)
    {
        $sql = "SELECT esCorrecta FROM respuestas WHERE id_respuesta = $id_respuesta";
        $resultado = $this->db->query($sql);

        // Si no hay una respuesta con este id, devuelvo false
        if (empty($resultado)) {
            return false;
        }

        // Retorna el atributo boolean esCorrecta de la respuesta encontrada
        return $resultado[0]['esCorrecta'];
    }

    public function marcarPreguntaComoVista($id_usuario, $id_pregunta)
    {
        $stmt = $this->db->prepare("INSERT INTO usuario_pregunta (idUsuario, idPregunta, fechaVisto) VALUES (?, ?, NOW())");
        $stmt->execute([$id_usuario, $id_pregunta]);
    }

    public function incrementarEntregas($id_pregunta)
    {
        $stmt = $this->db->prepare("UPDATE preguntas SET entregadas = entregadas + 1 WHERE id_pregunta = ?");
        $stmt->execute([$id_pregunta]);
    }

    public function incrementarEntregadasUsuario($id_usuario)
    {
        $stmt = $this->db->prepare("UPDATE usuario SET preguntas_entregadas = preguntas_entregadas + 1 WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);
    }

    public function registrarPreguntaEnPartida($id_partida, $id_pregunta)
    {
        $stmt = $this->db->prepare("INSERT INTO partida_pregunta (id_partida, id_pregunta) VALUES (?, ?)");
        $stmt->execute([$id_partida, $id_pregunta]);
    }

    public function guardarRespuestaEnPartida($id_partida, $id_pregunta, $id_respuesta, $es_correcta)
    {
        $stmt = $this->db->prepare("UPDATE partida_pregunta SET id_respuesta_elegida = ?, `acerto?` = ? WHERE id_partida = ? AND id_pregunta = ?");
        $stmt->execute([$id_respuesta, $es_correcta ? 1 : 0, $id_partida, $id_pregunta]);
    }

    public function incrementarCorrectasPregunta($id_pregunta)
    {
        $stmt = $this->db->prepare("UPDATE preguntas SET correctas = correctas + 1 WHERE id_pregunta = ?");
        $stmt->execute([$id_pregunta]);
    }

    public function incrementarCorrectasUsuario($id_usuario)
    {
        $stmt = $this->db->prepare("UPDATE usuarios SET preguntas_acertadas = preguntas_acertadas + 1 WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);
    }
}
