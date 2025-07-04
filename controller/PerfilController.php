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

      $datos = $this->model->getDatos($id_usuario);
      $cantidadPartidas = $this->model->getCantidadPartidasJugadas($id_usuario);
      $totalPreguntas = $this->model->getTotalPreguntasRespondidas($id_usuario);
      $porcentajeAcierto = $this->model->getPorcentajeAcierto($id_usuario);
      $mayorPuntaje = $this->model->getMayorPuntajePartida($id_usuario);
      $categoriasDestacadas = $this->model->getCategoriasDestacadas($id_usuario);
      $posicionRanking = $this->model->getPosicionRanking($id_usuario);

      if (!empty($datos) && is_array($datos)) {
          $usuario = $datos[0];
      } else {
          $usuario = ['nombre_usuario' => 'Invitado'];
      }

      // Obtiene el host dinamico para no estar cambiandolo manualmente
      $host = $_SERVER['HTTP_HOST'];
      $es_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
      $protocolo = $es_https ? 'https' : 'http';

      $url_perfil = "$protocolo://$host/perfil?idUsuario=$id_usuario";

      $this->view->render("perfil", array_merge(
          [
              'title' => 'Perfil Usuario',
              'url_perfil' => $url_perfil,
              'cantidad_partidas' => $cantidadPartidas,
              'total_preguntas' => $totalPreguntas,
              'porcentaje_acierto' => $porcentajeAcierto,
              'mayor_puntaje' => $mayorPuntaje,
              'categorias_destacadas' => $categoriasDestacadas,
              'posicion_ranking' => $posicionRanking
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