<?php

class PreguntaModel
{
  private $db;

  public function __construct($db)
  {
    $this->db = $db;
  }

  /*
   * 1. Dificultad adecuada según su nivel (ratio: correctas / entregadas)
   * 2. Haya sido entregada al menos 5 veces
   * 3. El usuario no la vio (usuario_pregunta)
   * 4. Sea de esa categoria
   */
  public function obtenerPregunta($id_usuario, $id_categoria)
  {
    // Por ahora solo devuelvo la pregunta que coincida con esa categoria
    $sql = "SELECT * FROM preguntas WHERE id_categoria = $id_categoria";
    $resultado = $this->db->query($sql);
    return $resultado[0];
  }

  public function obtenerRespuestas($id_pregunta)
  {
    $sql = "SELECT * FROM respuestas WHERE id_pregunta = $id_pregunta";
    $resultado = $this->db->query($sql);
    return $resultado;
  }

  public function validarRespuesta($id_respuesta)
  {
    $sql = "SELECT esCorrecta FROM respuestas WHERE id_respuesta = $id_respuesta";
    $resultado = $this->db->query($sql);

    // Si no hay una respuesta con este id, devuelvo false
    if (empty($resultado)) {
      return false;
    }

    // Retorna el atributo boolean esCorrecta de la respuesta encontrada
    return $resultado[0]['esCorrecta'];
  }
}
