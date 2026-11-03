# S.I.G.S.M. — Hospital de Clínicas (Grupo Sirius)

Sistema web de **traslados de pacientes y gestión de documentos** del Hospital de Clínicas, hecho en
**PHP + MySQL/MariaDB** con **Bootstrap** y organizado en **3 capas** (datos, negocio y presentación).

## Cómo funciona

- **Pacientes y familiares no se registran.** Escanean el **código QR**, que abre la portada del portal
  (`portal.php`), y desde ahí entran a **Documentos** y a **Encuestas** sin iniciar sesión.
- **Solo los administradores crean cuentas** (pantalla *Usuarios*).
- Hay **dos roles**:
  - **Administrador:** documentos (subir, editar, reemplazar PDF, dar de baja), servicios, usuarios,
    resultados de encuestas, código QR, solicitudes de traslado y ambulancias.
  - **Usuario básico:** consulta documentos, cambia su contraseña y carga solicitudes de traslado.

## Qué incluye

- Login con cédula y contraseña, sesiones de PHP y cierre de sesión (`logout.php`).
- Permisos controlados en PHP en cada página, también al escribir la dirección a mano.
- **Documentos:** subida de **PDF** (tipo y tamaño validados, máx. 5 MB) guardados en `documentos/`,
  título, descripción, servicio, publicado/oculto, búsqueda por título, filtro por servicio, edición,
  reemplazo del PDF, **baja lógica** e historial de cambios.
- **Encuestas de satisfacción** anónimas (opciones 1 a 5) y reporte para el administrador.
- **Solicitudes y traslados** de pacientes, con sus estados, y gestión de **ambulancias**.
- **Código QR** único que abre la portada del portal (se descarga como PNG).
- **Administración** de usuarios y servicios (alta, edición, baja lógica).
- Seguridad: `password_hash`/`password_verify`, consultas preparadas (PDO), token CSRF en los formularios
  que modifican datos, texto escapado al mostrarlo y validación en PHP y en JavaScript.

## Estructura

```
config.php                nombre del sistema
config_captcha.php        claves de reCAPTCHA (no se sube a GitHub)
index.php                 redirige al panel o al login
login.php / logout.php    autenticación
portal.php                portada del portal (la abre el QR)
documentos_publicos.php   documentos para pacientes (búsqueda y filtro)
encuesta.php              encuestas públicas y anónimas
documento_abrir.php       abre un PDF en el navegador (solo si está activo y publicado)
descargar.php             descarga un PDF (misma regla)
documentos/               PDF guardados en el servidor (no se abren directo, ver .htaccess)
negocio/                  reglas de negocio: Auth, Csrf, Usuario, Documento, ArchivoPdf, Solicitud, ...
datos/                    acceso a la base con PDO: Conexion y los DAO
presentacion/             páginas internas, estilos, imágenes y JavaScript
sql/                      instalar_todo.sql (instalación completa de la base)
```

## Requisitos

- PHP 8.0 o superior (extensiones `pdo_mysql`, `mbstring` y `fileinfo`, activas en XAMPP).
- MySQL 5.7+ o MariaDB 10.3+.
- Un servidor web (Apache de XAMPP).
- Conexión a internet para cargar Bootstrap y la librería del QR desde CDN.

## Instalación (XAMPP)

1. Copiar la carpeta del proyecto a `C:\xampp\htdocs\S.I.G.S.M`.
2. Iniciar **Apache** y **MySQL** desde el panel de XAMPP.
3. Abrir `http://localhost/phpmyadmin`, ir a **Importar** y subir **`sql/instalar_todo.sql`** (una sola vez).
   Crea la base `traslados_pacientes`, las tablas y los datos ficticios de prueba.
4. **Crear los archivos de configuración** a partir de los de ejemplo:
   - Copiar `datos/Conexion.example.php` como `datos/Conexion.php`. Por defecto sirve para XAMPP
     (`localhost`, base `traslados_pacientes`, usuario `root`, sin contraseña).
   - Copiar `config_captcha.example.php` como `config_captcha.php` (ver la sección de reCAPTCHA).
5. Abrir `http://localhost/S.I.G.S.M/login.php` (los pacientes entran por `portal.php`).

### En un hosting (por ejemplo InfinityFree)

- Crear la base desde el panel y **seleccionarla** en phpMyAdmin antes de importar.
- `sql/instalar_todo.sql` empieza con `CREATE DATABASE` y `USE traslados_pacientes;`: los hostings
  compartidos no lo permiten, así que hay que borrar esas dos sentencias antes de importar.
- En `datos/Conexion.php` poner el host, el nombre de la base, el usuario y la contraseña que da el hosting.
- Borrar el `index2.html` que trae `htdocs` por defecto para que se abra `index.php`.
- Los nombres de archivos y tablas distinguen mayúsculas en Linux: no cambiarlos.

## Credenciales de prueba

| Rol | Cédula | Contraseña |
|-----|--------|------------|
| Administrador | `10000001` | `admin123` |
| Usuario básico | `10000006` | `medico123` |
| Usuario básico | `10000007` | `enfermeria123` |

La cédula se escribe sin puntos ni guión. Las cédulas del personal son `10000000 + id`; si una no entra,
el administrador puede verla en *Usuarios*. Los pacientes no tienen cuenta. Todos los datos y documentos
son ficticios.

## Verificación "No soy un robot" (login y encuesta)

El login y la encuesta pública usan la casilla de Google reCAPTCHA (v2). Para activarla:

1. Registrar el dominio del sistema en el sitio de administración de reCAPTCHA de Google (tipo *casilla*).
   Para probar en XAMPP hay que agregar también `localhost` como dominio.
2. Copiar la **clave del sitio** y la **clave secreta** en `config_captcha.php`.

Mientras las claves estén vacías, el login y la encuesta funcionan pero sin verificación anti-robots.

## Datos sensibles

`datos/Conexion.php` y `config_captcha.php` contienen contraseñas y claves secretas, por eso están en el
`.gitignore` y **no se suben a GitHub**. En el repositorio solo están las versiones `.example`, con valores
de ejemplo.

## Código QR

Desde el panel (administrador) → **Código QR**. Apunta a `portal.php` con la dirección desde la que se
abrió el sistema. Con `localhost` solo funciona en esa computadora; para probarlo con un celular hay que
abrir el sistema con la IP de la computadora o publicarlo en un hosting.
