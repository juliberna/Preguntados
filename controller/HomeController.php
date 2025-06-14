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
    if ($_SESSION['esEditor']) {
      $this->view->render("homeEditor", [
        'title' => 'Panel Editor'
      ]);
    } else {
      $this->view->render("home", [
        'title' => 'Home Jugador'
      ]);
    }
  }
}
