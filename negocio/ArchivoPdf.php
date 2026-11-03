<?php
// Capa de NEGOCIO
// Todo lo relacionado con los archivos PDF: validar la subida (tipo y tamano),
// guardarlos en la carpeta documentos/ del servidor y entregarlos al navegador.
// Los archivos no se pueden abrir directo por URL (documentos/.htaccess lo impide):
// siempre se entregan desde documento_abrir.php / descargar.php, que revisan
// que el documento este activo.

require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/../datos/DocumentoDAO.php';

class ArchivoPdf {

    public const TAMANIO_MAXIMO = 5242880; // 5 MB
    public const CARPETA = 'documentos';

    private static function carpetaAbsoluta(): string {
        return dirname(__DIR__) . '/' . self::CARPETA;
    }

    // Controla el archivo de $_FILES. Si no es obligatorio y no se eligio ninguno, no hay errores.
    public static function validar(?array $archivo, bool $obligatorio): array {
        $codigo = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($codigo === UPLOAD_ERR_NO_FILE) {
            return $obligatorio ? ['Tenés que seleccionar un archivo PDF.'] : [];
        }
        if ($codigo === UPLOAD_ERR_INI_SIZE || $codigo === UPLOAD_ERR_FORM_SIZE) {
            return ['El archivo supera el tamaño máximo permitido (5 MB).'];
        }
        if ($codigo !== UPLOAD_ERR_OK) {
            return ['No se pudo subir el archivo. Intentá de nuevo.'];
        }

        $errores = [];
        $tmp = $archivo['tmp_name'];

        if (!is_uploaded_file($tmp)) {
            return ['El archivo recibido no es válido.'];
        }
        if ($archivo['size'] <= 0) {
            $errores[] = 'El archivo está vacío.';
        } elseif ($archivo['size'] > self::TAMANIO_MAXIMO) {
            $errores[] = 'El archivo supera el tamaño máximo permitido (5 MB).';
        }

        if (strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION)) !== 'pdf') {
            $errores[] = 'El archivo tiene que ser un PDF (extensión .pdf).';
        }

        // No alcanza con la extension: se mira el contenido real del archivo
        $inicio = (string) file_get_contents($tmp, false, null, 0, 5);
        $mime = function_exists('finfo_open') ? (string) finfo_file(finfo_open(FILEINFO_MIME_TYPE), $tmp) : 'application/pdf';
        if ($inicio !== '%PDF-' || $mime !== 'application/pdf') {
            $errores[] = 'El contenido del archivo no corresponde a un PDF válido.';
        }

        return $errores;
    }

    // Guarda el PDF con un nombre aleatorio y devuelve la ruta relativa (documentos/xxxx.pdf)
    public static function guardar(array $archivo): string {
        $carpeta = self::carpetaAbsoluta();
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0755, true);
        }
        $nombre = bin2hex(random_bytes(16)) . '.pdf';
        if (!move_uploaded_file($archivo['tmp_name'], $carpeta . '/' . $nombre)) {
            throw new RuntimeException('No se pudo guardar el archivo en el servidor.');
        }
        return self::CARPETA . '/' . $nombre;
    }

    // Ruta real del archivo, solo si esta dentro de la carpeta documentos/
    public static function resolver(string $ruta): ?string {
        $carpeta = realpath(self::carpetaAbsoluta());
        $archivo = realpath(dirname(__DIR__) . '/' . $ruta);
        if ($carpeta === false || $archivo === false || !is_file($archivo)) {
            return null;
        }
        if (strpos($archivo, $carpeta . DIRECTORY_SEPARATOR) !== 0) {
            return null;
        }
        return $archivo;
    }

    public static function eliminar(string $ruta): void {
        $archivo = self::resolver($ruta);
        if ($archivo !== null) {
            @unlink($archivo);
        }
    }

    // Busca el documento y lo envia al navegador. Corta con 404 si no esta disponible.
    // $enLinea = true lo abre en el navegador; false fuerza la descarga.
    public static function entregarDocumento(int $id, bool $enLinea): void {
        $documento = null;
        try {
            if ($id > 0) {
                $documento = (new DocumentoDAO())->obtenerAbrible($id, Auth::esAdministrador());
            }
        } catch (PDOException $ex) {
            http_response_code(500);
            echo 'No se pudo consultar el documento.';
            exit;
        }

        if ($documento === null) {
            http_response_code(404);
            echo 'El documento no está disponible.';
            exit;
        }

        $archivo = self::resolver($documento['archivo_url']);
        if ($archivo === null) {
            http_response_code(404);
            echo 'El archivo no está disponible en el servidor.';
            exit;
        }

        // Nombre de descarga legible, sin caracteres raros
        $nombre = trim(preg_replace('/[^A-Za-z0-9]+/', '_', $documento['titulo']), '_');
        $nombre = ($nombre === '' ? 'documento' : substr($nombre, 0, 60)) . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: ' . ($enLinea ? 'inline' : 'attachment') . '; filename="' . $nombre . '"');
        header('Content-Length: ' . filesize($archivo));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private');
        readfile($archivo);
        exit;
    }
}
