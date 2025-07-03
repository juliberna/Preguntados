<?php

class RuletaController
{

   private $view;
    private $model;

   public function __construct($model, $view){
        $this->view = $view;
        $this->model = $model;
    }

    public function show(){

        $categorias = $this->model->getCategorias();

        $repeticiones = 5;
        $categoriasRepetidas = [];
        for ($i = 0; $i < $repeticiones; $i++) {
            $categoriasRepetidas = array_merge($categoriasRepetidas, $categorias);
        }

        $yaGiro = isset($_SESSION['categoria']);

        $this->view->render("ruleta", [
            'title' => 'Ruleta',
            'categorias' => $categoriasRepetidas,
            'yaGiro' => $yaGiro
        ]);
    }

    public function girar()
    {
        header('Content-Type: application/json');

        if (isset($_SESSION['categoria'])) {
            echo json_encode(['error' => 'Ya giraste la ruleta.'], JSON_THROW_ON_ERROR);
            return;
        }

        $categoria = $this->model->getCategoriaAleatoria();
        $_SESSION["categoria"] = $categoria;

        $categorias = $this->model->getCategorias();
        $repeticiones = 5;
        $totalCategorias = count($categorias);

        $indiceOriginal = array_search(
            $categoria["id_categoria"],
            array_column($categorias, "id_categoria"),
            true
        );

        $vuelta = rand(2, $repeticiones - 2);
        $posicionGanadoraExtendida = $indiceOriginal + $vuelta * $totalCategorias;

        echo json_encode(['posicion' => $posicionGanadoraExtendida], JSON_THROW_ON_ERROR);
    }
}