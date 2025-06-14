<?php

class RuletaController
{

   private $view;
    private $model;

   public function __construct($model, $view){
        $this->view = $view;$this->model = $model;
    }

    public function show(){

       $id_usuario = $_SESSION['usuario_id'] ?? null;

       $user = $this->model->getUsuario($id_usuario);

        $this->view->render("ruleta", [
            'title' => 'Ruleta',
            'css' => '<link rel="stylesheet" href="/public/css/styles.css">',
            'usuario_id' => $id_usuario,
            'user' => $user
        ]);
    }
}