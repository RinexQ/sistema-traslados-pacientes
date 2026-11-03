<?php
// Abre un documento en el navegador (publico, pero solo si esta activo y publicado).
// Un documento dado de baja no se puede abrir ni escribiendo la direccion a mano.
require_once __DIR__ . '/negocio/ArchivoPdf.php';

ArchivoPdf::entregarDocumento((int) ($_GET['id'] ?? 0), true);
