<?php

class PartidaModel
{

    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }


    public function getColorCategoria($nombre_categoria)
    {

        $sql = "SELECT color FROM categoria WHERE nombre = '$nombre_categoria' ";
        $resultado = $this->db->query($sql);
        return $resultado[0]['color'];

    }

    public function getFotoCategoria($nombre_categoria)
    {
        $sql = "SELECT foto_categoria FROM categoria WHERE nombre = '$nombre_categoria' ";
        $resultado = $this->db->query($sql);
        return $resultado[0]['foto_categoria'];
    }


    public function getRespuestasPorPregunta($id_pregunta)
    {
        $id_preg = intval($id_pregunta);

        $sql = "SELECT id_respuesta, respuesta, esCorrecta FROM respuestas WHERE id_pregunta = $id_preg ";
        return $this->db->query($sql);

    }

    public function getUsuario($id_usuario)
    {
        $sql = "SELECT nombre_usuario FROM usuarios WHERE id_usuario = $id_usuario ";
        $resultado = $this->db->query($sql);
        return $resultado[0]['nombre_usuario'];
    }

    public function incrementarEntregas($id_pregunta)
    {
        $stmt = $this->db->prepare("UPDATE preguntas SET entregadas = entregadas + 1 WHERE id_pregunta = ?");
        $stmt->bind_param("i", $id_pregunta);
        $stmt->execute();
    }

    public function incrementarEntregadasUsuario($id_usuario)
    {
        $sql = "UPDATE usuarios SET preguntas_entregadas = preguntas_entregadas + 1 WHERE id_usuario = $id_usuario ";
        $this->db->execute($sql);

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

    public function incrementoPuntaje($id_partida)
    {

        $_SESSION['puntaje'] = $_SESSION['puntaje'] + 5;
        $puntaje = $_SESSION['puntaje'];
        $sql = "UPDATE partidas SET puntaje_final = $puntaje  WHERE id_partida = $id_partida ";
        $this->db->execute($sql);
    }

    public function acumularPuntajeUsuario($id_usuario)
    {

        $sql = "UPDATE usuarios SET puntaje_acumulado = puntaje_acumulado + 5 WHERE id_usuario = $id_usuario ";
        $this->db->execute($sql);
    }


    public function actualizarFechaPartidaFinalizada($id_partida)
    {

        $sql = "UPDATE partidas SET fecha_fin = NOW() WHERE id_partida = $id_partida ";
        $this->db->execute($sql);

    }


    public function incremetoPreguntaRespondidaCorrectamente($id_partida)
    {

        $sql = "UPDATE partidas SET correctas = correctas + 1 WHERE id_partida = $id_partida";
        $this->db->execute($sql);
    }

    public function getCantidadDePreguntas($id_partida)
    {
        $sql = "SELECT correctas FROM partidas WHERE id_partida = $id_partida";
        $resultado = $this->db->query($sql);
        return $resultado[0]['correctas'];
    }


    public function getTiempo()
    {
        $inicio = $_SESSION['inicio_pregunta'] ?? null;
        if (!$inicio) return 0;

        $ahora = time();
        $tiempo_total = 10;
        $tiempo_pasado = $ahora - $inicio;
        $tiempo_restante = max(0, $tiempo_total - $tiempo_pasado);
        return $tiempo_restante;
    }


    public function crearRegistroPreguntaRespondida($id_partida, $id_pregunta, $id_respuesta, $acerto)
    {

        $id_preg = intval($id_pregunta);
        $id_par = intval($id_partida);

        // Si ya existe, no la inserto
        if ($this->partidaPreguntaYaRegistrada($id_par,$id_preg)) {
            return;
        }

        $id_resp = is_null($id_respuesta) ? "NULL" : intval($id_respuesta);
        $acerto = intval($acerto);


        $sql = "INSERT INTO partida_pregunta (id_partida, id_pregunta, id_respuesta_elegida, acerto) 
    VALUES ($id_par, $id_preg, $id_resp, $acerto)";

        $this->db->execute($sql);
    }

    private function partidaPreguntaYaRegistrada($id_partida, $id_pregunta): bool {
        $res = $this->db->query("
        SELECT 1 
        FROM partida_pregunta 
        WHERE id_partida = $id_partida AND id_pregunta = $id_pregunta
        LIMIT 1
    ");
        return !empty($res);
    }

    public function crearPartida($id_usuario)
    {

        $id_usuario = intval($id_usuario);
        $sql = "INSERT INTO partidas (id_usuario) VALUES ($id_usuario)";
        $this->db->execute($sql);
        return $this->db->getLastInsertId();

    }

    public function getCategoriaAleatoria()
    {
        $sql = "SELECT * FROM categoria ORDER BY RAND() LIMIT 1";
        $resultado = $this->db->query($sql);
        return $resultado[0] ?? null;
    }

    public function marcarPreguntaComoVista($id_usuario, $id_pregunta)
    {
        $stmt = $this->db->prepare("INSERT INTO usuario_pregunta (idUsuario, idPregunta, fechaVisto) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $id_usuario, $id_pregunta);
        $stmt->execute();
    }

    public function getRespuestasPorIdPreguntaAleatoria($id_pregunta)
    {
        $sql = "SELECT id_respuesta, respuesta FROM respuestas WHERE id_pregunta = $id_pregunta ";
        $respuestas_obtenidas = $this->db->query($sql);

        $respuestas = [];

        foreach ($respuestas_obtenidas as $respuesta) {
            $respuestas[] = [
                'id' => $respuesta['id_respuesta'],
                'texto_respuesta' => $respuesta['respuesta']
            ];
        }
        return $respuestas;
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

        // Preguntas no vistas y con estado 'activa'
        // Con estado 'reportada' trae las que no fueron reportadas por ese usuario
        $preguntas = $this->getPreguntasNoVistas($id_usuario, $id_categoria);

        // si se acabaron → limpio historial y recursión
        if (empty($preguntas)) {
            // Solo limpia las de esa categoria
            $this->limpiarHistorialPreguntasVistas($id_usuario, $id_categoria);
            return $this->obtenerPregunta($id_usuario, $id_categoria);
        }

        // d) agrupo y selecciono según nivel
        $grupos = $this->agruparPorNivel($preguntas);

        $preg = $this->elegirPorNivelUsuario($grupos, $nivelUsuario);
        return $preg;
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

    // Trae preguntas que el usuario no vio, de la categoría dada,
    // con estado activa/reportada (pero no las que él mismo reportó),
    private function getPreguntasNoVistas($id_usuario, $id_categoria): array
    {

        $sql = "
        SELECT p.*
          FROM preguntas p

          LEFT JOIN usuario_pregunta up
            ON p.id_pregunta = up.idPregunta
           AND up.idUsuario   = $id_usuario

          LEFT JOIN preguntas_reportadas pr
            ON p.id_pregunta      = pr.id_pregunta
           AND pr.id_reportador   = $id_usuario
           
        WHERE 
            up.idPregunta IS NULL                           
            AND pr.id_reporte IS NULL                       
            AND p.id_categoria  = $id_categoria                         
            AND p.estado IN ('activa','reportada')                               
    ";

        return $this->db->query($sql);
    }

    private function limpiarHistorialPreguntasVistas($id_usuario, $id_categoria)
    {
        $this->db->execute("
        DELETE up
          FROM usuario_pregunta up
          JOIN preguntas p ON up.idPregunta = p.id_pregunta
         WHERE up.idUsuario = $id_usuario
           AND p.id_categoria = $id_categoria
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
}