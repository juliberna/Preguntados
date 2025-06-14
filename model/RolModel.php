<?php

class RolModel
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getRolesDelUsuario(int $id_usuario): array
    {
        $sql = "
            SELECT r.nombre_rol
            FROM usuario_rol ur
            JOIN roles r ON r.id_rol = ur.id_rol
            WHERE ur.id_usuario = $id_usuario
        ";
        $resultados = $this->db->query($sql);
        return array_column($resultados, 'nombre_rol');
    }
}