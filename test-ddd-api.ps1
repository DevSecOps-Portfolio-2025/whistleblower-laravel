# Script de Prueba - Arquitectura DDD Whistleblowing
# Asegúrate de tener el servidor corriendo: php artisan serve

Write-Host "==================================" -ForegroundColor Cyan
Write-Host "🧪 PRUEBAS ARQUITECTURA DDD" -ForegroundColor Cyan
Write-Host "==================================" -ForegroundColor Cyan
Write-Host ""

# Base URL
$baseUrl = "http://localhost:8000/api/v1"

# Test 1: Health Check
Write-Host "1️⃣  Probando Health Check..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/whistleblowing/health" -Method Get
    Write-Host "✅ Health Check OK" -ForegroundColor Green
    Write-Host ($response | ConvertTo-Json) -ForegroundColor Gray
} catch {
    Write-Host "❌ Error en Health Check: $_" -ForegroundColor Red
}
Write-Host ""

# Test 2: Crear Reporte Anónimo
Write-Host "2️⃣  Creando Reporte Anónimo..." -ForegroundColor Yellow
$reportData = @{
    title = "Reporte de prueba DDD - Anónimo"
    description = "Esta es una descripción de prueba para validar la arquitectura DDD implementada en el proyecto"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/whistleblowing/reports" -Method Post -Body $reportData -ContentType "application/json"
    Write-Host "✅ Reporte creado exitosamente" -ForegroundColor Green
    Write-Host ($response | ConvertTo-Json) -ForegroundColor Gray
    $reportId = $response.data.id
} catch {
    Write-Host "❌ Error al crear reporte: $_" -ForegroundColor Red
}
Write-Host ""

# Test 3: Crear Reporte con Reporter ID
Write-Host "3️⃣  Creando Reporte con Reporter ID..." -ForegroundColor Yellow
$reportDataWithId = @{
    title = "Reporte de prueba DDD - Identificado"
    description = "Este reporte incluye un reporter_id para demostrar reportes no anónimos en la arquitectura DDD"
    reporter_id = "user_12345"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/whistleblowing/reports" -Method Post -Body $reportDataWithId -ContentType "application/json"
    Write-Host "✅ Reporte con ID creado exitosamente" -ForegroundColor Green
    Write-Host ($response | ConvertTo-Json) -ForegroundColor Gray
} catch {
    Write-Host "❌ Error al crear reporte: $_" -ForegroundColor Red
}
Write-Host ""

# Test 4: Listar Todos los Reportes
Write-Host "4️⃣  Listando Todos los Reportes..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/whistleblowing/reports" -Method Get
    Write-Host "✅ Reportes obtenidos exitosamente" -ForegroundColor Green
    Write-Host "📊 Total de reportes: $($response.data.Count)" -ForegroundColor Cyan
    Write-Host ($response | ConvertTo-Json -Depth 5) -ForegroundColor Gray
} catch {
    Write-Host "❌ Error al listar reportes: $_" -ForegroundColor Red
}
Write-Host ""

# Test 5: Obtener Reporte Específico
if ($reportId) {
    Write-Host "5️⃣  Obteniendo Reporte Específico (ID: $reportId)..." -ForegroundColor Yellow
    try {
        $response = Invoke-RestMethod -Uri "$baseUrl/whistleblowing/reports/$reportId" -Method Get
        Write-Host "✅ Reporte obtenido exitosamente" -ForegroundColor Green
        Write-Host ($response | ConvertTo-Json -Depth 5) -ForegroundColor Gray
    } catch {
        Write-Host "❌ Error al obtener reporte: $_" -ForegroundColor Red
    }
    Write-Host ""
}

# Test 6: Validación - Título muy corto
Write-Host "6️⃣  Probando Validación (título corto)..." -ForegroundColor Yellow
$invalidReport = @{
    title = "ABC"
    description = "Esta descripción es suficientemente larga para pasar la validación"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/whistleblowing/reports" -Method Post -Body $invalidReport -ContentType "application/json"
    Write-Host "⚠️  La validación no funcionó correctamente" -ForegroundColor Yellow
} catch {
    Write-Host "✅ Validación funcionando correctamente - Error esperado" -ForegroundColor Green
    Write-Host "Error: $($_.Exception.Response.StatusCode)" -ForegroundColor Gray
}
Write-Host ""

# Test 7: Validación - Descripción muy corta
Write-Host "7️⃣  Probando Validación (descripción corta)..." -ForegroundColor Yellow
$invalidReport2 = @{
    title = "Título válido de prueba"
    description = "Corta"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/whistleblowing/reports" -Method Post -Body $invalidReport2 -ContentType "application/json"
    Write-Host "⚠️  La validación no funcionó correctamente" -ForegroundColor Yellow
} catch {
    Write-Host "✅ Validación funcionando correctamente - Error esperado" -ForegroundColor Green
    Write-Host "Error: $($_.Exception.Response.StatusCode)" -ForegroundColor Gray
}
Write-Host ""

Write-Host "==================================" -ForegroundColor Cyan
Write-Host "✨ PRUEBAS COMPLETADAS" -ForegroundColor Cyan
Write-Host "==================================" -ForegroundColor Cyan
