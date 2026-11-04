# S.I.G.S.M. — Hospital de Clínicas (Grupo Sirius)

Sistema web de **traslados de pacientes, documentación y encuestas** del Hospital de Clínicas, hecho en
**PHP + MySQL/MariaDB** con **Bootstrap** y organizado en **3 capas** (datos, negocio y presentación).

## Cómo funciona

- **Pacientes y familiares no se registran.** Escanean el **código QR**, que abre la portada del portal
  (`portal.php`), y desde ahí entran a **Documentos** y a **Encuestas** sin iniciar sesión.
- **Solo los administradores crean cuentas** (pantalla *Usuarios*).
- Hay **dos roles**:
  - **Administrador:** acceso a todo el menú: Dashboard, Solicitudes, Ambulancias, Documentación,
    Encuestas, Servicios, Usuarios, Código QR y Registro de acciones.
  - **Usuario básico:** consulta la documentación, carga y sigue solicitudes de traslado y cambia su
    contraseña.

## Qué incluye

- **Login** con cédula y contraseña, sesiones de PHP y cierre de sesión (`logout.php`). Los permisos se
  controlan en PHP en cada página, también al escribir la dirección a mano.
- **Documentos:** subida de **PDF** (tipo y tamaño validados, máx. 5 MB) guardados en `documentos/`, con
  título, descripción, servicio, publicado/oculto, búsqueda por título, filtro por servicio, edición,
  reemplazo del PDF, **baja lógica** e historial de cambios.
- **Encuestas de satisfacción** con constructor propio (ver más abajo), anónimas y con resultados para el
  administrador.
- **Solicitudes y traslados** de pacientes, con sus estados, y gestión de **ambulancias**.
- **Registro de acciones (auditoría):** quién hizo qué y cuándo (ver más abajo).
- **Código QR** único que abre la portada del portal (se descarga como PNG).
- **Administración** de usuarios y servicios (alta, edición, baja lógica).
- **Búsqueda, filtros y paginación** en los listados principales: documentos, usuarios, solicitudes y
  registro de acciones.
- **Seguridad:** `password_hash` / `password_verify`, consultas preparadas (PDO), token CSRF en los formularios
  que modifican datos, texto escapado al mostrarlo, validación en PHP y en JavaScript, y reCAPTCHA opcional.

### Constructor de encuestas

- El administrador **crea encuestas** con título, descripción y servicio, y les agrega **preguntas** de cuatro
  tipos: **opción única**, **opción múltiple**, **valoración de 1 a 5** y **texto libre**. Cada pregunta puede
  ser obligatoria u opcional, y se pueden **reordenar, editar o eliminar**.
- Cada encuesta pasa por tres **estados**: **borrador** (se edita libremente), **publicada** (la ven los
  pacientes) y **cerrada**.
- **Versiones:** para cambiar una encuesta ya publicada se crea una **nueva versión** como borrador; al
  publicarla, la anterior se cierra y sus respuestas se conservan. También se puede **duplicar** una encuesta
  y ver una **vista previa** antes de publicarla.
- Las respuestas son **anónimas**. Cada envío lleva un **token único** que evita que se duplique al recargar
  la página.
- **Resultados:** cantidades, porcentajes y promedio por pregunta, **gráficos** (Chart.js), respuestas de texto
  y **filtro por fechas**.
- **Exportación a CSV** (se abre bien en Excel) de dos formas: una fila por persona que respondió, o un
  resumen por pregunta y opción. Ninguna incluye datos personales.

### Registro de acciones (auditoría)

Queda registrado quién hizo cada acción importante, sobre qué y cuándo: inicio y cierre de sesión, cambios de
contraseña, alta de usuarios, alta y edición de servicios, carga de documentos, y creación, edición,
publicación, duplicación y cambios de preguntas de las encuestas. El administrador lo consulta en
**Registro de acciones**, con filtros por usuario, acción, entidad y fechas, y paginado.

## Estructura

```
config.php                nombre del sistema
config_captcha.php        claves de reCAPTCHA
index.php                 redirige al panel o al login
login.php / logout.php    autenticación
portal.php                portada del portal (la abre el QR)
documentos_publicos.php   documentos para pacientes (búsqueda y filtro)
encuesta.php              encuestas públicas y anónimas
documento_abrir.php       abre un PDF en el navegador (solo si está activo y publicado)
descargar.php             descarga un PDF (misma regla)
documentos/               PDF guardados en el servidor (no se abren directo, ver .htaccess)
negocio/                  reglas de negocio: Auth, Csrf, Usuario, Documento, ArchivoPdf, Solicitud, Traslado,
                          Encuesta, EnvioEncuesta, Auditoria, Filtros, Paginacion, Recaptcha, ...
datos/                    acceso a la base con PDO: Conexion y los DAO
presentacion/             páginas internas, estilos (css/), imágenes (img/) y JavaScript (js/)
sql/                      instalar_todo.sql (crea toda la base) e instalar_todo_hosting.sql (igual, para hosting)
```

## Requisitos

- PHP 8.0 o superior (extensiones `pdo_mysql`, `mbstring` y `fileinfo`).
- MySQL 5.7+ o MariaDB 10.3+.
- Un servidor web (Apache).
- Conexión a internet para cargar Bootstrap, Chart.js y la librería del QR desde CDN.

## Instalación

Toda la base de datos se crea con **un solo archivo SQL**. No hay que ejecutar ningún otro: ya incluye las
tablas, los datos de prueba y las mejoras del constructor de encuestas, las respuestas por envío y el registro
de acciones.

| Archivo | Cuándo usarlo |
|---------|---------------|
| `sql/instalar_todo.sql` | **XAMPP y Linux.** Crea la base `traslados_pacientes` (`CREATE DATABASE`) y todo lo demás. |
| `sql/instalar_todo_hosting.sql` | **Hosting compartido** (InfinityFree y similares). Es idéntico, sin las líneas `CREATE DATABASE` y `USE`, porque el hosting no deja crear bases por SQL. |

Se importa **una sola vez**. Si la base ya existe con tablas de una versión anterior, hay que borrarla antes
(`DROP DATABASE traslados_pacientes;`).

### En XAMPP (Windows)

1. Copiar la carpeta del proyecto a `C:\xampp\htdocs\` e iniciar **Apache** y **MySQL**.
2. Abrir `http://localhost/phpmyadmin`, ir a **Importar** (sin seleccionar ninguna base) y subir
   **`sql/instalar_todo.sql`**. Se crea la base `traslados_pacientes` completa.
3. En `datos/Conexion.php` poner los datos de XAMPP:
   `host = localhost`, `dbname = traslados_pacientes`, `user = root`, `pass` vacía.
4. Abrir `http://localhost/NOMBRE_DE_LA_CARPETA/login.php` (los pacientes entran por `portal.php`).

### En Linux (Fedora, Apache y MariaDB)

```bash
# Crear toda la base con un solo comando
sudo mariadb < sql/instalar_todo.sql

# Crear el usuario de la aplicación (cambiar CLAVE)
sudo mariadb -e "CREATE USER 'sigsm'@'localhost' IDENTIFIED BY 'CLAVE'; GRANT ALL PRIVILEGES ON traslados_pacientes.* TO 'sigsm'@'localhost'; FLUSH PRIVILEGES;"

# Permisos y SELinux (Fedora lo trae activo)
sudo chown -R apache:apache /var/www/html/NOMBRE_DE_LA_CARPETA
sudo chmod -R 775 /var/www/html/NOMBRE_DE_LA_CARPETA/documentos
sudo setsebool -P httpd_can_network_connect_db 1
sudo semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/html/NOMBRE_DE_LA_CARPETA/documentos(/.*)?"
sudo restorecon -Rv /var/www/html/NOMBRE_DE_LA_CARPETA/documentos
sudo systemctl restart httpd
```

En `datos/Conexion.php` poner `host = localhost`, `dbname = traslados_pacientes`, `user = sigsm` y la clave
elegida.

### En un hosting (por ejemplo InfinityFree)

1. Crear la base desde el panel del hosting y **seleccionarla** en phpMyAdmin (panel izquierdo).
2. **Importar `sql/instalar_todo_hosting.sql`**.
3. En `datos/Conexion.php` poner el host, el nombre de la base, el usuario y la contraseña que da el panel.
   **El `Conexion.php` de este repositorio está configurado para InfinityFree:** en cualquier otro servidor
   hay que cambiarlo, porque esa base solo acepta conexiones desde el propio hosting.
4. Borrar el `index2.html` que trae `htdocs` por defecto para que se abra `index.php`.
5. En Linux los nombres de archivos distinguen mayúsculas: no cambiarlos.

## Credenciales de prueba

| Rol | Cédula | Contraseña |
|-----|--------|------------|
| Administrador | `10000001` | `admin123` |
| Usuario básico | `10000006` | `medico123` |
| Usuario básico | `10000007` | `enfermeria123` |

La cédula se escribe sin puntos ni guión. Las cédulas del personal son `10000000 + id`; si una no entra, el
administrador puede verla en *Usuarios*. Los pacientes no tienen cuenta. Todos los datos y documentos son
ficticios.

## Verificación "No soy un robot" (login y encuesta)

El login y la encuesta pública usan la casilla de Google reCAPTCHA (v2). Las claves se guardan en
`config_captcha.php`. Para usar otro dominio:

1. Registrarlo en el sitio de administración de reCAPTCHA de Google (tipo *casilla*). Para probar en local hay
   que agregar también `localhost`.
2. Pegar la **clave del sitio** y la **clave secreta** en `config_captcha.php`.

Si las claves están vacías, el login y la encuesta funcionan pero sin verificación anti-robots.

## Código QR

Desde el panel (administrador) → **Código QR**. Apunta a `portal.php` con la dirección desde la que se abrió
el sistema. Con `localhost` solo funciona en esa computadora; para probarlo con un celular hay que abrir el
sistema con la IP del servidor o publicarlo en un hosting.
