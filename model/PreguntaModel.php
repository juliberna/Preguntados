<?php

class PreguntaModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    private function seDebeCalcularNivelUsuario($entregadas)
    {
        return $entregadas >= 5;
    }

    private function seDebeCalcularNivelPregunta($pregunta)
    {
        return $pregunta["entregadas"] >= 5;
    }

    private function getEstadisticasUsuario($id_usuario): array
    {
        $result = $this->db->query("
            SELECT preguntas_entregadas, preguntas_acertadas
            FROM usuarios
            WHERE id_usuario = $id_usuario
        ");

        return [
            "entregadas" => $result[0]["preguntas_entregadas"],
            "acertadas" => $result[0]["preguntas_acertadas"]
        ] ?? ['entregadas' => 0, 'acertadas' => 0];
    }

    private function getNivelUsuario($entregadas, $acertadas): string
    {
        if (!$this->seDebeCalcularNivelUsuario($entregadas)) {
            return "intermedio";
        }

        $ratio = $acertadas / $entregadas;
        if ($ratio > 0.7) {
            return 'facil';
        }
        if ($ratio < 0.3) {
            return 'dificil';
        }
        return 'intermedio';
    }

    private function getDificultadPregunta($pregunta): string
    {
        if (!$this->seDebeCalcularNivelPregunta($pregunta)) {
            return 'intermedio';
        }

        $ratio = $pregunta['correctas'] / $pregunta['entregadas'];
        if ($ratio > 0.7) {
            return 'facil';
        }
        if ($ratio < 0.3) {
            return 'dificil';
        }
        return 'intermedio';
    }

    private function getPreguntasNoVistas($id_usuario, $id_categoria): array
    {
        return $this->db->query("
            SELECT p.*
              FROM preguntas p
              LEFT JOIN usuario_pregunta up
                ON p.id_pregunta = up.idPregunta
               AND up.idUsuario   = $id_usuario
             WHERE up.idPregunta IS NULL
               AND p.id_categoria = $id_categoria
        ");
    }

    private function limpiarHistorialPreguntasVistas($id_usuario)
    {
        $this->db->execute("
            DELETE FROM usuario_pregunta
             WHERE idUsuario = $id_usuario
        ");
    }

    public function agruparPorNivel($preguntas): array
    {
        $grupos = [
            'facil' => [],
            'intermedio' => [],
            'dificil' => [],
        ];
        foreach ($preguntas as $p) {
            $dificultad = $this->getDificultadPregunta($p);
            $grupos[$dificultad][] = $p;
        }
        return $grupos;
    }

    public function elegirPorNivelUsuario($grupos, $nivelUsuario): array
    {
        $order = ['facil', 'intermedio', 'dificil'];
        $idx = array_search($nivelUsuario, $order, true);

        // Intento desde el mismo nivel hacia niveles más difíciles
        for ($i = $idx, $iMax = count($order); $i < $iMax; $i++) {
            if (!empty($grupos[$order[$i]])) {
                return $grupos[$order[$i]][array_rand($grupos[$order[$i]])];
            }
        }
        // Si aún vacío, busco niveles más fáciles
        for ($i = $idx - 1; $i >= 0; $i--) {
            if (!empty($grupos[$order[$i]])) {
                return $grupos[$order[$i]][array_rand($grupos[$order[$i]])];
            }
        }

        // No debería llegar aquí
        return [];
    }


    /*
     * 1. Dificultad adecuada según su nivel (ratio: correctas / entregadas)
     * 2. Haya sido entregada al menos 5 veces
     * 3. El usuario no la vio (usuario_pregunta)
     * 4. Sea de esa categoria
     */
    public function obtenerPregunta($id_usuario, $id_categoria)
    {
        // estadisticas del usuario
        $estadisticas = $this->getEstadisticasUsuario($id_usuario);

        // Nivel del usuario
        $nivelUsuario = $this->getNivelUsuario(
            $estadisticas['entregadas'],
            $estadisticas['acertadas']
        );

        // Preguntas no vistas
        $preguntas = $this->getPreguntasNoVistas($id_usuario, $id_categoria);

        // si se acabaron → limpio historial y recursión
        if (empty($preguntas)) {
            $this->limpiarHistorialPreguntasVistas($id_usuario);
            return $this->obtenerPregunta($id_usuario, $id_categoria);
        }

        // d) agrupo y selecciono según nivel
        $grupos = $this->agruparPorNivel($preguntas);

        $preg = $this->elegirPorNivelUsuario($grupos, $nivelUsuario);
        return $preg;
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
        $stmt->bind_param("ii", $id_usuario, $id_pregunta);
        $stmt->execute();
    }

    public function incrementarEntregas($id_pregunta)
    {
        $stmt = $this->db->prepare("UPDATE preguntas SET entregadas = entregadas + 1 WHERE id_pregunta = ?");
        $stmt->bind_param("i", $id_pregunta);
        $stmt->execute();
    }

    public function incrementarEntregadasUsuario($id_usuario)
    {
        $stmt = $this->db->prepare("UPDATE usuarios SET preguntas_entregadas = preguntas_entregadas + 1 WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
    }

    public function registrarPreguntaEnPartida($id_partida, $id_pregunta, $id_respuesta, $es_correcta)
    {
        $stmt = $this->db->prepare("INSERT INTO partida_pregunta (id_partida, id_pregunta,id_respuesta_elegida,acerto) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiii", $id_partida, $id_pregunta, $id_respuesta, $es_correcta);
        $stmt->execute();
    }

    public function incrementarCorrectasPregunta($id_pregunta)
    {
        $stmt = $this->db->prepare("UPDATE preguntas SET correctas = correctas + 1 WHERE id_pregunta = ?");
        $stmt->bind_param("i", $id_pregunta);
        $stmt->execute();
    }

    public function incrementarCorrectasUsuario($id_usuario)
    {
        $stmt = $this->db->prepare("UPDATE usuarios SET preguntas_acertadas = preguntas_acertadas + 1 WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
    }

    public function sumarPunto($id_usuario)
    {
        $stmt = $this->db->prepare("UPDATE usuarios SET puntaje_acumulado = puntaje_acumulado + 1 WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
    }

    public function getPuntaje($id_usuario)
    {
        $stmt = $this->db->prepare("SELECT puntaje_acumulado FROM usuarios WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado && $row = $resultado->fetch_assoc()) {
            return (int)$row['puntaje_acumulado']; // lo casteamos a entero
        }

        return 0;
    }
}
