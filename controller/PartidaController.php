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
        $_SESSION['puntaje'] = 0;

        //crear partida
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
                'title' => 'Ruleta',
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

        $categoria = $this->model->getCategoriaAleatoria();

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
        $id_usuario = $_SESSION['usuario_id'] ?? null;

        $inicio = $_SESSION['inicio_pregunta'] ?? null;
        if ($inicio === null || (time() - $inicio) > 10) {
            $this->model->actualizarFechaPartidaFinalizada($_SESSION['id_partida']);
            unset($_SESSION['inicio_pregunta']);
            header("Location: /perdio/show");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_respuesta'])) {
            $id_pregunta = $_SESSION['id_pregunta'];
            $id_partida = $_SESSION['id_partida'];
            $respuestas = $this->model->getRespuestasPorPregunta($id_pregunta);

            $respuestaCorrecta = false;
            $texto = "¡INCORRECTA!";
            $color = 'text-danger';

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
                    }
                }
            }

            if (!$respuestaCorrecta) {
                $this->model->actualizarFechaPartidaFinalizada($id_partida);
                $this->model->crearRegistroPreguntaRespondida($id_partida, $id_pregunta, $_POST['id_respuesta'], 0);
            }

            $_SESSION['cantidad'] = intval($this->model->getCantidadDePreguntas($id_partida));
            $this->mostrarVistaRespuesta($id_usuario, $id_pregunta, $respuestaCorrecta, $texto, $color, false);
        } else {
            echo 'error';
        }
    }

    public function reportarPregunta() {
        $idPregunta = (int)$_POST['id_pregunta'];
        $idUsuario = $_SESSION['usuario_id'] ?? null;
        $idPartida = $_SESSION['id_partida'] ?? null;

        $motivo = trim($_POST['motivo'] ?? '') ? : 'Sin motivo especificado';

        $this->preguntaModel->insertarReportePregunta($idPregunta, $idUsuario, $motivo);
        $this->preguntaModel->actualizarEstadoPregunta($idPregunta, 'reportada');

        // Simular como si la hubiera respondido mal
        $this->model->actualizarFechaPartidaFinalizada($idPartida);

        $_SESSION['cantidad'] = intval($this->model->getCantidadDePreguntas($idPartida));

        // Mostrar la vista como incorrecta, con un mensaje personalizado
        $this->mostrarVistaRespuesta($idUsuario, $idPregunta, false, "Has reportado la pregunta. Se marcó como incorrecta.", "text-warning",true);
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
            'reportado' => $reportado
        ]);

        $this->limpiarSesionPregunta();
    }

    private function limpiarSesionPregunta()
    {
        unset(
            $_SESSION['nombre_categoria'],
            $_SESSION['id_pregunta'],
            $_SESSION['pregunta'],
            $_SESSION['inicio_pregunta']
        );
    }
}