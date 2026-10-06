<?php
// public.php — Plugin WhatsApp para UISP
// Maneja todos los eventos de webhook de UISP y envía notificaciones vía OpenWA.

// =====================================================================
// 1. CONFIGURACIÓN
// =====================================================================
$configPath = __DIR__ . '/data/config.json';
$config = json_decode(file_get_contents($configPath), true);
$openwaUrl = $config['openwa_url'];
$apiKey = $config['openwa_api_key'];
$sessionId = $config['openwa_session_id'];
$ucrmPublicUrl = $config['ucrm_public_url'] ?? '';

// Credenciales UISP
$ucrmJsonPath = __DIR__ . '/ucrm.json';
$ucrmConfig = json_decode(file_get_contents($ucrmJsonPath), true);
$ucrmApiUrl = rtrim($ucrmConfig['ucrmLocalUrl'], '/') . '/api/v1.0';
$ucrmAppKey = $ucrmConfig['pluginAppKey'];

// Base URL de OpenWA para mensajes
$openwaBase = rtrim($openwaUrl, '/') . '/sessions/' . $sessionId . '/messages';

// Ruta del archivo de rate-limit
$alertLogPath = __DIR__ . '/data/alert_log.json';

// Intervalo anti-spam en segundos (60 minutos)
define('RATE_LIMIT_SECONDS', 3600);

// =====================================================================
// 2. FUNCIONES AUXILIARES
// =====================================================================

/**
 * Envía un mensaje de texto vía OpenWA.
 */
function sendTextMessage($openwaBase, $apiKey, $chatId, $text) {
    $data = [
        'chatId' => $chatId,
        'text'   => $text
    ];
    $ch = curl_init($openwaBase . '/send-text');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'response' => $response];
}

/**
 * Envía un documento (PDF) vía OpenWA usando base64.
 */
function sendDocumentMessage($openwaBase, $apiKey, $chatId, $base64Data, $filename) {
    $data = [
        'chatId'   => $chatId,
        'base64'   => $base64Data,
        'mimetype' => 'application/pdf',
        'filename' => $filename
    ];
    $ch = curl_init($openwaBase . '/send-document');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'response' => $response];
}

/**
 * Descarga un PDF de factura desde la API de UISP.
 * Retorna la cadena base64 o null si falla.
 */
function downloadInvoicePdf($ucrmApiUrl, $ucrmAppKey, $invoiceId) {
    $ch = curl_init($ucrmApiUrl . "/invoices/{$invoiceId}/pdf");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Auth-App-Key: ' . $ucrmAppKey]);
    $pdfData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || empty($pdfData)) {
        return null;
    }
    return base64_encode($pdfData);
}

/**
 * Verifica si un evento para un cliente ha excedido el límite de envíos.
 * @param string $alertLogPath Ruta del archivo de log.
 * @param string $clientId ID del cliente o entidad.
 * @param string $eventName Nombre del evento.
 * @param int $maxSends Número máximo de envíos permitidos en el período.
 * @param int $periodSeconds Período en segundos (ej: 86400 para 24 horas, 1800 para 30 min).
 * @return bool true si se puede enviar, false si se debe bloquear.
 */
function checkRateLimitAdvanced($alertLogPath, $clientId, $eventName, $maxSends, $periodSeconds) {
    $log = [];
    if (file_exists($alertLogPath)) {
        $log = json_decode(file_get_contents($alertLogPath), true) ?: [];
    }

    $key = $clientId . '_' . $eventName;
    $now = time();

    // Obtener los timestamps de esta alerta
    $timestamps = isset($log[$key]) && is_array($log[$key]) ? $log[$key] : [];

    // Compatibilidad con la versión anterior que guardaba un integer
    if (!is_array($timestamps) && !empty($timestamps)) {
        $timestamps = [$timestamps];
    } elseif (empty($timestamps)) {
        $timestamps = [];
    }

    // Filtrar timestamps que estén dentro del período
    $validTimestamps = [];
    foreach ($timestamps as $t) {
        if (($now - $t) <= $periodSeconds) {
            $validTimestamps[] = $t;
        }
    }

    // Limpieza general del log para que no crezca infinitamente
    foreach ($log as $k => $times) {
        if (!is_array($times)) $times = [$times];
        $filtered = array_filter($times, function($t) use ($now) {
            return ($now - $t) <= 86400; // Mantener histórico máximo de 24h
        });
        if (empty($filtered)) {
            unset($log[$k]);
        } else {
            $log[$k] = array_values($filtered);
        }
    }

    // Si ya enviamos el máximo permitido, bloqueamos
    if (count($validTimestamps) >= $maxSends) {
        file_put_contents($alertLogPath, json_encode($log, JSON_PRETTY_PRINT));
        return false;
    }

    // Registrar este nuevo envío
    $validTimestamps[] = $now;
    $log[$key] = $validTimestamps;
    
    file_put_contents($alertLogPath, json_encode($log, JSON_PRETTY_PRINT));
    return true;
}

/**
 * Obtiene los datos de un cliente desde la API de UISP.
 */
function getClientData($ucrmApiUrl, $ucrmAppKey, $clientId) {
    $ch = curl_init($ucrmApiUrl . "/clients/" . $clientId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Auth-App-Key: ' . $ucrmAppKey]);
    $data = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $data;
}

/**
 * Extrae y formatea el número de teléfono del cliente para WhatsApp.
 */
function getClientPhone($clientData) {
    $phone = preg_replace('/[^0-9]/', '', $clientData['contacts'][0]['phone'] ?? '');
    if (empty($phone)) return null;
    // Agregar código de país Colombia si tiene 10 dígitos
    $phone = (strlen($phone) == 10 ? '57' . $phone : $phone) . '@c.us';
    return $phone;
}

/**
 * Obtiene el nombre completo del cliente.
 */
function getClientName($clientData) {
    $first = $clientData['firstName'] ?? '';
    $last = $clientData['lastName'] ?? '';
    $company = $clientData['companyName'] ?? '';
    $name = trim($first . ' ' . $last);
    return !empty($name) ? $name : $company;
}

/**
 * Formatea una fecha ISO a formato legible dd/mm/aaaa.
 */
function formatDate($isoDate) {
    if (empty($isoDate)) return 'N/A';
    $dt = new DateTime($isoDate);
    return $dt->format('d/m/Y');
}

/**
 * Formatea un valor monetario.
 */
function formatMoney($amount, $currency = 'COP') {
    return '$' . number_format($amount, 0, ',', '.') . ' ' . $currency;
}

// =====================================================================
// 3. PROCESAMIENTO DEL WEBHOOK
// =====================================================================

$payload = file_get_contents('php://input');
$event = json_decode($payload, true);

if (!$event || !isset($event['eventName'])) {
    http_response_code(400);
    die('Payload inválido');
}

$eventName = $event['eventName'];
$entityData = $event['extraData']['entity'] ?? [];
$clientId = $entityData['clientId'] ?? null;

// Eventos de tipo 'client' usan 'id' directamente como clientId
if (!$clientId && strpos($eventName, 'client.') === 0) {
    $clientId = $entityData['id'] ?? null;
}

// Si no hay clientId, no podemos enviar mensaje
if (!$clientId) {
    http_response_code(200);
    die("Sin clientId para evento {$eventName}");
}

// Obtener datos del cliente desde UISP
$clientData = getClientData($ucrmApiUrl, $ucrmAppKey, $clientId);
if (!$clientData) {
    http_response_code(200);
    die("No se pudo obtener datos del cliente {$clientId}");
}

$phone = getClientPhone($clientData);
if (!$phone) {
    http_response_code(200);
    die("Cliente {$clientId} sin teléfono registrado");
}

$clientName = getClientName($clientData);
$orgName = $entityData['organizationName'] ?? 'nuestra empresa';
$currency = $entityData['currencyCode'] ?? 'COP';

// =====================================================================
// 4. MANEJO DE EVENTOS
// =====================================================================

$result = null;
$eventHandled = false;

switch (true) {

    // -----------------------------------------------------------------
    // FACTURAS
    // -----------------------------------------------------------------

    // Nueva factura creada
    case ($eventName === 'invoice.add'):
        $invoiceId = $entityData['id'];
        $invoiceNumber = $entityData['number'] ?? 'S/N';
        $total = $entityData['total'] ?? 0;
        $dueDate = formatDate($entityData['dueDate'] ?? '');

        // Descargar y enviar PDF
        $base64Pdf = downloadInvoicePdf($ucrmApiUrl, $ucrmAppKey, $invoiceId);
        if ($base64Pdf) {
            $result = sendDocumentMessage(
                $openwaBase, $apiKey, $phone,
                $base64Pdf,
                "Factura_{$invoiceNumber}.pdf"
            );
        }

        // Enviar mensaje de texto informativo
        $msg = "📄 *Nueva Factura*\n\n"
             . "Hola *{$clientName}*, se ha generado tu factura:\n\n"
             . "• Factura N°: *{$invoiceNumber}*\n"
             . "• Total: *" . formatMoney($total, $currency) . "*\n"
             . "• Vence: *{$dueDate}*\n\n"
             . "Si ya realizaste el pago, puedes ignorar este mensaje. ¡Gracias!";
        $result = sendTextMessage($openwaBase, $apiKey, $phone, $msg);
        $eventHandled = true;
        break;

    // Factura editada
    case ($eventName === 'invoice.edit'):
        // No notificar ediciones menores de factura
        $eventHandled = true;
        break;

    // Factura próxima a vencer
    case ($eventName === 'invoice.near_due'):
        if (!checkRateLimitAdvanced($alertLogPath, $clientId, $eventName, 2, 86400)) {
            http_response_code(200);
            die("Rate-limit: ya se notificó {$eventName} al cliente {$clientId}");
        }
        $invoiceNumber = $entityData['number'] ?? 'S/N';
        $total = $entityData['total'] ?? $entityData['amountToPay'] ?? 0;
        $dueDate = formatDate($entityData['dueDate'] ?? '');

        $msg = "⏰ *Recordatorio de Pago*\n\n"
             . "Hola *{$clientName}*, tu factura *{$invoiceNumber}* por *" . formatMoney($total, $currency) . "* está próxima a vencer el *{$dueDate}*.\n\n"
             . "Te invitamos a realizar tu pago para evitar la suspensión del servicio.";
        $result = sendTextMessage($openwaBase, $apiKey, $phone, $msg);
        $eventHandled = true;
        break;

    // Factura vencida
    case ($eventName === 'invoice.overdue'):
        if (!checkRateLimitAdvanced($alertLogPath, $clientId, $eventName, 2, 86400)) {
            http_response_code(200);
            die("Rate-limit: ya se notificó {$eventName} al cliente {$clientId}");
        }
        $invoiceNumber = $entityData['number'] ?? 'S/N';
        $total = $entityData['total'] ?? $entityData['amountToPay'] ?? 0;

        $msg = "🔴 *Factura Vencida*\n\n"
             . "Hola *{$clientName}*, tu factura *{$invoiceNumber}* por *" . formatMoney($total, $currency) . "* se encuentra vencida.\n\n"
             . "Por favor realiza tu pago lo antes posible para evitar la suspensión de tu servicio.";
        $result = sendTextMessage($openwaBase, $apiKey, $phone, $msg);
        $eventHandled = true;
        break;

    // -----------------------------------------------------------------
    // PAGOS
    // -----------------------------------------------------------------

    // Pago recibido
    case ($eventName === 'payment.add'):
        $amount = $entityData['amount'] ?? 0;
        $method = $entityData['methodName'] ?? $entityData['providerName'] ?? 'registrado';
        $paymentNote = $entityData['note'] ?? '';

        $msg = "✅ *Pago Recibido*\n\n"
             . "Hola *{$clientName}*, hemos recibido tu pago:\n\n"
             . "• Monto: *" . formatMoney($amount, $currency) . "*\n"
             . "• Método: *{$method}*\n\n"
             . "¡Gracias por tu pago!";
        $result = sendTextMessage($openwaBase, $apiKey, $phone, $msg);
        $eventHandled = true;
        break;

    // -----------------------------------------------------------------
    // SERVICIOS
    // -----------------------------------------------------------------

    // Servicio suspendido
    case ($eventName === 'service.suspend'):
        if (!checkRateLimitAdvanced($alertLogPath, $clientId, $eventName, 2, 86400)) {
            http_response_code(200);
            die("Rate-limit: ya se notificó {$eventName} al cliente {$clientId}");
        }
        $serviceName = $entityData['name'] ?? 'tu servicio de internet';

        $msg = "🚫 *Servicio Suspendido*\n\n"
             . "Hola *{$clientName}*, tu servicio *{$serviceName}* ha sido suspendido por falta de pago.\n\n"
             . "Para reactivar tu conexión, realiza el pago pendiente y contáctanos. ¡Estamos para ayudarte!";
        $result = sendTextMessage($openwaBase, $apiKey, $phone, $msg);
        $eventHandled = true;
        break;

    // Suspensión cancelada (servicio reactivado)
    case ($eventName === 'service.suspend_cancel'):
        $serviceName = $entityData['name'] ?? 'tu servicio de internet';

        $msg = "🟢 *Servicio Reactivado*\n\n"
             . "Hola *{$clientName}*, tu servicio *{$serviceName}* ha sido reactivado exitosamente.\n\n"
             . "¡Disfruta tu conexión! Gracias por estar al día.";
        $result = sendTextMessage($openwaBase, $apiKey, $phone, $msg);
        $eventHandled = true;
        break;

    // Servicio editado / cambios
    case ($eventName === 'service.edit'):
        if (!checkRateLimitAdvanced($alertLogPath, $clientId, $eventName, 1, 1800)) {
            http_response_code(200);
            die("Rate-limit: ya se notificó {$eventName} al cliente {$clientId}");
        }
        $serviceName = $entityData['name'] ?? 'tu servicio';

        $msg = "ℹ️ *Actualización de Servicio*\n\n"
             . "Hola *{$clientName}*, se han realizado cambios en tu servicio *{$serviceName}*.\n\n"
             . "Si tienes dudas sobre los cambios, no dudes en contactarnos.";
        $result = sendTextMessage($openwaBase, $apiKey, $phone, $msg);
        $eventHandled = true;
        break;

    // Alerta de interrupción de servicio (outage)
    case ($eventName === 'service.outage'):
        if (!checkRateLimitAdvanced($alertLogPath, $clientId, $eventName, 1, 1800)) {
            http_response_code(200);
            die("Rate-limit: ya se notificó {$eventName} al cliente {$clientId}");
        }
        $serviceName = $entityData['name'] ?? 'tu servicio';

        $msg = "⚠️ *Interrupción de Servicio*\n\n"
             . "Hola *{$clientName}*, hemos detectado una interrupción en tu servicio.\n\n"
             . "Nuestro equipo técnico ya está trabajando para solucionarlo. Agradecemos tu paciencia.";
        $result = sendTextMessage($openwaBase, $apiKey, $phone, $msg);
        $eventHandled = true;
        break;

    // Nuevo servicio
    case ($eventName === 'service.add'):
        $serviceName = $entityData['name'] ?? 'nuevo servicio';

        $msg = "🆕 *Nuevo Servicio Activado*\n\n"
             . "Hola *{$clientName}*, tu servicio *{$serviceName}* ha sido activado.\n\n"
             . "¡Bienvenido/a y disfruta tu conexión!";
        $result = sendTextMessage($openwaBase, $apiKey, $phone, $msg);
        $eventHandled = true;
        break;

    // Suspensión pospuesta
    case ($eventName === 'service.postpone'):
        $serviceName = $entityData['name'] ?? 'tu servicio';

        $msg = "⏳ *Suspensión Pospuesta*\n\n"
             . "Hola *{$clientName}*, la suspensión de tu servicio *{$serviceName}* ha sido pospuesta.\n\n"
             . "Te recomendamos realizar tu pago pronto para evitar la interrupción del servicio.";
        $result = sendTextMessage($openwaBase, $apiKey, $phone, $msg);
        $eventHandled = true;
        break;

    // -----------------------------------------------------------------
    // CLIENTES
    // -----------------------------------------------------------------

    // Nuevo cliente
    case ($eventName === 'client.add'):
        $msg = "👋 *¡Bienvenido/a!*\n\n"
             . "Hola *{$clientName}*, gracias por elegir *{$orgName}*.\n\n"
             . "Este es nuestro canal de comunicación por WhatsApp. Aquí recibirás tus facturas, confirmaciones de pago y avisos importantes sobre tu servicio.\n\n"
             . "¡Estamos para servirte!";
        $result = sendTextMessage($openwaBase, $apiKey, $phone, $msg);
        $eventHandled = true;
        break;

    // -----------------------------------------------------------------
    // EVENTOS NO MANEJADOS
    // -----------------------------------------------------------------
    default:
        http_response_code(200);
        die("Evento '{$eventName}' no requiere notificación WhatsApp.");
        break;
}

// =====================================================================
// 5. RESPUESTA FINAL
// =====================================================================

if ($result) {
    http_response_code(200);
    echo "Evento: {$eventName} | OpenWA ({$result['code']}) | chatId: {$phone} | Respuesta: {$result['response']}";
} else {
    http_response_code(200);
    echo "Evento: {$eventName} | Procesado (sin envío WhatsApp)";
}
?>