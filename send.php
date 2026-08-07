<?php
/**
 * send.php — Procesa el formulario de contacto de Blue Lion Academy
 * y envía un correo a la dirección de la academia.
 *
 * Requisitos del hosting:
 * - Soporte PHP (la mayoría de los hostings compartidos lo tienen).
 * - La función mail() habilitada. Si los correos no llegan o caen en spam,
 *   revisa con tu hosting si necesitas usar SMTP autenticado en su lugar
 *   (te puedo armar esa versión con PHPMailer si hace falta).
 */

// ⚠️ CONFIRMA este correo antes de subir el archivo
$destinatario = "info@bluelionacademy.net";

// Solo aceptar envíos por POST (evita accesos directos por URL)
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.html");
    exit;
}

// Campo trampa anti-spam (bots suelen rellenar todos los campos, incluido este oculto)
if (!empty($_POST['sitio_web'])) {
    exit; // si el campo oculto viene lleno, es un bot: no hacemos nada
}

// Recoger y limpiar los datos del formulario
function limpiar($valor) {
    return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
}

$nombre   = limpiar($_POST['nombre'] ?? '');
$email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$telefono = limpiar($_POST['telefono'] ?? '');
$grado    = limpiar($_POST['grado'] ?? '');
$mensaje  = limpiar($_POST['mensaje'] ?? '');

// Validación mínima de campos obligatorios
$errores = [];
if ($nombre === '') $errores[] = "El nombre es obligatorio.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = "El correo no es válido.";
if ($mensaje === '') $errores[] = "El mensaje es obligatorio.";

if (!empty($errores)) {
    http_response_code(400);
    echo "<h2>Hubo un problema con tu envío</h2><ul>";
    foreach ($errores as $e) echo "<li>" . $e . "</li>";
    echo "</ul><p><a href='index.html'>Volver</a></p>";
    exit;
}

// Armar el correo
$asunto = "Nuevo contacto desde el sitio web - Blue Lion Academy";

$cuerpo  = "Se recibió un nuevo mensaje desde el formulario de contacto:\n\n";
$cuerpo .= "Nombre: $nombre\n";
$cuerpo .= "Email: $email\n";
$cuerpo .= "Teléfono: " . ($telefono !== '' ? $telefono : "No proporcionado") . "\n";
$cuerpo .= "Grado de interés: " . ($grado !== '' ? $grado : "No especificado") . "\n\n";
$cuerpo .= "Mensaje:\n$mensaje\n";

// Cabeceras: el "From" debe ser del propio dominio para evitar que el
// correo se marque como spam; el "Reply-To" es el correo de la persona
// que llenó el formulario, para que puedas responderle directo.
$headers  = "From: Sitio Web Blue Lion Academy <noreply@bluelionacademy.net>\r\n";
$headers .= "Reply-To: $nombre <$email>\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$enviado = mail($destinatario, $asunto, $cuerpo, $headers);

if ($enviado) {
    echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'>
    <title>Mensaje enviado</title>
    <meta http-equiv='refresh' content='4;url=index.html'>
    <style>body{font-family:sans-serif;text-align:center;padding:4rem 1rem;color:#0a2540;}</style>
    </head><body>
    <h2>¡Gracias, $nombre!</h2>
    <p>Tu mensaje fue enviado correctamente. Nos pondremos en contacto contigo pronto.</p>
    <p>Serás redirigido al sitio en unos segundos, o <a href='index.html'>haz clic aquí</a>.</p>
    </body></html>";
} else {
    http_response_code(500);
    echo "<h2>No se pudo enviar el mensaje</h2>
    <p>Ocurrió un error en el servidor. Por favor intenta de nuevo más tarde o escríbenos directamente a $destinatario.</p>
    <p><a href='index.html'>Volver</a></p>";
}