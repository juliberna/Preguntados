<?php

class HomeController
{
  private $view;

  public function __construct($view)
  {
    $this->view = $view;
  }

  public function show()
  {
    if (!$this->isLogueado()) {
      header("Location: /login/show");
      exit();
    }

    $this->view->render("home", [
      'title' => 'Home Usuario',
      'css' => '<link rel="stylesheet" href="/public/css/perfil.css">'
    ]);
  }

  private function isLogueado(): bool
  {
    return $_SESSION['usuario_id'] != null;
  }
}
