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
    if (!$this->isLogueado()) {
      $this->redirectTo("/login/show");
    }

    $id_usuario = $_SESSION['usuario_id'] ?? null;

    $datos = $this->model->getDatos($id_usuario);

    if (!empty($datos) && is_array($datos)) {
      $usuario = $datos[0];
    } else {
      $usuario = ['nombre_usuario' => 'Invitado'];
    }

    $this->view->render("perfil", array_merge([
      'title' => 'Perfil Usuario',
      'css' => '<link rel="stylesheet" href="/public/css/perfil.css">'
    ], $usuario));
  }

  private function redirectTo($str)
  {
    header('Location: ' . $str);
    exit();
  }

  private function isLogueado(): bool
  {
    return $_SESSION['usuario_id'] != null;
  }

}
