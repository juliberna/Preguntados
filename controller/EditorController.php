<?php

class EditorController
{
    private $view;
    private $model;
    private $preguntaModel;

    public function __construct($view,$model, $preguntaModel)
    {
        $this->model = $model;
        $this->view = $view;
        $this->preguntaModel = $preguntaModel;
    }

    public function show()
    {
        $this->view->render("panelEditor", [
            'title' => 'Panel Editor'
        ]);
    }


    public function sugerencias(){

        $preguntasSugeridas = $this->model->getPreguntasSugeridas();

        $haySugeridas = !empty($preguntasSugeridas);

        $this->view->render("sugerencias", [
            'title' => 'Sugerencias de usuarios',
            'sugeridas' => $preguntasSugeridas,
            'haySugeridas' => $haySugeridas
        ]);
    }

    public function activarPregunta(){

        $id= $_GET['id'];

        $this->model->activarPreguntaSugerida($id);
        $this->model->fechaResolucionSugerencia($id);
        $this->model->actualizarEstadoPregunta($id, 'aprobada');
        header('Location: /editor/sugerencias');

    }

    public function desactivarPregunta(){
        $id= $_GET['id'];
        $this->model->desactivarPreguntaSugerida($id);
        $this->model->fechaResolucionSugerencia($id);
        $this->model->actualizarEstadoPregunta($id, 'rechazada');
        header('Location: /editor/sugerencias');
    }

    public function gestionarPreguntas()
    {
        $id_categoria = $_GET['categoria'] ?? 'todasLasCategorias';
        $terminoBusqueda = $_GET['terminoBusqueda'] ?? '';

        $categorias = $this->model->getCategorias();
        foreach ($categorias as &$categoria) {
            $categoria['seleccionada'] = ($categoria['id_categoria'] == $id_categoria);
        }

        if ($id_categoria === 'todasLasCategorias') {
            $preguntas = $this->model->getPreguntas($terminoBusqueda);
        } else {
            $preguntas = $this->model->getPreguntasPorCategoria((int)$id_categoria, $terminoBusqueda);
        }

        foreach ($preguntas as &$pregunta) {
            $pregunta['activa'] = $pregunta['estado'] === 'activa';
        }

        $this->view->render("gestionarPreguntas", [
            'title' => 'Gestión de Preguntas',
            'categorias' => $categorias,
            'categoria_todas' => $id_categoria === 'todasLasCategorias',
            'preguntas' => $preguntas,
            'terminoBusqueda' => $terminoBusqueda,
        ]);
    }

    public function desactivar(){
        $id_pregunta = $_GET['id_pregunta'] ?? '';
        $pregunta = $this->model->desactivarPregunta($id_pregunta);

        header("Location: /editor/gestionarPreguntas");
        exit;
    }

    public function activar(){
        $id_pregunta = $_GET['id_pregunta'] ?? '';
        $pregunta = $this->model->activarPregunta($id_pregunta);

        header("Location: /editor/gestionarPreguntas");
        exit;
    }

    public function editar(){
        $id_pregunta = $_GET['id_pregunta'] ?? '';
        $id_reporte = $_GET['id_reporte'] ?? '';

        if ($id_reporte) {
            $this->preguntaModel->actualizarEstadoReporte($id_reporte, 'resuelto');
            $this->preguntaModel->actualizarEstadoPregunta($id_pregunta, 'activa');
        }

        $pregunta = $this->model->getPreguntaPorId($id_pregunta);
        $pregunta = $pregunta[0] ?? null;
        $respuestas = $this->model->getRespuestasPorPregunta($id_pregunta);

        $this->view->render("editarPregunta", [
            'title' => 'Editar Pregunta',
            'pregunta' => $pregunta,
            'respuestas' => $respuestas
        ]);
    }

    public function guardarEdicion()
    {
        $id_pregunta = $_POST['id_pregunta'] ?? null;
        $textoPregunta = $_POST['pregunta'] ?? '';
        $respuestas = $_POST['respuestas'] ?? [];
        $ids_respuestas = $_POST['ids_respuestas'] ?? [];

        $this->model->actualizarPregunta($id_pregunta, $textoPregunta);

        foreach ($respuestas as $i => $respuesta) {
            if (isset($ids_respuestas[$i])) {
                $this->model->actualizarRespuesta((int)$ids_respuestas[$i], $respuesta);
            }
        }

        header("Location: /editor/gestionarPreguntas");
        exit;
    }

    public function reportes()
    {
        $terminoBusqueda = $_GET['terminoBusqueda'] ?? '';
        $id_categoria = $_GET['categoria'] ?? 'todasLasCategorias';

        $categorias = $this->model->getCategorias();
        foreach ($categorias as &$categoria) {
            $categoria['seleccionada'] = ($categoria['id_categoria'] == $id_categoria);
        }

        // Obtener reportes filtrados
        $preguntasReportadas = $this->preguntaModel->getPreguntasReportadasConDetalles($terminoBusqueda, $id_categoria);

        $this->view->render('preguntasReportadas', [
            'title' => 'Preguntas Reportadas',
            'reportes' => $preguntasReportadas,
            'terminoBusqueda' => $terminoBusqueda,
            'hayReportes' => !empty($preguntasReportadas),
            'categorias' => $categorias,
            'categoria_todas' => $id_categoria === 'todasLasCategorias',
            'id_categoria' => $id_categoria
        ]);

    }

    public function procesarReporte()
    {
        $id_reporte = (int)($_POST['id_reporte'] ?? 0);
        $id_pregunta = (int)($_POST['id_pregunta'] ?? 0);
        $accion = $_POST['accion'] ?? '';

        if ($id_reporte && $id_pregunta && $accion) {
            switch ($accion) {
                case 'descartar':
                    $this->preguntaModel->descartarReporte($id_pregunta, $id_reporte);
                    break;
                case 'aprobar':
                    $this->preguntaModel->aprobarReporte($id_pregunta, $id_reporte);
                    break;
                case 'editar':
                    header("Location: /editor/editar?id_pregunta={$id_pregunta}&id_reporte={$id_reporte}");
                    exit;
            }
        }

        header("Location: /editor/reportes");
        exit;
    }

}