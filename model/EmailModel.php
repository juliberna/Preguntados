<?php

class EmailModel
{

    private $database;

    public function __construct($database){
        $this->database = $database;
    }

    public function validarCorreo($id){
        $sql = "UPDATE usuarios SET es_validado = true WHERE id_usuario = $id";

        $this->database->execute($sql);
    }

}
