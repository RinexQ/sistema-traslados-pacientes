<?php
require_once __DIR__ . '/../negocio/Auth.php';

// Hay un unico codigo QR: abre la portada del portal, y desde ahi se llega a Documentos y Encuestas
Auth::exigirAdministrador();

$esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$urlPortal = $esquema . '://' . $_SERVER['HTTP_HOST'] . Auth::url('portal.php');

$tituloPagina = 'Código QR';
$paginaActiva = 'qr';
require __DIR__ . '/layout_inicio.php';
?>

            <div class="mt-4">
                <h2>Código QR del portal</h2>
                <p class="text-muted">
                    Al escanearlo se abre la portada del portal para pacientes, desde donde se accede a los
                    documentos y a las encuestas. Se puede imprimir y colocar en las pantallas del hospital.
                </p>
            </div>

            <div class="card shadow-sm" style="max-width: 420px;">
                <div class="card-body d-block text-center">
                    <div id="qr" class="d-flex justify-content-center mb-3"></div>
                    <p class="small text-muted text-break"><?= e($urlPortal) ?></p>
                    <a id="bajar" href="#" download="qr_portal.png" class="btn btn-primary btn-sm">Descargar QR (PNG)</a>
                    <a href="<?= e($urlPortal) ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">Probar el enlace</a>
                    <p class="small text-muted mt-3 mb-0">
                        Si el sistema se abre desde <strong>localhost</strong>, el QR solo funciona en esa computadora.
                    </p>
                </div>
            </div>

            <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
            <script>
                // Dibuja el QR en la pantalla
                new QRCode(document.getElementById('qr'), {
                    text: <?= json_encode($urlPortal) ?>,
                    width: 256,
                    height: 256
                });

                // Al hacer clic en "Descargar" se toma la imagen del QR
                document.getElementById('bajar').addEventListener('click', function () {
                    var lienzo = document.querySelector('#qr canvas');
                    if (lienzo) {
                        this.href = lienzo.toDataURL('image/png');
                    }
                });
            </script>

<?php require __DIR__ . '/layout_fin.php'; ?>
