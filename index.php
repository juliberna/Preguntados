<?php
session_start();

// Rutas accesibles sin estar logueado
$rutasPublicas = [
    'login/show',
    'login/procesar',
    'registro/show',
    'registro/pasoMapa',
    'registro/mapa',
    'registro/getUbicacion',
    'registro/procesar',
    'registro/success',
    'registro/verificar',
    'login/logout',
    'perfil/show',
    'ranking/show',
    'login/logout',
    '/'
];

$controller = $_GET['controller'] ?? null;
$method = $_GET['method'] ?? null;

$ruta = "$controller/$method";

// Validacion General
if (!isset($_SESSION['usuario_id']) && !in_array($ruta, $rutasPublicas, true)) {
    header("Location: /");
    exit();
}

// Validacion por Roles
if (isset($_SESSION['usuario_id'])) {
    $roles = $_SESSION['roles'] ?? [];

    // Si la ruta es de editor y el usuario NO tiene el rol, lo sacamos.
    if (str_starts_with($ruta, 'editor/') && !in_array('editor', $roles, true)) {
        session_unset();
        session_destroy();
        header("Location: /login/show");
        exit();
    }
}

require_once "Configuration.php";
$configuration = new Configuration();
$router = $configuration->getRouter();

$router->go($controller, $method);