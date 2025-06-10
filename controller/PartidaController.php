<?php

class PartidaController
{
    private $view;
    private $categoriaModel;

    private $partidaModel;

    public function __construct($partidaModel, $categoriaModel, $view)
    {

        $this->partidaModel = $partidaModel;
        $this->categoriaModel = $categoriaModel;
        $this->view = $view;
    }

    // El usuario hace clic en Jugar
    public function iniciarPartida()
    {
        // Esto se usa para asignar la partida a ese usuario cuando se cree
        $id_usuario = $_SESSION["usuario_id"];

        // Crear la partida de ese usuario
        $id_partida = $this->partidaModel->crearPartida($id_usuario);
        $_SESSION["id_partida"] = $id_partida;

        header("Location: /partida/jugar");
        exit();
    }

    public function jugar()
    {
        $categorias = $this->categoriaModel->getCategorias();

        $mostrarBoton = true;

        if (!isset($_SESSION["categoria"])) {
            $categoria = $this->categoriaModel->getCategoriaAleatoria();
            $_SESSION["categoria"] = $categoria;
            $mostrarBoton = false;
        }

        // Esto es para iniciar el puntaje
        if (!isset($_SESSION["puntaje"])) {
            $_SESSION["puntaje"] = 0;
        }

        $indiceCategoria = array_search(
            $_SESSION["categoria"]["id_categoria"],
            array_column($categorias, "id_categoria"),
            true
        );

        $this->view->render("ruleta", [
            'title' => 'Ruleta',
            'css' => '<link rel="stylesheet" href="/public/css/perfil.css">',
            'categorias' => $categorias,
            'posicionGanadora' => $indiceCategoria,
            'mostrarBoton' => $mostrarBoton,
        ]);
    }

    public function finalizarPartida()
    {
        // Registrar en BD la finalización de la partida
        $id_partida = $_SESSION["id_partida"];
        $this->partidaModel->finalizarPartida($id_partida, $_SESSION["puntaje_partida"]);

        // Limpiar datos de la partida,categoria y pregunta actual en sesión
        unset($_SESSION["id_partida"], $_SESSION["categoria"], $_SESSION["pregunta_actual"], $_SESSION["inicio_pregunta"], $_SESSION["puntaje_partida"]);

        // Redirigir al home
        header("Location: /home");
        exit();

    }
}
