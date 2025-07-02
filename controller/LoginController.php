<?php

class LoginController
{
    private $model;
    private $view;
    private $rolModel;

    public function __construct($model, $view, $rolModel)
    {
        $this->model = $model;
        $this->view = $view;
        $this->rolModel = $rolModel;
    }

    public function show()
    {
        $error = $_SESSION['login_error'] ?? null;

        if (isset($_GET['error']) && $_GET['error'] === 'trampa') {
            $error = "Has sido desconectado por intento de trampa.";
        }

        unset($_SESSION['login_error']);

        $this->view->render("login", [
            'title' => 'Iniciar sesión',
            'error' => $error
        ]);
    }

    public function procesar()
    {
        $email = $_POST["email"];
        $password = $_POST["password"];

        $usuario = $this->model->buscarUsuarioPorEmail($email);

        if (!$usuario || !password_verify($password, $usuario["contrasena_hash"])) {
            $_SESSION['login_error'] = 'Correo o contraseña incorrectos';
            $this->redirectTo("/login/show");
            return;
        }

        if (!$usuario["es_validado"]) {
            $_SESSION['login_error'] = 'Tu cuenta aún no fue validada. Por favor revisá tu correo.';
            $this->redirectTo("/login/show");
            return;
        }

        $_SESSION["usuario_id"] = $usuario["id_usuario"];
        $_SESSION["nombre_usuario"] = $usuario["nombre_usuario"];

        $roles = $this->rolModel->getRolesDelUsuario($usuario['id_usuario']);
        $_SESSION['roles'] = $roles;
        $_SESSION['esEditor'] = in_array('editor', $roles, true);
        $_SESSION['esAdmin'] = in_array('admin', $roles, true);
        $_SESSION['esJugador'] = in_array('jugador', $roles, true);

        if (in_array('editor', $roles)) {
            $this->redirectTo("/editor/show");
            return;
        }
        $this->redirectTo("/lobby/show");
    }


    public
    function logout()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            session_unset();
            session_destroy();
            $this->redirectTo("/login/show");
        }
    }


    private
    function redirectTo($str)
    {
        header('Location: ' . $str);
        exit();
    }

}