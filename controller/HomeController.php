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
      $this->view->render("home", [
          'title' => 'Home Usuario',
          'css' => '<link rel="stylesheet" href="/public/css/perfil.css">'
      ]);
  }
}
