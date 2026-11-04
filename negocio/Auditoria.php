<?php
// Capa de NEGOCIO
// Registro de acciones importantes: quien hizo que y cuando.
// Se llama desde las paginas despues de una accion que salio bien. Si el registro
// falla, no se interrumpe la accion del usuario (solo se deja de anotar).

require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/../datos/AuditoriaDAO.php';

class Auditoria {

    public static function registrar(string $accion, string $entidad, ?int $idEntidad, string $detalle): void {
        try {
            $usuario = Auth::usuarioActual();
            $idAdmin = $usuario !== null ? (int) $usuario['identificador'] : null;
            (new AuditoriaDAO())->registrar($idAdmin, $accion, $entidad, $idEntidad, $detalle);
        } catch (Throwable $ex) {
            // no se muestra nada: el registro es un complemento
        }
    }
}
