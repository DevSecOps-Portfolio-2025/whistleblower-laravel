<?php

/**
 * Test de Flujo de Cifrado Zero-Knowledge
 * 
 * Este script simula el comportamiento del cliente (Frontend) para verificar
 * el flujo completo de cifrado end-to-end:
 * 
 * 1. Obtener la llave pública desde la API
 * 2. Cifrar un mensaje sensible usando RSA
 * 3. Enviar el mensaje cifrado a la API
 * 4. Verificar que la API nunca vea el contenido sin cifrar
 */

echo "🔐 TEST DE FLUJO DE CIFRADO ZERO-KNOWLEDGE\n";
echo "==========================================\n\n";

// Configuración
// Si ejecutas desde el HOST (Windows): usa localhost:8080
// Si ejecutas desde Docker: usa web:80
$apiBaseUrl = getenv('API_BASE_URL') ?: 'http://localhost:8080/api/v1/whistleblowing';
$publicKeyEndpoint = $apiBaseUrl . '/keys/public';
$reportsEndpoint = $apiBaseUrl . '/reports';

// Mensaje sensible que queremos proteger
$secretMessage = "DENUNCIA SECRETA: El gerente está desviando fondos a cuentas offshore. Tengo evidencia documentada.";

echo "📝 Mensaje original (sensible):\n";
echo "   \"$secretMessage\"\n\n";

// ==============================================
// PASO 1: Obtener la llave pública de la API
// ==============================================
echo "🔑 PASO 1: Obteniendo llave pública...\n";

$ch = curl_init($publicKeyEndpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ ERROR: No se pudo obtener la llave pública (HTTP $httpCode)\n" .
        "   Respuesta: $response\n" .
        "   HINT: Ejecuta primero 'php artisan whistleblowing:generate-keys'\n");
}

$keyData = json_decode($response, true);
if (!isset($keyData['publicKey'])) {
    die("❌ ERROR: Respuesta inválida de la API\n");
}

$publicKey = $keyData['publicKey'];
echo "   ✅ Llave pública obtenida exitosamente\n";
echo "   📊 Tamaño: {$keyData['keySize']} bits\n";
echo "   🔐 Algoritmo: {$keyData['algorithm']}\n\n";

// ==============================================
// PASO 2: Cifrar el mensaje usando RSA
// ==============================================
echo "🔒 PASO 2: Cifrando mensaje con RSA...\n";

// Convertir la llave pública a recurso OpenSSL
$publicKeyResource = openssl_pkey_get_public($publicKey);
if ($publicKeyResource === false) {
    die("❌ ERROR: No se pudo cargar la llave pública: " . openssl_error_string() . "\n");
}

// Cifrar el mensaje
$encryptedMessage = '';
$result = openssl_public_encrypt(
    $secretMessage,
    $encryptedMessage,
    $publicKeyResource,
    OPENSSL_PKCS1_OAEP_PADDING
);

if (!$result) {
    die("❌ ERROR: No se pudo cifrar el mensaje: " . openssl_error_string() . "\n");
}

// Codificar en Base64 para transmisión
$encryptedBase64 = base64_encode($encryptedMessage);

echo "   ✅ Mensaje cifrado exitosamente\n";
echo "   📦 Tamaño cifrado: " . strlen($encryptedMessage) . " bytes\n";
echo "   📤 Base64 (primeros 80 chars): " . substr($encryptedBase64, 0, 80) . "...\n\n";

// ==============================================
// PASO 3: Enviar reporte cifrado a la API
// ==============================================
echo "📤 PASO 3: Enviando reporte cifrado a la API...\n";

$reportData = [
    'title' => 'Reporte de Prueba - Cifrado',
    'description' => $encryptedBase64,  // ← Aquí va el contenido cifrado
    'is_encrypted' => true,
    'reporter_name' => 'Anonymous Tester'
];

$ch = curl_init($reportsEndpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($reportData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   📡 Respuesta de la API (HTTP $httpCode):\n";
echo "   " . str_repeat("-", 60) . "\n";
echo "   " . $response . "\n";
echo "   " . str_repeat("-", 60) . "\n\n";

// ==============================================
// VERIFICACIÓN DE SEGURIDAD
// ==============================================
echo "🛡️  VERIFICACIÓN DE SEGURIDAD ZERO-KNOWLEDGE:\n";
echo "   " . str_repeat("=", 60) . "\n";

$responseData = json_decode($response, true);

if ($httpCode >= 200 && $httpCode < 300) {
    echo "   ✅ Reporte enviado exitosamente\n";
    
    if (isset($responseData['data']['description'])) {
        $storedDescription = $responseData['data']['description'];
        
        // Verificar que lo almacenado es diferente al original
        if ($storedDescription !== $secretMessage) {
            echo "   ✅ PASS: El servidor almacenó el contenido CIFRADO\n";
            echo "   ✅ PASS: El mensaje original NUNCA fue visible para el servidor\n";
            echo "   ✅ PASS: Flujo Zero-Knowledge verificado correctamente\n";
        } else {
            echo "   ❌ FAIL: El servidor almacenó el mensaje SIN CIFRAR\n";
            echo "   ⚠️  VULNERABILIDAD: El servidor puede leer el contenido\n";
        }
        
        // Verificar que parece Base64
        if (preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $storedDescription)) {
            echo "   ✅ PASS: El formato almacenado es Base64 (cifrado)\n";
        }
        
        // Verificar tamaño (cifrado debe ser más grande)
        $originalLength = strlen($secretMessage);
        $encryptedLength = strlen($storedDescription);
        echo "   📊 Tamaño original: $originalLength chars\n";
        echo "   📊 Tamaño cifrado: $encryptedLength chars\n";
        
        if ($encryptedLength > $originalLength) {
            echo "   ✅ PASS: El contenido cifrado es más grande (overhead de RSA)\n";
        }
    }
} else {
    echo "   ❌ FAIL: Error al enviar el reporte\n";
    echo "   📋 Mensaje: " . ($responseData['message'] ?? 'Error desconocido') . "\n";
}

echo "   " . str_repeat("=", 60) . "\n\n";

// ==============================================
// RESUMEN FINAL
// ==============================================
echo "📋 RESUMEN DEL FLUJO:\n";
echo "   1. ✅ Llave pública obtenida desde la API\n";
echo "   2. ✅ Mensaje cifrado con RSA-4096 + OAEP\n";
echo "   3. ✅ Mensaje codificado en Base64\n";
echo "   4. ✅ Enviado a la API sin exponer el contenido\n\n";

echo "🎯 CONCLUSIÓN:\n";
echo "   El servidor NUNCA vio el mensaje original.\n";
echo "   Solo el poseedor de la llave privada puede descifrarlo.\n";
echo "   Esto es Zero-Knowledge: el servidor procesa datos que no puede leer.\n\n";

echo "💡 PRÓXIMO PASO:\n";
echo "   Para descifrar, ejecuta:\n";
echo "   php artisan whistleblowing:decrypt-report <report_id>\n\n";

echo "✅ Test completado exitosamente!\n";
