<?php

class RegistroController
{
  private $model;
  private $view;
  private $emailSender;

  public function __construct($registroModel, $view, $emailSender)
  {
    $this->model = $registroModel;
    $this->view = $view;
    $this->emailSender = $emailSender;
  }

  public function show()
  {
    $this->view->render("register", [
      'title' => 'Registrarse',
      'css' => '<link rel="stylesheet" href="/public/css/styles.css">'
    ]);
  }

  public function procesar()
  {
    $nombreCompleto = $_POST['nombre'];
    $fechaNac = $_POST['fecha-nac'];
    $correo = $_POST['email'];
    $usuario = $_POST['usuario'];
    $contrasenaHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $sexo = $_POST['sexo'];
    $sexoId = ($sexo === "masculino") ? 1 : (($sexo == "femenino") ? 2 : 3);

    // Hardcodeado siempre sera Buenos Aires y Argentina hasta tener el mapa
    $idPais = 1;
    $idCiudad = 1;

    $fotoNombre = null;
    if (isset($_FILES['foto']) && is_uploaded_file($_FILES['foto']['tmp_name'])) {
      $fotoNombre = $_FILES['foto']['name'];
      $temp = $_FILES['foto']['tmp_name'];
      $destino = __DIR__ . '/../public/uploads/' . $fotoNombre;

      if (!is_dir(dirname($destino))) {
        mkdir(dirname($destino), 0755, true);
      }

      move_uploaded_file($temp, $destino);
    }

    /*
      Dentro del model genero un random y lo guardo en la base
      Cuando doy de alta el usuario en la bdd con un valor random y un campo
      validado = false
    */

    $result = $this->model->registrarUsuario(
      $nombreCompleto,
      $fechaNac,
      $sexoId,
      $idPais,
      $idCiudad,
      $correo,
      $contrasenaHash,
      $usuario,
      $fotoNombre
    );

    $idUsuario = $result["idUsuario"];

    if ($idUsuario == null) {
      $this->renderErrorView('Error de Registro', 'No se pudo crear tu cuenta. Intentá de nuevo más tarde.');
      return; // Estoy en duda si esta bien hacer esto
    }

    // Cuando lo doy de alta, voy a mandar un correo
    $body = $this->generateEmailBodyFor($result["nombreUsuario"], $result["token"], $result["idUsuario"]);
    $mailOK = $this->emailSender->send($result["email"], $body);

    if (!$mailOK) {
      $this->renderErrorView('Error de Envío de Email', 'Tu cuenta se creó, pero no pudimos enviarte el correo de validación.');
      return; // Estoy en duda si esta bien hacer esto
    }

    $_SESSION['id_usuario'] = $idUsuario;
    $_SESSION['email'] = $result["email"];
    $this->redirectTo("/registro/success");
  }

  public function success()
  {
    $email = $_SESSION['email'];
    $this->view->render("registroSuccess", [
      'title' => 'Validacion',
      'css' => '<link rel="stylesheet" href="/public/css/styles.css">',
      'email' => $email
    ]);
  }

  // Endpoint que llama el link que le llega por correo al usuario
  public function verificar()
  {
    $idVerificador = $_GET["idVerificador"];
    $idUsuario = $_GET["idUsuario"];

    // EN LA BASE TENGO PARA ESE USUARIO UN VALOR RANDOM
    // Si coincide el random con el idUsuario de la bdd, voy a cambiar el validado a true
    $verificado = $this->model->verificarEmailUsuario($idVerificador, $idUsuario);

    if (!$verificado) {
      $this->renderErrorView('Error de validación', 'Ocurrio un error en la validacion del correo. Verifica tus credenciales.');
      return; // Estoy en duda si esta bien hacer esto
    }

    $this->view->render("verificacionSuccess", [
      'title' => '¡Cuenta verificada!',
      'css' => '<link rel="stylesheet" href="/public/css/styles.css">',
      'message' => 'Tu correo ha sido validado correctamente. Ya podés ingresar al sistema.'
    ]);
  }

  private function redirectTo($str)
  {
    header('Location: ' . $str);
    exit();
  }

  private function generateEmailBodyFor($userName, $token, $idUsuario)
  {
    $url = "http://localhost/registro/verificar?idUsuario=$idUsuario&idVerificador=$token";
    return "
    <body>
      <p>Hola $userName,</p>
      <p>Gracias por registrarte. Por favor, valida tu cuenta haciendo click en el siguiente enlace:</p>
      <p><a href='$url'>Validar cuenta</a></p>
    </body>
  ";
  }

  private function renderErrorView($title, $message)
  {
    $this->view->render("error", [
      'title' => $title,
      'css' => '<link rel="stylesheet" href="/public/css/styles.css">',
      'message' => $message
    ]);
  }
}
