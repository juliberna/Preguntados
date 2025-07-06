<?php

class PerfilController
{
    private $model;
    private $view;

    public function __construct($model, $view)
    {
        $this->model = $model;
        $this->view = $view;
    }

    public function show()
    {
        $id_usuario = $_GET['idUsuario'] ?? ($_SESSION['usuario_id'] ?? null);

        if (!$id_usuario) {
            $this->redirectTo("/error");
        }

        $datos = $this->model->getDatos($id_usuario);

        if (empty($datos) || in_array($datos[0]['rol'], ['admin', 'editor'])) {
            $this->redirectTo("/perfil?idUsuario=" . $_SESSION['usuario_id']);
        }

        $usuario = $datos[0];

        $cantidadPartidas = $this->model->getCantidadPartidasJugadas($id_usuario);
        $tieneEstadisticas = $cantidadPartidas !== "0";

        $totalPreguntas = $this->model->getTotalPreguntasRespondidas($id_usuario);
        $porcentajeAcierto = $this->model->getPorcentajeAcierto($id_usuario);
        $mayorPuntaje = $this->model->getMayorPuntajePartida($id_usuario);
        $categoriasDestacadas = $this->model->getCategoriasDestacadas($id_usuario);
        $posicionRanking = $this->model->getPosicionRanking($id_usuario);

        // Construir la URL del perfil
        $host = $_SERVER['HTTP_HOST'];
        $es_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $protocolo = $es_https ? 'https' : 'http';
        $url_perfil = "$protocolo://$host/perfil?idUsuario=$id_usuario";

        // Renderizar vista
        $this->view->render("perfil", array_merge(
            [
                'title' => 'Perfil Usuario',
                'url_perfil' => $url_perfil,
                'cantidad_partidas' => $cantidadPartidas,
                'total_preguntas' => $totalPreguntas,
                'porcentaje_acierto' => $porcentajeAcierto,
                'mayor_puntaje' => $mayorPuntaje,
                'categorias_destacadas' => $categoriasDestacadas,
                'posicion_ranking' => $posicionRanking,
                'tiene_estadisticas' => $tieneEstadisticas
            ],
            $usuario
        ));
    }

    private function redirectTo($str)
    {
        header('Location: ' . $str);
        exit();
    }

    private function isLogueado(): bool
    {
        return !($_SESSION['usuario_id'] === null);
    }

}