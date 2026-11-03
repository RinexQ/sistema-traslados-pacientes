<?php
// Descarga de un documento (publica, pero solo si esta activo y publicado).
require_once __DIR__ . '/negocio/ArchivoPdf.php';

ArchivoPdf::entregarDocumento((int) ($_GET['id'] ?? 0), false);
