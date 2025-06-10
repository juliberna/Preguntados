<?php
session_start();

$rutasPublicas = [
    'login/show',
    'login/procesar',
    'registro/show',
    'registro/procesar',
    'registro/success',
    'registro/verificar'
];

$controller = $_GET['controller'] ?? null;
$method = $_GET['method'] ?? null;

$ruta = "$controller/$method";

if (!isset($_SESSION['usuario_id']) && !in_array($ruta, $rutasPublicas, true)) {
    header("Location: /login/show");
    exit();
}

require_once "Configuration.php";
$configuration = new Configuration();
$router = $configuration->getRouter();

$router->go($controller, $method);
