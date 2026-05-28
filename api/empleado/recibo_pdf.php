<?php
require_once "../../config/database.php";
require_once "../../core/Auth.php";
require_once "../../core/middleware.php";
require_once "../../core/helpers.php";

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole([ROLE_ADMIN, ROLE_RECEPCION]);
Middleware::checkSessionTimeout();

$facturaId = intval($_GET['id'] ?? 0);
if ($facturaId <= 0) {
    http_response_code(404);
    echo 'Factura no encontrada.';
    exit();
}

$stmt = $conn->prepare(
    "SELECT f.*, c.id_cita, c.fecha_inicio, CONCAT(u.nombre, ' ', u.apellido) AS cliente_nombre, u.email, u.telefono
        FROM factura f
        LEFT JOIN cita c ON f.id_cita = c.id_cita
        LEFT JOIN mascota m ON c.id_mascota = m.id_mascota
        LEFT JOIN cliente cl ON m.id_cliente_principal = cl.id_cliente
        LEFT JOIN usuario u ON cl.id_cliente = u.id_usuario
        WHERE f.id_factura = ?"
);
$stmt->execute([$facturaId]);
$factura = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$factura) {
    http_response_code(404);
    echo 'Factura no encontrada.';
    exit();
}

$stmt = $conn->prepare("SELECT concepto, cantidad, precio_unitario, subtotal FROM detalle_factura WHERE id_factura = ? ORDER BY id_factura");
$stmt->execute([$facturaId]);
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT monto, metodo_pago, referencia_transaccion, estado_pago FROM pago WHERE id_factura = ? ORDER BY id_pago DESC LIMIT 1");
$stmt->execute([$facturaId]);
$pago = $stmt->fetch(PDO::FETCH_ASSOC);

// Crear el PDF
$pdf = buildSimplePdf($factura, $detalles, $pago);

// Verificar que el PDF no esté vacío
if (empty($pdf)) {
    die('Error al generar el PDF');
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="recibo_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $factura['numero_factura']) . '.pdf"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

echo $pdf;
exit();

function pdfEscape(string $text): string {
    // Eliminar caracteres especiales que puedan causar problemas
    $text = preg_replace('/[\\x00-\\x1F\\x7F]/', '', $text);
    return '(' . str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], utf8_decode($text)) . ')';
}

function addText($content, $text, $x, $y, $font = 'F1', $size = 10) {
    $content .= "BT\n";
    $content .= "/$font $size Tf\n";
    $content .= "$x $y Td\n";
    $content .= pdfEscape($text) . " Tj\n";
    $content .= "ET\n";
    return $content;
}

function buildSimplePdf($factura, $detalles, $pago) {
    $content = "";
    
    // Configuración inicial
    $content .= "BT\n/F1 10 Tf\nET\n";
    
    // Posiciones Y (desde arriba hacia abajo)
    $y = 800;
    
    // Título principal
    $content = addText($content, "PET SPA", 220, $y, "F2", 18);
    $y -= 30;
    
    $content = addText($content, "RECIBO / FACTURA", 210, $y, "F2", 14);
    $y -= 35;
    
    // Línea separadora
    $content .= "q\n";
    $content .= "2 w\n";
    $content .= "50 $y m\n";
    $content .= "545 $y l\n";
    $content .= "S\n";
    $content .= "Q\n";
    $y -= 30;
    
    // Información de la factura
    $content = addText($content, "N° FACTURA: " . $factura['numero_factura'], 50, $y, "F2", 10);
    $content = addText($content, "FECHA: " . $factura['fecha_emision'], 350, $y, "F1", 10);
    $y -= 25;
    
    $content = addText($content, "CLIENTE: " . $factura['cliente_nombre'], 50, $y, "F2", 10);
    $y -= 20;
    
    if (!empty($factura['email'])) {
        $content = addText($content, "EMAIL: " . $factura['email'], 50, $y, "F1", 9);
        $y -= 18;
    }
    
    if (!empty($factura['telefono'])) {
        $content = addText($content, "TELÉFONO: " . $factura['telefono'], 50, $y, "F1", 9);
        $y -= 18;
    }
    
    if (!empty($factura['id_cita'])) {
        $content = addText($content, "CITA ID: " . $factura['id_cita'], 50, $y, "F1", 9);
        $y -= 18;
        $content = addText($content, "FECHA CITA: " . $factura['fecha_inicio'], 50, $y, "F1", 9);
        $y -= 25;
    } else {
        $y -= 15;
    }
    
    // Cabecera de la tabla
    $content .= "q\n";
    $content .= "1 w\n";
    $content .= "50 " . ($y - 2) . " 495 22 re\n";
    $content .= "S\n";
    $content .= "Q\n";
    
    $content = addText($content, "CONCEPTO", 55, $y, "F2", 10);
    $content = addText($content, "CANT.", 300, $y, "F2", 10);
    $content = addText($content, "P.UNIT.", 370, $y, "F2", 10);
    $content = addText($content, "SUBTOTAL", 460, $y, "F2", 10);
    $y -= 25;
    
    // Detalles de la factura
    foreach ($detalles as $d) {
        // Evitar que se salga de la página
        if ($y < 100) {
            break;
        }
        
        $content .= "q\n";
        $content .= "1 w\n";
        $content .= "50 " . ($y - 2) . " 495 20 re\n";
        $content .= "S\n";
        $content .= "Q\n";
        
        $concepto = strlen($d['concepto']) > 35 ? substr($d['concepto'], 0, 32) . '...' : $d['concepto'];
        $content = addText($content, $concepto, 55, $y, "F1", 9);
        $content = addText($content, $d['cantidad'], 310, $y, "F1", 9);
        $content = addText($content, number_format($d['precio_unitario'], 2), 375, $y, "F1", 9);
        $content = addText($content, number_format($d['subtotal'], 2), 455, $y, "F1", 9);
        $y -= 22;
    }
    
    $y -= 10;
    
    // Totales
    $content = addText($content, "SUBTOTAL:", 360, $y, "F2", 10);
    $content = addText($content, "Bs. " . number_format($factura['subtotal'], 2), 460, $y, "F1", 10);
    $y -= 20;
    
    $content = addText($content, "IMPUESTO:", 360, $y, "F2", 10);
    $content = addText($content, "Bs. " . number_format($factura['impuesto'], 2), 460, $y, "F1", 10);
    $y -= 20;
    
    // Línea separadora antes del total
    $content .= "q\n";
    $content .= "0.5 w\n";
    $content .= "350 " . ($y + 5) . " m\n";
    $content .= "545 " . ($y + 5) . " l\n";
    $content .= "S\n";
    $content .= "Q\n";
    
    $content = addText($content, "TOTAL:", 360, $y, "F2", 14);
    $content = addText($content, "Bs. " . number_format($factura['total'], 2), 455, $y, "F2", 14);
    $y -= 40;
    
    // Información de pago
    if ($pago && $pago['monto'] > 0) {
        $content .= "q\n";
        $content .= "0.5 w\n";
        $content .= "50 $y m\n";
        $content .= "545 $y l\n";
        $content .= "S\n";
        $content .= "Q\n";
        $y -= 15;
        
        $content = addText($content, "INFORMACIÓN DE PAGO", 50, $y, "F2", 11);
        $y -= 22;
        
        $content = addText($content, "Monto pagado: Bs. " . number_format($pago['monto'], 2), 60, $y, "F1", 9);
        $y -= 16;
        
        $content = addText($content, "Método de pago: " . strtoupper($pago['metodo_pago']), 60, $y, "F1", 9);
        $y -= 16;
        
        if (!empty($pago['referencia_transaccion'])) {
            $content = addText($content, "Referencia: " . $pago['referencia_transaccion'], 60, $y, "F1", 9);
            $y -= 16;
        }
        
        $content = addText($content, "Estado: " . strtoupper($pago['estado_pago']), 60, $y, "F1", 9);
        $y -= 30;
    }
    
    // Mensaje de agradecimiento
    $content = addText($content, "¡Gracias por preferir Pet Spa!", 190, $y, "F2", 11);
    
    // Construir el PDF
    $pdf = "%PDF-1.4\n";
    
    // Objetos del PDF
    $objects = [
        "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj",
        "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj",
        "3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 595 842]\n/Resources <<\n/Font <<\n/F1 4 0 R\n/F2 5 0 R\n>>\n>>\n/Contents 6 0 R\n>>\nendobj",
        "4 0 obj\n<<\n/Type /Font\n/Subtype /Type1\n/BaseFont /Helvetica\n>>\nendobj",
        "5 0 obj\n<<\n/Type /Font\n/Subtype /Type1\n/BaseFont /Helvetica-Bold\n>>\nendobj",
        "6 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream\nendobj"
    ];
    
    $offsets = [0];
    $pos = strlen($pdf);
    
    foreach ($objects as $obj) {
        $offsets[] = $pos;
        $pdf .= $obj . "\n";
        $pos = strlen($pdf);
    }
    
    $xrefStart = $pos;
    
    $pdf .= "xref\n0 " . count($offsets) . "\n";
    $pdf .= sprintf("%010d 65535 f \n", 0);
    
    for ($i = 1; $i < count($offsets); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    
    $pdf .= "trailer\n<<\n/Size " . count($offsets) . "\n/Root 1 0 R\n>>\n";
    $pdf .= "startxref\n" . $xrefStart . "\n%%EOF";
    
    return $pdf;
}
?>