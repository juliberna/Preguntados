<?php

class RegistroController
{
    private $model;
    private $ubicacionModel;
    private $view;
    private $emailSender;

    public function __construct($registroModel, $ubicacionModel, $view, $emailSender)
    {
        $this->model = $registroModel;
        $this->ubicacionModel = $ubicacionModel;
        $this->view = $view;
        $this->emailSender = $emailSender;
    }

    public function show()
    {
        $this->view->render("register", [
            'title' => 'Registrarse'
        ]);
    }

    public function pasoMapa()
    {
        $_SESSION['registro'] = [
            'nombre' => $_POST['nombre'],
            'fecha_nac' => $_POST['fecha-nac'],
            'email' => $_POST['email'],
            'usuario' => $_POST['usuario'],
            'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
            'sexo' => $_POST['sexo']
        ];

        if (isset($_FILES['foto']) && is_uploaded_file($_FILES['foto']['tmp_name'])) {
            $fotoTmpNombre = basename($_FILES['foto']['name']);
            $destinoTmp = __DIR__ . '/../public/uploads/tmp/' . $fotoTmpNombre; // ruta absoluta

            if (!is_dir(dirname($destinoTmp))) {
                mkdir(dirname($destinoTmp), 0755, true);
            }

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $destinoTmp)) {
                $_SESSION['foto_temp'] = $destinoTmp;
            }
        }

        $this->redirectTo("/registro/mapa");
    }

    public function mapa()
    {
        $this->view->render("mapaRegistro", [
            'title' => 'Elige tu ubicación'
        ]);
    }

    public function getUbicacion()
    {
        if (!isset($_GET['lat']) || !isset($_GET['lng'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Coordenadas no válidas']);
            return;
        }

        $lat = $_GET['lat'];
        $lng = $_GET['lng'];

        // Usar la API de Nominatim
        $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&zoom=10&addressdetails=1";

        $opts = [
            "http" => [
                "header" => "User-Agent: /1.0\r\n"
            ]
        ];
        $context = stream_context_create($opts);
        $response = file_get_contents($url, false, $context);

        if ($response === FALSE) {
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo obtener ubicación']);
            return;
        }

        $data = json_decode($response, true);

        $pais = $data['address']['country'] ?? 'Desconocido';
        $provincia = $data['address']['state'] ?? 'Desconocido';

        header('Content-Type: application/json');
        echo json_encode([
            'pais' => $pais,
            'provincia' => $provincia
        ]);
    }

    public function procesar()
    {
        // Recuperar datos del registro que estan en session
        $r = $_SESSION['registro'] ?? null;

        // Datos mapa (POST)
        $lat = $_POST['latitud'] ?? null;
        $lng = $_POST['longitud'] ?? null;

        // Obtener país y ciudad desde backend
        $ubicacion = $this->ubicacionModel->obtenerPaisYCiudadPorCoordenadas($lat, $lng);

        if (!$ubicacion || $ubicacion['pais'] === 'Desconocido' || $ubicacion['ciudad'] === 'Desconocido') {
            $this->view->render("mapaRegistro", [
                'title' => 'Elige tu ubicacion',
                'error' => 'Seleccioná una ubicación válida en el mapa.'
            ]);
            return;
        }

        // Crear / Obtener ids
        $idPais = $this->ubicacionModel->obtenerOCrearPais($ubicacion['pais']);
        $idCiudad = $this->ubicacionModel->obtenerOCrearCiudad($ubicacion['ciudad'], $idPais);

        $fotoNombre = null;
        if (isset($_SESSION['foto_temp']) && file_exists($_SESSION['foto_temp'])) {
            $fotoNombre = basename($_SESSION['foto_temp']);
            $destinoFinal = "public/uploads/" . $fotoNombre;
            rename($_SESSION['foto_temp'], $destinoFinal);
        }

        /*
          Dentro del model genero un random y lo guardo en la base
          Cuando doy de alta el usuario en la bdd con un valor random y un campo
          validado = false
        */

        $res = $this->model->registrarUsuario(
            $r['nombre'],
            $r['fecha_nac'],
            ($r['sexo'] === 'masculino' ? 1 : ($r['sexo'] === 'femenino' ? 2 : 3)),
            $idPais,
            $idCiudad,
            $r['email'],
            $r['password'],
            $r['usuario'],
            $fotoNombre,
            $lat,
            $lng
        );
        if (!$res['idUsuario']) {
            $this->renderErrorView('Error', 'No pudimos crear tu cuenta.');
            return;
        }

        // Asignar rol y enviar email
        $this->model->asignarRolJugador($res['idUsuario']);
        $body = $this->generateEmailBodyFor($res['nombreUsuario'], $res['token'], $res['idUsuario']);
        $this->emailSender->send($res['email'], $body);

        $_SESSION['id_usuario'] = $res['idUsuario'];
        $_SESSION['email'] = $res['email'];
        $this->redirectTo('/registro/success');
        unset($_SESSION['registro'], $_SESSION['foto_temp']);
    }

    public function success()
    {
        $email = $_SESSION['email'];
        $this->view->render("registroSuccess", [
            'title' => 'Validacion',
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
            return;
        }

        $this->view->render("verificacionSuccess", [
            'title' => '¡Cuenta verificada!',
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
            'message' => $message
        ]);
    }

    public function checkEmail()
    {
        $email = $_POST['email'] ?? '';

        if (empty($email)) {
            echo json_encode(['exists' => false]);
            return;
        }

        $exists = $this->model->existeEmail($email);
        header('Content-Type: application/json');
        echo json_encode(['exists' => $exists]);
    }

    public function checkUsuario()
    {
        $usuario = $_POST['usuario'] ?? '';

        if (empty($usuario)) {
            echo json_encode(['exists' => false]);
            return;
        }

        $exists = $this->model->existeUsuario($usuario);
        header('Content-Type: application/json');
        echo json_encode(['exists' => $exists]);
    }
}
