<?php

declare(strict_types=1);

/**
 * send.php
 *
 * Formulario de contacto de Blue Lions Academy.
 *
 * Envía el correo mediante la API HTTPS de Resend.
 *
 * From:
 * no-reply@bluelionsacademy.com
 *
 * To:
 * contacto@bluelionsacademy.com
 *
 * Reply-To:
 * correo proporcionado por el visitante.
 */

// ======================================================
// CONFIGURACIÓN
// ======================================================

$destinatario = 'contacto@bluelionsacademy.com';

$remitente = 'Blue Lions Academy <no-reply@bluelionsacademy.com>';

$resendApiKey = getenv('RESEND_API_KEY');


// ======================================================
// VALIDAR CONFIGURACIÓN DEL SERVIDOR
// ======================================================

if (!$resendApiKey) {

    error_log('RESEND_API_KEY no está configurada.');

    http_response_code(500);

    echo '
        <h2>No se pudo enviar el mensaje</h2>
        <p>El servicio de correo no está disponible en este momento.</p>
        <p><a href="index.html">Volver</a></p>
    ';

    exit;
}


// ======================================================
// SOLO PERMITIR POST
// ======================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: index.html');

    exit;
}


// ======================================================
// HONEYPOT ANTI-SPAM
// ======================================================

if (!empty($_POST['sitio_web'])) {

    // Un bot probablemente llenó el campo oculto.

    http_response_code(204);

    exit;
}


// ======================================================
// LIMPIEZA DE CAMPOS
// ======================================================

function limpiarTexto(string $valor): string
{
    $valor = trim($valor);

    // Eliminar caracteres de control.
    $valor = preg_replace(
        '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',
        '',
        $valor
    );

    return $valor ?? '';
}


// ======================================================
// OBTENER DATOS
// ======================================================

$nombre = limpiarTexto(
    (string) ($_POST['nombre'] ?? '')
);

$email = trim(
    (string) ($_POST['email'] ?? '')
);

$telefono = limpiarTexto(
    (string) ($_POST['telefono'] ?? '')
);

$grado = limpiarTexto(
    (string) ($_POST['grado'] ?? '')
);

$mensaje = limpiarTexto(
    (string) ($_POST['mensaje'] ?? '')
);


// ======================================================
// VALIDACIONES
// ======================================================

$errores = [];


/*
 * Nombre
 */

if ($nombre === '') {

    $errores[] = 'El nombre es obligatorio.';

} elseif (mb_strlen($nombre) > 120) {

    $errores[] = 'El nombre es demasiado largo.';
}


/*
 * Email
 */

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    $errores[] = 'El correo no es válido.';
}


/*
 * Evitar inyección CRLF
 */

if (
    str_contains($email, "\r") ||
    str_contains($email, "\n")
) {

    $errores[] = 'El correo contiene caracteres inválidos.';
}


/*
 * Teléfono
 */

if (mb_strlen($telefono) > 30) {

    $errores[] = 'El teléfono es demasiado largo.';
}


/*
 * Grado
 */

if (mb_strlen($grado) > 100) {

    $errores[] = 'El grado seleccionado no es válido.';
}


/*
 * Mensaje
 */

if ($mensaje === '') {

    $errores[] = 'El mensaje es obligatorio.';

} elseif (mb_strlen($mensaje) > 5000) {

    $errores[] = 'El mensaje no puede superar los 5000 caracteres.';
}


// ======================================================
// RESPUESTA DE VALIDACIÓN
// ======================================================

if (!empty($errores)) {

    http_response_code(400);

    echo '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Error en el formulario</title>
        </head>
        <body>
            <h2>Hubo un problema con tu envío</h2>
            <ul>
    ';

    foreach ($errores as $error) {

        echo '<li>' .
            htmlspecialchars(
                $error,
                ENT_QUOTES,
                'UTF-8'
            ) .
            '</li>';
    }

    echo '
            </ul>

            <p>
                <a href="index.html">
                    Volver
                </a>
            </p>

        </body>
        </html>
    ';

    exit;
}


// ======================================================
// ARMAR CORREO
// ======================================================

$asunto =
    'Nuevo contacto desde el sitio web - Blue Lions Academy';


$cuerpo = <<<TEXT
Se recibió un nuevo mensaje desde el formulario de contacto de Blue Lions Academy.

DATOS DEL INTERESADO

Nombre:
{$nombre}

Correo:
{$email}

Teléfono:
{$telefono}

Grado de interés:
{$grado}

MENSAJE

{$mensaje}

--------------------------------------------------

Mensaje generado automáticamente desde:
https://bluelionsacademy.com

Para responder al interesado utilice "Responder" en su cliente de correo.

TEXT;


// ======================================================
// PAYLOAD PARA RESEND
// ======================================================

$payload = [

    'from' => $remitente,

    'to' => [
        $destinatario
    ],

    'reply_to' => $email,

    'subject' => $asunto,

    'text' => $cuerpo,

];


// ======================================================
// LLAMAR API DE RESEND
// ======================================================

$curl = curl_init(
    'https://api.resend.com/emails'
);

curl_setopt_array(
    $curl,
    [

        CURLOPT_POST => true,

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_CONNECTTIMEOUT => 10,

        CURLOPT_TIMEOUT => 20,

        CURLOPT_HTTPHEADER => [

            'Authorization: Bearer ' .
                $resendApiKey,

            'Content-Type: application/json',

        ],

        CURLOPT_POSTFIELDS =>
            json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE
            ),

    ]
);


$response = curl_exec($curl);

$httpCode = curl_getinfo(
    $curl,
    CURLINFO_HTTP_CODE
);

$curlError = curl_error($curl);

curl_close($curl);


// ======================================================
// ERROR DE CONEXIÓN
// ======================================================

if ($response === false || $curlError !== '') {

    error_log(
        'Error conectando con Resend: ' .
        $curlError
    );

    http_response_code(500);

    echo '
        <h2>No se pudo enviar el mensaje</h2>

        <p>
            Ocurrió un error al conectar con el
            servicio de correo.
        </p>

        <p>
            Intenta nuevamente más tarde.
        </p>

        <p>
            <a href="index.html">
                Volver
            </a>
        </p>
    ';

    exit;
}


// ======================================================
// ERROR DEVUELTO POR RESEND
// ======================================================

if ($httpCode < 200 || $httpCode >= 300) {

    error_log(
        'Resend HTTP ' .
        $httpCode .
        ': ' .
        $response
    );

    http_response_code(500);

    echo '
        <h2>No se pudo enviar el mensaje</h2>

        <p>
            Ocurrió un error procesando
            el correo.
        </p>

        <p>
            Intenta nuevamente más tarde
            o escríbenos directamente a
            contacto@bluelionsacademy.com.
        </p>

        <p>
            <a href="index.html">
                Volver
            </a>
        </p>
    ';

    exit;
}


// ======================================================
// RESPUESTA EXITOSA
// ======================================================

$nombreSeguro = htmlspecialchars(
    $nombre,
    ENT_QUOTES,
    'UTF-8'
);

echo <<<HTML

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Mensaje enviado</title>

    <meta
        http-equiv="refresh"
        content="4;url=index.html"
    >

    <style>

        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 4rem 1rem;
            color: #132044;
            background: #f8fafc;
        }

        .card {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem;
            background: white;
            border-radius: 16px;
            box-shadow:
                0 8px 30px
                rgba(0, 0, 0, 0.08);
        }

        h2 {
            color: #132044;
        }

        a {
            color: #207AB6;
        }

    </style>

</head>

<body>

    <div class="card">

        <h2>
            ¡Gracias, {$nombreSeguro}!
        </h2>

        <p>
            Tu mensaje fue enviado correctamente.
        </p>

        <p>
            Nos pondremos en contacto contigo pronto.
        </p>

        <p>
            Serás redirigido al sitio en unos segundos.
        </p>

        <p>
            <a href="index.html">
                Volver a Blue Lions Academy
            </a>
        </p>

    </div>

</body>

</html>

HTML;