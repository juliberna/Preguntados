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
        $rangoFechas = $this->getRangoFechas($filtro, $_GET);
        $desde = $rangoFechas['desde'];
        $hasta = $rangoFechas['hasta'];

        $edad = [
            'menor' => 0,
            'media' => 0,
            'mayor' => 0
        ];
        foreach ($this->model->obtenerDistribucionPorRangoEdad($desde, $hasta) as $fila) {
            switch ($fila['rangoEdad']) {
                case 'Menor': $edad['menor'] = (int)$fila['cantidad']; break;
                case 'Mediana edad': $edad['media'] = (int)$fila['cantidad']; break;
                case 'Mayor': $edad['mayor'] = (int)$fila['cantidad']; break;
            }
        }

        $genero = [
            'femenino' => 0,
            'masculino' => 0,
            'otro' => 0
        ];
        foreach ($this->model->obtenerDistribucionPorGenero($desde, $hasta) as $fila) {
            switch ($fila['descripcion']) {
                case 'Femenino': $genero['femenino'] = (int)$fila['cantidad']; break;
                case 'Masculino': $genero['masculino'] = (int)$fila['cantidad']; break;
                case 'Prefiero no cargarlo': $genero['otro'] = (int)$fila['cantidad']; break;
            }
        }

        $porcentajeGeneral = $this->model->obtenerPorcentajeGeneral($desde, $hasta);
        $usuariosPais = $this->model->obtenerUsuariosPorPaisPorFecha($desde, $hasta);
        $rendimientoUsuarios = $this->model->obtenerRendimientosUsuarios($desde, $hasta);

        $this->view->render("panelAdmin", [
            'title' => 'Dashboard',
            'filtro_Actual' => $filtro,
            'filtro_dia' => $filtro === 'dia',
            'filtro_semana' => $filtro === 'semana',
            'filtro_mes' => $filtro === 'mes',
            'filtro_anio' => $filtro === 'anio',
            'filtro_personalizado' => $filtro === 'personalizado',
            'desde' => substr($desde, 0, 10),
            'hasta' => substr($hasta, 0, 10),
            'rango_mostrar' => date('d/m/Y', strtotime($desde)) . ' al ' . date('d/m/Y', strtotime($hasta)),

            'total_jugadores' => $this->model->obtenerTotalUsuarios(),
            'total_jugadores_nuevos' => $this->model->obtenerTotalUsuariosNuevosPorFecha($desde, $hasta),
            'partidas_jugadas' => $this->model->obtenerPartidasJugadasPorFecha($desde, $hasta),
            'total_preguntas' => $this->model->obtenerPreguntasActivas(),
            'total_preguntas_creadas' => $this->model->obtenerPreguntasActivasPorFecha($desde, $hasta),

            'edad' => $edad,
            'genero' => $genero,
            'hay_datos_edad' => array_sum($edad) > 0,
            'hay_datos_genero' => array_sum($genero) > 0,

            'porcentaje_general' => $porcentajeGeneral,
            'hay_datos_porcentaje' => $porcentajeGeneral[0]['porcentajeCorrectas'] !== null,

            'json_paises_usuarios' => json_encode($usuariosPais),
            'hay_datos_paises' => count($usuariosPais) > 0,

            'rendimiento_usuarios' => $rendimientoUsuarios
        ]);
    }

    private function getRangoFechas($filtro, $parametros): array {
        switch ($filtro) {
            case 'dia':
                $desde = (new DateTime())->setTime(0, 0)->format('Y-m-d H:i:s');
                $hasta = (new DateTime())->setTime(23, 59, 59)->format('Y-m-d H:i:s');
                break;
            case 'semana':
                $lunes = new DateTime('monday this week');
                $domingo = new DateTime('sunday this week');
                $desde = $lunes->format('Y-m-d 00:00:00');
                $hasta = $domingo->format('Y-m-d 23:59:59');
                break;
            case 'anio':
                $desde = date('Y-01-01 00:00:00');
                $hasta = date('Y-12-31 23:59:59');
                break;
            case 'personalizado':
                $desde = $parametros['desde'] ?? '';
                $hasta = $parametros['hasta'] ?? '';
                if ($desde && $hasta) {
                    $desde .= ' 00:00:00';
                    $hasta .= ' 23:59:59';
                } else {
                    // Mes por defecto cuando selecciona personalizado
                    $desde = date('Y-m-01 00:00:00');
                    $hasta = date('Y-m-t 23:59:59');
                }
                break;
            case 'mes':
            default:
                $desde = date('Y-m-01 00:00:00');
                $hasta = date('Y-m-t 23:59:59');
                break;
        }

        return ['desde' => $desde, 'hasta' => $hasta];
    }
}