<?php

class EditorModel
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getCategorias(): array
    {
        $sql = "
              SELECT id_categoria, nombre
              FROM categoria";
        return $this->db->query($sql);
    }

    public function getPreguntasSugeridas($terminoBusqueda = '', $id_categoria = 'todasLasCategorias')
    {
        $where = "p.estado = 'sugerida'";

        if ($terminoBusqueda !== '') {
            $term = $this->db->escapeLike($terminoBusqueda);
            $where .= " AND p.pregunta LIKE '%$term%'";
        }

        if ($id_categoria !== 'todasLasCategorias') {
            $where .= " AND p.id_categoria = " . (int)$id_categoria;
        }

        $sql = "
        SELECT DISTINCT p.id_pregunta, p.pregunta, c.nombre, u.nombre_usuario, u.email, p.estado
        FROM preguntas p
        JOIN categoria c ON p.id_categoria = c.id_categoria
        JOIN sugerencias_preguntas s ON s.pregunta_sugerida COLLATE utf8mb4_spanish_ci = p.pregunta COLLATE utf8mb4_spanish_ci
        JOIN usuarios u ON s.id_usuario = u.id_usuario
        WHERE $where
    ";

        return $this->db->query($sql);
    }

    public function getAutorDePreguntaSugerida($id_pregunta)
    {
        $sql = "
        SELECT u.nombre_usuario, u.email
        FROM sugerencias_preguntas sp
        JOIN usuarios u ON sp.id_usuario = u.id_usuario
        JOIN preguntas p ON sp.pregunta_sugerida COLLATE utf8mb4_spanish_ci = p.pregunta
        WHERE p.id_pregunta = $id_pregunta
        LIMIT 1
    ";
        $resultado = $this->db->query($sql);
        return $resultado[0] ?? null;
    }

    public function activarPreguntaSugerida($id)
    {

        $estado = 'activa';

        $sql = "UPDATE preguntas SET estado = '$estado' WHERE id_pregunta = $id ";
        $this->db->execute($sql);

        $sql2 = "UPDATE respuestas SET activa = '1' WHERE id_pregunta = $id ";
        $this->db->execute($sql2);

    }

    public function desactivarPreguntaSugerida($id)
    {
        $estado = 'deshabilitada';

        $sql = "UPDATE preguntas SET estado = '$estado' WHERE id_pregunta = $id ";
        $this->db->execute($sql);

        $sql2 = "UPDATE respuestas SET activa = '0' WHERE id_pregunta = $id ";
        $this->db->execute($sql2);

    }

    public function fechaResolucionSugerencia($id)
    {

        $query = "SELECT pregunta FROM preguntas WHERE id_pregunta = $id ";
        $resultado = $this->db->query($query);

        $final = $resultado[0]['pregunta'];

        $sql = "UPDATE sugerencias_preguntas SET fecha_resolucion = NOW() WHERE pregunta_sugerida = '$final' ";
        $this->db->execute($sql);

    }


    public function actualizarEstadoPregunta($id, $estado)
    {

        $query = "SELECT pregunta FROM preguntas WHERE id_pregunta = $id ";
        $resultado = $this->db->query($query);

        $final = $resultado[0]['pregunta'];

        $sql = "UPDATE sugerencias_preguntas SET estado = '$estado' WHERE pregunta_sugerida = '$final' ";
        $this->db->execute($sql);

    }


    public function getPreguntasPorCategoria($id_categoria, $terminoBusqueda = ''): array
    {
        $where = "p.id_categoria = $id_categoria";
        if (trim($terminoBusqueda) !== '') {
            $term = $this->db->escapeLike($terminoBusqueda);
            $where .= " AND p.pregunta LIKE '%$term%'";
        }

        $sql = "
            SELECT p.id_pregunta, p.pregunta, c.nombre, p.estado
            FROM preguntas p
            JOIN categoria c ON p.id_categoria = c.id_categoria
            WHERE $where
            ";
        return $this->db->query($sql);
    }

    public function getPreguntasPorCategoriaIncluirInactivas($id_categoria): array
    {
        $sql = "
              SELECT p.id_pregunta ,p.pregunta, c.nombre, p.activa
              FROM preguntas p join categoria c on p.id_categoria = c.id_categoria
              WHERE p.id_categoria = $id_categoria
              ";
        return $this->db->query($sql);
    }

    public function getPreguntas($terminoBusqueda = ''): array
    {
        $where = "1=1";
        if (trim($terminoBusqueda) !== '') {
            $term = $this->db->escapeLike($terminoBusqueda);
            $where .= " AND p.pregunta LIKE '%$term%'";
        }

        $sql = "
            SELECT p.id_pregunta, p.pregunta, c.nombre, p.estado
            FROM preguntas p
            JOIN categoria c ON p.id_categoria = c.id_categoria
            WHERE $where
            ORDER BY p.id_pregunta
            ";
        return $this->db->query($sql);
    }

    public function desactivarPregunta($id_pregunta)
    {
        $sql = "
                UPDATE preguntas
                SET estado = 'deshabilitada'
                WHERE id_pregunta = $id_pregunta";
        $this->db->execute($sql);
    }

    public function activarPregunta($id_pregunta)
    {
        $sql = "
                UPDATE preguntas
                SET estado = 'activa'
                WHERE id_pregunta = $id_pregunta";
        $this->db->execute($sql);
    }

    /*  public function getPreguntaConRespuestas(int $id_pregunta)
      {
          $sql = "
                  SELECT *
                  FROM preguntas p join respuestas r on r.id_pregunta = p.id_pregunta
                  WHERE p.id_pregunta = $id_pregunta";
          return $this->db->query($sql);
      }*/

    public function getPreguntaPorId($id_pregunta)
    {
        $sql = "SELECT p.id_pregunta, p.pregunta, p.estado, c.nombre
            FROM preguntas p
            JOIN categoria c ON p.id_categoria = c.id_categoria
            WHERE p.id_pregunta = $id_pregunta";
        return $this->db->query($sql);
    }

    public function getRespuestasPorPregunta($id_pregunta): array
    {
        $sql = "SELECT id_respuesta, respuesta, esCorrecta
            FROM respuestas
            WHERE id_pregunta = $id_pregunta";
        return $this->db->query($sql);
    }

    public function actualizarPregunta($id_pregunta, $textoPregunta)
    {
        $sql = "UPDATE preguntas
                SET pregunta = '$textoPregunta'
                WHERE id_pregunta = $id_pregunta";
        $this->db->execute($sql);
    }

    public function actualizarRespuesta($id_respuesta, $textoRespuesta)
    {
        $sql = "UPDATE respuestas
                SET respuesta = '$textoRespuesta'
                WHERE id_respuesta = $id_respuesta";
        $this->db->execute($sql);
    }

}