<?php

class PartidaController
{

    private $model;
    private $view;
    private $preguntaModel;

    public function __construct($model, $view, $preguntaModel)
    {
        $this->model = $model;
        $this->view = $view;
        $this->preguntaModel = $preguntaModel;
    }

    public function crearPartida()
    {
        $id_usuario = $_SESSION['usuario_id'] ?? null;

        // Si ya hay una partida en curso, no crear otra
        if (isset($_SESSION['id_partida'])) {
            header('Location: /ruleta/show');
            exit();
        }

        // Si no hay partida, crearla
        $_SESSION['puntaje'] = 0;
        $id_partida = $this->model->crearPartida($id_usuario);
        $_SESSION['id_partida'] = $id_partida;

        header('Location: /ruleta/show');
        exit();
    }

    public function jugar()
    {

        $id_usuario = $_SESSION['usuario_id'] ?? null;

        // Verificar si ya tiene una pregunta
        if (isset($_SESSION['id_pregunta'])) {
            $nombre_categoria = $_SESSION['nombre_categoria'] ?? null;
            $id_pregunta = $_SESSION['id_pregunta'];
            $pregunta_texto = $_SESSION['pregunta'];
            $respuestas = $this->model->getRespuestasPorIdPreguntaAleatoria($id_pregunta);

            $fondo = $this->model->getColorCategoria($nombre_categoria);
            $foto = $this->model->getFotoCategoria($nombre_categoria);
            $user = $this->model->getUsuario($id_usuario);

            $tiempo_restante = $this->model->getTiempo();
            $this->view->render("partida", [
                'title' => 'Partida',
                'usuario_id' => $id_usuario,
                'pregunta' => $pregunta_texto,
                'categoria' => $nombre_categoria,
                'respuestas' => $respuestas,
                'id_partida' => $_SESSION['id_partida'],
                'tiempo_restante' => $tiempo_restante,
                'fondo' => $fondo,
                'foto' => $foto,
                'user' => $user
            ]);
            return;
        }

        $categoria = $_SESSION['categoria'];
        $nombre_categoria = $categoria["nombre"];

        $pregunta = $this->model->obtenerPregunta($id_usuario, $categoria["id_categoria"]);

        $id_pregunta = $pregunta["id_pregunta"];

        $pregunta_texto = $pregunta["pregunta"];

        $respuestas = $this->model->getRespuestasPorIdPreguntaAleatoria($id_pregunta);

        $_SESSION['id_pregunta'] = $id_pregunta;
        $_SESSION['pregunta'] = $pregunta_texto;
        $_SESSION['nombre_categoria'] = $nombre_categoria;
        $_SESSION['opciones'] = $respuestas;
        $_SESSION['inicio_pregunta'] = time();

        $fondo = $this->model->getColorCategoria($nombre_categoria);
        $foto = $this->model->getFotoCategoria($nombre_categoria);
        $user = $this->model->getUsuario($id_usuario);

        $tiempo_restante = $this->model->getTiempo();

        // Se le entrego la pregunta, actualizar datos bdd
        $this->model->incrementarEntregas($id_pregunta);
        $this->model->incrementarEntregadasUsuario($id_usuario);
        $this->model->marcarPreguntaComoVista($id_usuario, $id_pregunta);

        $this->view->render("partida", [
            'title' => 'Partida',
            'usuario_id' => $id_usuario,
            'pregunta' => $pregunta_texto,
            'id_pregunta' => $id_pregunta,
            'categoria' => $nombre_categoria,
            'respuestas' => $respuestas,
            'id_partida' => $_SESSION['id_partida'],
            'tiempo_restante' => $tiempo_restante,
            'fondo' => $fondo,
            'foto' => $foto,
            'user' => $user
        ]);

    }

    public function responder()
    {
        $this->detectarTrampaYExpulsar();

        $id_usuario = $_SESSION['usuario_id'] ?? null;
        $id_pregunta = $_SESSION['id_pregunta'];
        $id_partida = $_SESSION['id_partida'];

        $inicio = $_SESSION['inicio_pregunta'] ?? null;
        $tiempo_agotado = $inicio !== null && (time() - $inicio) > 10;

        $respuestaCorrecta = false;
        $texto = $tiempo_agotado ? '¡TIEMPO AGOTADO!' : '¡INCORRECTA!';
        $color = $tiempo_agotado ? 'text-warning' : 'text-danger';
        $id_respuesta = $_POST['id_respuesta'] ?? null;

        // Se acabó el tiempo o no respondió
        if ($tiempo_agotado || $id_respuesta === -1) {
            $this->model->crearRegistroPreguntaRespondida($id_partida, $id_pregunta, null, 0);
            $this->finalizarPartida();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_respuesta'])) {
            $respuestas = $this->model->getRespuestasPorPregunta($id_pregunta);

            foreach ($respuestas as $respuesta) {
                if ($respuesta['esCorrecta']) {
                    if ($respuesta['id_respuesta'] == $_POST['id_respuesta']) {
                        $respuestaCorrecta = true;
                        $texto = "¡CORRECTA!";
                        $color = 'text-success';

                        $this->model->incrementoPuntaje($id_partida);
                        $this->model->incremetoPreguntaRespondidaCorrectamente($id_partida);
                        $this->model->crearRegistroPreguntaRespondida($id_partida, $id_pregunta, $respuesta['id_respuesta'], 1);
                        $this->model->acumularPuntajeUsuario($id_usuario);
                        $this->model->incrementarCorrectasPregunta($id_pregunta);
                        $this->model->incrementarCorrectasUsuario($id_usuario);
                        break;
                    }
                }
            }

            if (!$respuestaCorrecta) {
                $this->model->crearRegistroPreguntaRespondida($id_partida, $id_pregunta, $_POST['id_respuesta'], 0);
                $this->finalizarPartida();
            }
        } else {
            echo 'error';
        }

        $_SESSION['cantidad'] = intval($this->model->getCantidadDePreguntas($id_partida));
        $this->mostrarVistaRespuesta($id_usuario, $id_pregunta, $respuestaCorrecta, $texto, $color, false);
    }

    public function reportarPregunta()
    {
        $idPregunta = (int)$_POST['id_pregunta'];
        $idUsuario = $_SESSION['usuario_id'] ?? null;
        $idPartida = $_SESSION['id_partida'] ?? null;
        $motivo = trim($_POST['motivo'] ?? '') ?: 'Sin motivo especificado';

        $this->preguntaModel->insertarReportePregunta($idPregunta, $idUsuario, $motivo);
        $this->preguntaModel->actualizarEstadoPregunta($idPregunta, 'reportada');

        // Finalizar la partida si aun esta activa
        if ($idPartida !== null) {
            $this->finalizarPartida();
        }

        $this->view->render("reporteCreado", [
            'title' => 'Reporte enviado',
            'mensaje' => 'Gracias por reportar la pregunta. Será revisada por un editor.',
            'puntaje' => $_SESSION['puntaje'] ?? 0,
            'cantidad' => $_SESSION['cantidad'] ?? 0
        ]);
    }

    private function mostrarVistaRespuesta($id_usuario, $id_pregunta, $respuestaCorrecta, $texto, $color, $reportado)
    {
        $respuestas = $this->model->getRespuestasPorPregunta($id_pregunta);

        foreach ($respuestas as &$respuesta) {
            $respuesta['id'] = $respuesta['id_respuesta'];
            $respuesta['texto_respuesta'] = $respuesta['respuesta'];

            if ($respuesta['esCorrecta']) {
                $respuesta['clase'] = 'bg-success';
            } else {
                $respuesta['clase'] = ($respuesta['id_respuesta'] == ($_POST['id_respuesta'] ?? null))
                    ? 'bg-danger'
                    : 'bg-light';
            }

            $respuesta['disabled'] = true;
        }

        $fondo = $this->model->getColorCategoria($_SESSION['nombre_categoria']);
        $foto = $this->model->getFotoCategoria($_SESSION['nombre_categoria']);
        $user = $this->model->getUsuario($id_usuario);

        $this->view->render("partida", [
            'title' => 'Partida',
            'usuario_id' => $id_usuario,
            'pregunta' => $_SESSION['pregunta'],
            'respuestas' => $respuestas,
            'categoria' => $_SESSION['nombre_categoria'],
            'correcto' => $respuestaCorrecta,
            'respondido' => true,
            'texto' => $texto,
            'color' => $color,
            'puntaje' => $_SESSION['puntaje'],
            'ocultar' => 'display:none',
            'foto' => $foto,
            'fondo' => $fondo,
            'user' => $user,
            'reportado' => $reportado,
            'id_pregunta' => $id_pregunta
        ]);

        $this->limpiarSesionPregunta();
    }

    private function limpiarSesionPregunta()
    {
        unset(
            $_SESSION['categoria'],
            $_SESSION['nombre_categoria'],
            $_SESSION['id_pregunta'],
            $_SESSION['pregunta'],
            $_SESSION['inicio_pregunta']
        );
    }

    private function detectarTrampaYExpulsar()
    {
        if (!isset($_SESSION['id_pregunta'])) {
            if (!empty($_SESSION['id_partida'])) {
                $this->model->actualizarFechaPartidaFinalizada($_SESSION['id_partida']);
            }
            session_destroy();
            header("Location: /login/show?error=trampa");
            exit;
        }
    }

    private function finalizarPartida()
    {
        if (isset($_SESSION['id_partida'])) {
            $this->model->actualizarFechaPartidaFinalizada($_SESSION['id_partida']);
            unset($_SESSION['id_partida']);
        }
    }
}