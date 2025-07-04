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
            case 'dia':
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
                $desde = date('Y-m-01 00:00:00', strtotime('-30 days'));
                $hasta = date('Y-m-t 23:59:59');
                break;
        }

        $filtros = [
            'filtro_dia' => $filtro === 'dia',
            'filtro_semana' => $filtro === 'semana',
            'filtro_mes' => $filtro === 'mes',
            'filtro_anio' => $filtro === 'anio',
        ];

        $total_jugadores = $this->model->obtenerTotalUsuarios();
        $PartidasJugadas = $this->model->obtenerPartidasJugadasPorFecha($desde, $hasta);
        $total_preguntas = $this->model->obtenerPreguntasActivasPorFecha($desde, $hasta);
        $distribucionEdad = $this->model->obtenerDistribucionPorRangoEdad($desde, $hasta);
        $total_jugadores_nuevos= $this->model->obtenerTotalUsuariosNuevosPorFecha($desde, $hasta);

        $edad_menor = 0;
        $edad_media = 0;
        $edad_mayor = 0;

        foreach ($distribucionEdad as $fila) {
            switch ($fila['rangoEdad']) {
                case 'Menor':
                    $edad_menor = (int)$fila['cantidad'];
                    break;
                case 'Mediana edad':
                    $edad_media = (int)$fila['cantidad'];
                    break;
                case 'Mayor':
                    $edad_mayor = (int)$fila['cantidad'];
                    break;
            }   }

        $distribucionGenero = $this->model->obtenerDistribucionPorGenero($desde, $hasta);
        $genero_femenino = 0;
        $genero_masculino = 0;
        $genero_otro = 0;

        foreach ($distribucionGenero as $fila) {
            switch ($fila['descripcion']) {
                case 'Femenino':
                    $genero_femenino = (int)$fila['cantidad'];
                    break;
                case 'Masculino':
                    $genero_masculino = (int)$fila['cantidad'];
                    break;
                case 'Prefiero no cargarlo':
                    $genero_otro = (int)$fila['cantidad'];
                    break;
            }   }

        $porcentaje_correctas = $this->model->obtenerPorcentajeGeneral($desde, $hasta);

        $json_grafico_correctas = [
            'fecha' => $porcentaje_correctas[0]['fecha'],
            'porcentajeIncorrectas' => $porcentaje_correctas[0]['porcentajeIncorrectas'],
            'porcentajeCorrectas' => $porcentaje_correctas[0]['porcentajeCorrectas']
        ];



        $this->view->render("panelAdmin", [
            'title' => 'Dashboard',
            'total_jugadores' => $total_jugadores,
            'total_jugadores_nuevos' => $total_jugadores_nuevos,
            'partidas_jugadas' => $PartidasJugadas,
            'total_preguntas' => $total_preguntas,
            'edad_menor' => $edad_menor,
            'edad_media' => $edad_media,
            'edad_mayor' => $edad_mayor,
            'genero_femenino' => $genero_femenino,
            'genero_masculino' => $genero_masculino,
            'genero_otro' => $genero_otro,
            'filtro_Actual' => $filtro,
            'filtro_dia' => $filtro === 'dia',
            'filtro_semana' => $filtro === 'semana',
            'filtro_mes' => $filtro === 'mes',
            'filtro_anio' => $filtro === 'anio',
            'porcentaje_correctas' => $porcentaje_correctas,
            'json_grafico_correctas' => $json_grafico_correctas
        ]);
    }

}