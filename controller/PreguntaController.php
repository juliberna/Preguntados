<?php

class PreguntaController
{
    private $model;
    private $view;

    public function __construct($model, $view)
    {
        $this->model = $model;
        $this->view = $view;
    }

    // El usuario hace clic en "Ver Pregunta"
    public function dameUna()
    {
        // Validacion de que este logueado
        if (!isset($_SESSION["usuario_id"])) {
            header("Location: /login/show");
            exit();
        }

        $id_usuario = $_SESSION["usuario_id"];

        // Obtengo el id de la partida activa
        $id_partida = $_SESSION["id_partida"] ?? null;

        // Si no hay partida activa, llevar a iniciar partida
        if (!$id_partida) {
            $this->redirectTo("/partida/iniciarPartida");
        }

        // Si ya hay una pregunta entregada sin responder:
        if (isset($_SESSION["pregunta_actual"])) {
            $pregunta = $_SESSION["pregunta_actual"];
            $respuestas = $this->model->obtenerRespuestas($pregunta["id_pregunta"]);
            $tiempo_entrega = $_SESSION["inicio_pregunta"];
            $tiempo_transcurrido = time() - $tiempo_entrega;

            // Devolver la misma pregunta con el tiempo transcurrido
            $this->view->render("pregunta", [
                'pregunta' => $pregunta,
                'respuestas' => $respuestas,
                'categoria' => $_SESSION["categoria"],
                'tiempo_transcurrido' => $tiempo_transcurrido,
                'tiempo_limite' => 10
            ]);
            return;
        }

        // Si no hay pregunta activa, buscar nueva
        // Buscar pregunta adecuada (no repetida, según categoria y dificultad)
        $id_categoria = $_SESSION["categoria"]["id_categoria"];
        $pregunta = $this->model->obtenerPregunta($id_usuario, $id_categoria);
        $respuestas = $this->model->obtenerRespuestas($pregunta["id_pregunta"]);

        // Actualiza la tabla "Preguntas" el campo entregada
        $this->model->incrementarEntregas($pregunta["id_pregunta"]);
        // Actualiza la tabla "Usuario_pregunta" para no repetir preguntas
        $this->model->marcarPreguntaComoVista($id_usuario, $pregunta["id_pregunta"]);
        // Actualizar usuario: entregas totales
        $this->model->incrementarEntregadasUsuario($id_usuario);

        // Guardar en sesión la pregunta y el instante actual
        $_SESSION["pregunta_actual"] = $pregunta;
        $_SESSION["inicio_pregunta"] = time();

        // Guardar que esta pregunta se entregó en esta partida
        $this->model->registrarPreguntaEnPartida($id_partida, $pregunta["id_pregunta"]);

        // Mostrar pregunta con sus 4 respuestas y el tiempo
        $this->view->render("pregunta", [
            'title' => 'Pregunta',
            'pregunta' => $pregunta,
            'categoria' => $_SESSION["categoria"],
            'respuestas' => $respuestas,
            'tiempo_transcurrido' => 0,
            'tiempo_limite' => 10
        ]);
    }

    public function responder()
    {
        $id_usuario = $_SESSION["usuario_id"];
        $id_partida = $_SESSION["id_partida"];
        // Ver el puntaje (No va a ser necesario es solo para pruebas)
        $puntaje = $_SESSION["puntaje"];

        // Si no tengo una pregunta entregada no puedo tener una respuesta, esta haciendo trampa
        // Le cierro la partida y lo redirijo al home
        if (!isset($_SESSION["pregunta_actual"])) {
            // El usuario está intentando responder una pregunta que no le fue entregada
            // Terminar partida
            $this->redirectTo("/partida/finalizarPartida");
        }

        // Verificar tiempo límite
        $tiempo_entrega = $_SESSION["inicio_pregunta"];
        $tiempo_actual = time();
        if ($tiempo_actual - $tiempo_entrega > 10) {
            // Se pasó el tiempo, termina la partida
            $this->redirectTo("/partida/finalizarPartida");
        }

        // La pregunta que respondio el usuario
        $pregunta = $_SESSION["pregunta_actual"];
        $respuestas = $this->model->obtenerRespuestas($pregunta["id_pregunta"]);

        // Ver si la respuesta es correcta
        $idRespuestaSeleccionada = $_GET["idRespuesta"] ?? null;
        $es_correcta = $this->model->validarRespuesta($idRespuestaSeleccionada);

        if ($es_correcta) {
            // Actualizar
            $this->model->sumarPunto($id_usuario);
            $puntaje = $this->model->getPuntaje($id_usuario);
            $_SESSION['puntaje'] = $puntaje;

            // Actualizar estadisticas
            $this->model->incrementarCorrectasPregunta($pregunta["id_pregunta"]);
            $this->model->incrementarCorrectasUsuario($id_usuario);
        }

        // Guardar la respuesta en partida_pregunta
        $this->model->guardarRespuestaEnPartida($id_partida, $pregunta["id_pregunta"], $idRespuestaSeleccionada, $es_correcta);

        // Recorro las respuestas de esa pregunta para saber luego en el front cual fue elegida por el usuario
        foreach ($respuestas as &$r) {
            // Agrega el atributo isSelected como true o false,
            // de acuerdo a si matchea el id de la respuesta seleccionada
            $r['isSelected'] = ($r['id_respuesta'] === $idRespuestaSeleccionada);
        }

        $this->view->render("respuesta", [
            'pregunta' => $pregunta,
            'categoria' => $_SESSION["categoria"],
            'respuestas' => $respuestas,
            'idRespuestaSeleccionada' => $idRespuestaSeleccionada,
            'correcta' => $es_correcta
        ]);
        echo "Puntaje: $puntaje";

        // Limpiar los datos de la pregunta en la session
        unset($_SESSION["pregunta_actual"], $_SESSION["inicio_pregunta"], $_SESSION["categoria"]);
    }

    private
    function redirectTo($str)
    {
        header("Location: " . $str);
        exit();
    }
}
