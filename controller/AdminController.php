<?php

class AdminController{

    private $model;
    private $view;


    public function __construct($model, $view)
    {
        $this->model = $model;
        $this->view = $view;

    }

    public function show()
    {
        $filtro = $_GET['filtro'] ?? 'mes';

        switch ($filtro) {
            case 'hoy':
                $desde = date('Y-m-d 00:00:00');
                $hasta = date('Y-m-d 23:59:59');
                break;
            case 'semana':
                $desde = date('Y-m-d 00:00:00', strtotime('-7 days'));
                $hasta = date('Y-m-d 23:59:59');
                break;
            case 'anio':
                $desde = date('Y-01-01 00:00:00');
                $hasta = date('Y-12-31 23:59:59');
                break;
            case 'mes':
            default:
                $desde = date('Y-m-01 00:00:00');
                $hasta = date('Y-m-t 23:59:59');
                break;
        }

        $total_jugadores = $this->model->obtenerTotalUsuariosPorFecha($desde, $hasta);


        $this->view->render("panelAdmin", [
            'title' => 'Dashboard',
            'total_jugadores' => $total_jugadores,
        ]);
    }

}