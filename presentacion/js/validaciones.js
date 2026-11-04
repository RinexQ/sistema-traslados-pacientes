// Validaciones del lado del cliente (login, documentos, usuarios, servicios, contrasena, solicitudes, traslados y encuesta).
// El servidor en PHP vuelve a validar todo, esto es solo para avisar mas rapido.
(function () {
    'use strict';

    var REGEX_CEDULA = /^\d{7,8}$/;
    var TAMANIO_MAXIMO_PDF = 5 * 1024 * 1024; // 5 MB
    var REGEX_EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    var REGEX_TELEFONO = /^[0-9+\s-]{7,20}$/;

    // Marca un campo con error (o bien si el mensaje viene vacio)
    function marcar(input, mensaje) {
        var aviso = input.parentElement.querySelector('.invalid-feedback');
        if (mensaje) {
            input.classList.add('is-invalid');
            input.classList.remove('is-valid');
            if (aviso) { aviso.textContent = mensaje; }
            return false;
        }
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        if (aviso) { aviso.textContent = ''; }
        return true;
    }

    // Saca las marcas de un campo que no hace falta validar
    function limpiar(input) {
        input.classList.remove('is-invalid');
        input.classList.remove('is-valid');
        var aviso = input.parentElement.querySelector('.invalid-feedback');
        if (aviso) { aviso.textContent = ''; }
        return true;
    }

    // ---------- Validaciones sueltas ----------

    function validarCedula(input) {
        var valor = input.value.trim();
        if (valor === '') { return marcar(input, 'La cédula es obligatoria.'); }
        if (!REGEX_CEDULA.test(valor)) { return marcar(input, 'La cédula debe tener 7 u 8 dígitos, sin puntos ni guión.'); }
        return marcar(input, '');
    }

    function validarPassword(input) {
        if (input.value === '') { return marcar(input, 'La contraseña es obligatoria.'); }
        return marcar(input, '');
    }

    // Archivo PDF: obligatorio al crear (campo required), opcional al reemplazar
    function validarPdf(input) {
        if (input.files.length === 0) {
            return input.required ? marcar(input, 'Seleccioná un archivo PDF.') : marcar(input, '');
        }
        var archivo = input.files[0];
        if (!/\.pdf$/i.test(archivo.name)) { return marcar(input, 'El archivo tiene que ser un PDF.'); }
        if (archivo.size > TAMANIO_MAXIMO_PDF) { return marcar(input, 'El archivo supera el máximo de 5 MB.'); }
        if (archivo.size === 0) { return marcar(input, 'El archivo está vacío.'); }
        return marcar(input, '');
    }

    function validarEmail(input) {
        var valor = input.value.trim();
        if (valor === '') { return marcar(input, 'El email es obligatorio.'); }
        if (valor.length > 150 || !REGEX_EMAIL.test(valor)) { return marcar(input, 'Ingresá un email válido.'); }
        return marcar(input, '');
    }

    // Contrasena nueva: de 8 a 72 caracteres, con una letra y un numero
    function validarPasswordNueva(input) {
        if (input.value === '') { return marcar(input, 'La contraseña es obligatoria.'); }
        return controlarReglasPassword(input, input.value);
    }

    // Igual que la anterior pero se puede dejar vacia (edicion de usuario)
    function validarPasswordOpcional(input) {
        if (input.value === '') { return marcar(input, ''); }
        return controlarReglasPassword(input, input.value);
    }

    function controlarReglasPassword(input, valor) {
        if (valor.length < 8) { return marcar(input, 'Debe tener al menos 8 caracteres.'); }
        if (valor.length > 72) { return marcar(input, 'No puede superar los 72 caracteres.'); }
        if (!/[A-Za-z]/.test(valor) || !/\d/.test(valor)) { return marcar(input, 'Debe incluir al menos una letra y un número.'); }
        return marcar(input, '');
    }

    function validarTelefono(input) {
        if (!REGEX_TELEFONO.test(input.value.trim())) {
            return marcar(input, 'Ingresá un teléfono válido (7 a 20 caracteres, solo números, espacios, + o -).');
        }
        return marcar(input, '');
    }

    // Crea un validador de texto obligatorio con un maximo de caracteres
    function textoObligatorio(nombreCampo, maximo) {
        return function (input) {
            var valor = input.value.trim();
            if (valor === '') { return marcar(input, nombreCampo + ' es obligatorio.'); }
            if (valor.length > maximo) {
                return marcar(input, nombreCampo + ' no puede superar los ' + maximo + ' caracteres.');
            }
            return marcar(input, '');
        };
    }

    // Texto que se puede dejar vacio pero con un maximo de caracteres
    function textoOpcional(nombreCampo, maximo) {
        return function (input) {
            if (input.value.trim().length > maximo) {
                return marcar(input, nombreCampo + ' no puede superar los ' + maximo + ' caracteres.');
            }
            return limpiar(input);
        };
    }

    // Crea un validador para un select que no puede quedar vacio
    function selectObligatorio(mensaje) {
        return function (input) {
            if (input.value === '') { return marcar(input, mensaje); }
            return marcar(input, '');
        };
    }

    function validarVerificado(input) {
        if (!input.checked) { return marcar(input, 'Tenés que confirmar la lista de verificación.'); }
        return marcar(input, '');
    }

    function validarFecha(input) {
        if (input.value === '') { return marcar(input, 'Indicá la fecha y hora de salida.'); }
        return marcar(input, '');
    }

    // ---------- Conecta los validadores con un formulario ----------

    function conectar(form, campos) {
        form.addEventListener('submit', function (evento) {
            var todoOk = true;
            var primerError = null;
            campos.forEach(function (c) {
                if (!c.validar(c.input)) {
                    todoOk = false;
                    if (primerError === null) { primerError = c.input; }
                }
            });
            if (!todoOk) {
                evento.preventDefault();
                primerError.focus();
            }
        });

        campos.forEach(function (c) {
            c.input.addEventListener('blur', function () { c.validar(c.input); });
            c.input.addEventListener('input', function () {
                if (c.input.classList.contains('is-invalid')) { c.validar(c.input); }
            });
            c.input.addEventListener('change', function () {
                if (c.input.classList.contains('is-invalid')) { c.validar(c.input); }
            });
        });
    }

    function porId(id) {
        return document.getElementById(id);
    }

    // ---------- Pantalla 1: login ----------

    // Casilla "No soy un robot" de Google: tiene que estar marcada antes de enviar.
    // Si la pagina no tiene casilla (claves sin cargar), no se exige nada.
    function recaptchaMarcado(form) {
        var casilla = form.querySelector('.g-recaptcha');
        if (!casilla || typeof grecaptcha === 'undefined') { return true; }
        var aviso = porId('error-recaptcha');
        var marcado = grecaptcha.getResponse() !== '';
        if (aviso) { aviso.textContent = marcado ? '' : 'Marcá la casilla "No soy un robot".'; }
        return marcado;
    }

    function iniciarLogin() {
        var form = porId('form-login');
        if (!form) { return; }
        conectar(form, [
            { input: porId('cedula'), validar: validarCedula },
            { input: porId('password'), validar: validarPassword }
        ]);
        form.addEventListener('submit', function (evento) {
            if (!recaptchaMarcado(form)) { evento.preventDefault(); }
        });
    }

    // ---------- Pantalla 2: documentos ----------

    function iniciarDocumento() {
        var form = porId('form-documento');
        if (!form) { return; }
        conectar(form, [
            { input: porId('titulo'), validar: textoObligatorio('El título', 200) },
            { input: porId('descripcion'), validar: textoOpcional('La descripción', 500) },
            { input: porId('id_servicio'), validar: selectObligatorio('Elegí el servicio del documento.') },
            { input: porId('pdf'), validar: validarPdf }
        ]);
    }

    // ---------- Usuarios, servicios y contrasena ----------

    function iniciarUsuario() {
        var form = porId('form-usuario');
        if (!form) { return; }
        var esEdicion = form.getAttribute('data-edicion') === '1';
        conectar(form, [
            { input: porId('nombre'), validar: textoObligatorio('El nombre', 100) },
            { input: porId('ci'), validar: validarCedula },
            { input: porId('email'), validar: validarEmail },
            { input: porId('password'), validar: esEdicion ? validarPasswordOpcional : validarPasswordNueva }
        ]);
    }

    function iniciarServicio() {
        var form = porId('form-servicio');
        if (!form) { return; }
        conectar(form, [
            { input: porId('nombre'), validar: textoObligatorio('El nombre del servicio', 100) }
        ]);
    }

    function iniciarPassword() {
        var form = porId('form-password');
        if (!form) { return; }

        function validarConfirmacion(input) {
            if (input.value === '') { return marcar(input, 'Repetí la contraseña nueva.'); }
            if (input.value !== porId('nueva').value) { return marcar(input, 'La confirmación no coincide.'); }
            return marcar(input, '');
        }

        conectar(form, [
            { input: porId('actual'), validar: validarPassword },
            { input: porId('nueva'), validar: validarPasswordNueva },
            { input: porId('confirmar'), validar: validarConfirmacion }
        ]);
    }

    // ---------- Pantalla 3: solicitud de traslado ----------

    function iniciarSolicitud() {
        var form = porId('form-solicitud');
        if (!form) { return; }

        var tipo = porId('tipo');
        var bloquePaciente = porId('bloque-paciente');
        var aislamiento = porId('aislamiento');
        var detalle = porId('aislamiento_detalle');

        function esPaciente() {
            return tipo.value === 'paciente';
        }

        // Los datos del paciente solo se piden si se traslada un paciente
        function soloSiPaciente(validador) {
            return function (input) {
                if (!esPaciente()) { return limpiar(input); }
                return validador(input);
            };
        }

        // El detalle del aislamiento solo se pide si marco "requiere aislamiento"
        function validarDetalle(input) {
            if (!esPaciente() || !aislamiento.checked) { return limpiar(input); }
            return textoObligatorio('El detalle del aislamiento', 150)(input);
        }

        function actualizarPantalla() {
            bloquePaciente.style.display = esPaciente() ? 'block' : 'none';
            detalle.style.display = aislamiento.checked ? 'block' : 'none';
        }

        tipo.addEventListener('change', actualizarPantalla);
        aislamiento.addEventListener('change', function () {
            actualizarPantalla();
            validarDetalle(detalle);
        });
        actualizarPantalla();

        conectar(form, [
            { input: porId('origen'), validar: textoObligatorio('El origen', 200) },
            { input: porId('destino'), validar: textoObligatorio('El destino', 200) },
            { input: porId('motivo'), validar: textoObligatorio('El motivo', 255) },
            { input: porId('ci_paciente'), validar: soloSiPaciente(selectObligatorio('Elegí el paciente a trasladar.')) },
            { input: porId('domicilio'), validar: soloSiPaciente(textoObligatorio('El domicilio', 200)) },
            { input: porId('telefono'), validar: soloSiPaciente(validarTelefono) },
            { input: porId('diagnostico'), validar: soloSiPaciente(textoObligatorio('El diagnóstico', 255)) },
            { input: detalle, validar: validarDetalle },
            { input: porId('verificado'), validar: soloSiPaciente(validarVerificado) }
        ]);
    }

    // ---------- Pantalla 4: asignar traslado ----------

    function iniciarTraslado() {
        var form = porId('form-traslado');
        if (!form) { return; }

        var vehiculo = porId('id_vehiculo');

        // El enfermero es obligatorio solo si el vehiculo elegido lo requiere
        function validarEnfermero(input) {
            var opcion = vehiculo.options[vehiculo.selectedIndex];
            var requiere = opcion && opcion.getAttribute('data-requiere') === '1';
            if (requiere && input.value === '') {
                return marcar(input, 'Este vehículo requiere un enfermero en el traslado.');
            }
            return limpiar(input);
        }

        vehiculo.addEventListener('change', function () {
            validarEnfermero(porId('id_enfermero'));
        });

        conectar(form, [
            { input: porId('id_conductor'), validar: selectObligatorio('Elegí un conductor: sin chofer no se puede hacer el traslado.') },
            { input: vehiculo, validar: selectObligatorio('Elegí un vehículo.') },
            { input: porId('id_enfermero'), validar: validarEnfermero },
            { input: porId('fecha_salida'), validar: validarFecha }
        ]);
    }

    // ---------- Encuesta publica: varias preguntas de distintos tipos ----------

    // Revisa una pregunta obligatoria segun su tipo. Devuelve true si esta respondida.
    function preguntaRespondida(bloque) {
        var tipo = bloque.getAttribute('data-tipo');
        if (tipo === 'texto') {
            return bloque.querySelector('textarea').value.trim() !== '';
        }
        // opcion unica, multiple y valoracion: tiene que haber algo marcado
        return bloque.querySelector('input:checked') !== null;
    }

    function iniciarEncuesta() {
        var form = porId('form-encuesta');
        if (!form) { return; }

        var bloques = form.querySelectorAll('.pregunta');

        // Muestra o borra el aviso de una pregunta. Solo las obligatorias se exigen.
        function controlar(bloque) {
            var aviso = bloque.querySelector('.error-pregunta');
            var obligatoria = bloque.getAttribute('data-obligatoria') === '1';
            var ok = !obligatoria || preguntaRespondida(bloque);
            if (aviso) { aviso.textContent = ok ? '' : 'Esta pregunta es obligatoria.'; }
            return ok;
        }

        for (var i = 0; i < bloques.length; i++) {
            (function (bloque) {
                bloque.addEventListener('change', function () { controlar(bloque); });
                bloque.addEventListener('input', function () { controlar(bloque); });
            })(bloques[i]);
        }

        form.addEventListener('submit', function (evento) {
            var primeraConError = null;
            for (var j = 0; j < bloques.length; j++) {
                if (!controlar(bloques[j]) && primeraConError === null) {
                    primeraConError = bloques[j];
                }
            }
            if (primeraConError !== null) {
                evento.preventDefault();
                primeraConError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            if (!recaptchaMarcado(form)) {
                evento.preventDefault();
            }
        });
    }

    // ---------- Constructor de encuestas ----------

    function iniciarEncuestaDatos() {
        var form = porId('form-encuesta-datos');
        if (!form) { return; }
        conectar(form, [
            { input: porId('titulo'), validar: textoObligatorio('El título', 200) },
            { input: porId('descripcion'), validar: textoOpcional('La descripción', 500) }
        ]);
    }

    function iniciarPregunta() {
        var form = porId('form-pregunta');
        if (!form) { return; }

        var tipo = porId('tipo');
        var bloqueOpciones = porId('bloque-opciones');
        var opciones = porId('opciones');

        // Las opciones solo se piden en las preguntas de opcion unica y multiple
        function mostrarOpciones() {
            bloqueOpciones.style.display = (tipo.value === 'unica' || tipo.value === 'multiple') ? '' : 'none';
        }
        tipo.addEventListener('change', mostrarOpciones);
        mostrarOpciones();

        function validarOpciones(input) {
            if (tipo.value !== 'unica' && tipo.value !== 'multiple') { return marcar(input, ''); }
            var lineas = input.value.split(/\r\n|\r|\n/);
            var cantidad = 0;
            var vistas = {};
            for (var i = 0; i < lineas.length; i++) {
                var linea = lineas[i].trim();
                if (linea === '') { continue; }
                if (linea.length > 150) { return marcar(input, 'Cada opción puede tener hasta 150 caracteres.'); }
                if (vistas[linea.toLowerCase()]) { return marcar(input, 'Hay opciones repetidas.'); }
                vistas[linea.toLowerCase()] = true;
                cantidad++;
            }
            if (cantidad < 2) { return marcar(input, 'Escribí al menos 2 opciones (una por línea).'); }
            if (cantidad > 10) { return marcar(input, 'No puede haber más de 10 opciones.'); }
            return marcar(input, '');
        }

        conectar(form, [
            { input: porId('texto'), validar: textoObligatorio('La pregunta', 255) },
            { input: opciones, validar: validarOpciones }
        ]);
    }

    document.addEventListener('DOMContentLoaded', function () {
        iniciarLogin();
        iniciarDocumento();
        iniciarUsuario();
        iniciarServicio();
        iniciarPassword();
        iniciarSolicitud();
        iniciarTraslado();
        iniciarEncuesta();
        iniciarEncuestaDatos();
        iniciarPregunta();
    });
})();
