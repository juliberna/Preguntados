<?php

class LoginController
{
  private $model;
  private $view;
  private $loginUrl = "/login/show";

  public function __construct($model, $view)
  {
    $this->model = $model;
    $this->view = $view;
  }

  public function show()
  {
    $error = $_SESSION['login_error'] ?? null;
    unset($_SESSION['login_error']);

    $this->view->render("login", [
      'title' => 'Iniciar sesión',
      'css' => '<link rel="stylesheet" href="/public/css/styles.css" >',
      'error' => $error
    ]);
  }

  public function loguearse()
  {
    $email = $_POST["email"];
    $password = $_POST["password"];

    $usuario = $this->model->buscarUsuarioPorEmail($email);

    if (!$usuario || !password_verify($password, $usuario["contrasena_hash"])) {
      $_SESSION['login_error'] = 'Correo o contraseña incorrectos';
      $this->redirectTo($this->loginUrl);
    }

    if (!$usuario["es_validado"]) {
      $_SESSION['login_error'] = 'Tu cuenta aún no fue validada. Por favor revisá tu correo.';
      $this->redirectTo($this->loginUrl);
    }

    $_SESSION["usuario_id"] = $usuario["id_usuario"];
    $this->redirectTo("/");
  }


  public
  function logout()
  {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      session_unset();
      session_destroy();
      $this->redirectTo($this->loginUrl);
    }
  }

  private
  function redirectTo($str)
  {
    header('Location: ' . $str);
    exit();
  }
}
