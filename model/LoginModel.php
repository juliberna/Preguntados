<?php

class LoginModel
{
    private $database;

    public function __construct($databaseb){
        $this->database = $databaseb;
    }

    public function buscarUsuarioPorEmail($email){
        $stmt = $this->database->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    }

}
