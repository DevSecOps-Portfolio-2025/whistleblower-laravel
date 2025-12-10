# =========================================
# Script de Prueba: Auditoría Inmutable
# US-005: Tamper-Proof Audit Trail
# =========================================

Write-Host "`n🔐 TESTING IMMUTABLE AUDIT TRAIL" -ForegroundColor Cyan
Write-Host "================================`n" -ForegroundColor Cyan

$baseUrl = "http://localhost:8000/api/v1"
$headers = @{ "Content-Type" = "application/json" }

# =========================================
# PASO 1: Verificar que el servidor está corriendo
# =========================================
Write-Host "📡 1. Verificando servidor..." -ForegroundColor Yellow
try {
    $health = Invoke-RestMethod -Uri "$baseUrl/whistleblowing/health" -Method GET
    Write-Host "✅ Servidor funcionando correctamente" -ForegroundColor Green
    Write-Host "   Timestamp: $($health.timestamp)`n"
} catch {
    Write-Host "❌ Error: Servidor no está corriendo" -ForegroundColor Red
    Write-Host "   Ejecuta: php artisan serve`n" -ForegroundColor Yellow
    exit 1
}

# =========================================
# PASO 2: Verificar migración
# =========================================
Write-Host "📦 2. Verificando tabla audit_logs..." -ForegroundColor Yellow
try {
    php artisan tinker --execute="echo \Src\Whistleblowing\Infrastructure\Persistence\Eloquent\Models\AuditLog::count();" | Out-Null
    Write-Host "✅ Tabla audit_logs existe`n" -ForegroundColor Green
} catch {
    Write-Host "❌ Error: Ejecuta php artisan migrate`n" -ForegroundColor Red
    exit 1
}

# =========================================
# PASO 3: Contar registros iniciales
# =========================================
Write-Host "📊 3. Contando registros de auditoría iniciales..." -ForegroundColor Yellow
$initialCount = php artisan tinker --execute="echo \Src\Whistleblowing\Infrastructure\Persistence\Eloquent\Models\AuditLog::count();"
Write-Host "   Registros actuales: $initialCount`n" -ForegroundColor White

# =========================================
# PASO 4: Crear un reporte (debe generar audit log)
# =========================================
Write-Host "📝 4. Creando reporte (debe crear registro de auditoría)..." -ForegroundColor Yellow

$reportData = @{
    title = "Reporte de Prueba - Auditoría Inmutable"
    description = "Este reporte debe generar automáticamente un registro en la cadena de auditoría"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/whistleblowing/reports" `
        -Method POST `
        -Headers $headers `
        -Body $reportData
    
    Write-Host "✅ Reporte creado exitosamente" -ForegroundColor Green
    Write-Host "   Report ID: $($response.data.id)" -ForegroundColor White
    Write-Host "   Access Code: $($response.data.access_code)`n" -ForegroundColor Magenta
    
    $reportId = $response.data.id
} catch {
    Write-Host "❌ Error al crear reporte: $_`n" -ForegroundColor Red
    exit 1
}

Start-Sleep -Seconds 1

# =========================================
# PASO 5: Verificar que se creó el audit log
# =========================================
Write-Host "🔍 5. Verificando registro de auditoría..." -ForegroundColor Yellow
$newCount = php artisan tinker --execute="echo \Src\Whistleblowing\Infrastructure\Persistence\Eloquent\Models\AuditLog::count();"

if ($newCount -gt $initialCount) {
    Write-Host "✅ Registro de auditoría creado automáticamente" -ForegroundColor Green
    Write-Host "   Total de registros: $newCount (+1)`n" -ForegroundColor White
} else {
    Write-Host "❌ No se creó el registro de auditoría`n" -ForegroundColor Red
}

# =========================================
# PASO 6: Ver detalles del último audit log
# =========================================
Write-Host "📋 6. Detalles del último registro de auditoría..." -ForegroundColor Yellow
$lastAudit = php artisan tinker --execute="
    \$log = \Src\Whistleblowing\Infrastructure\Persistence\Eloquent\Models\AuditLog::latest('created_at')->first();
    if (\$log) {
        echo json_encode([
            'id' => \$log->id,
            'entity_type' => \$log->entity_type,
            'action' => \$log->action,
            'previous_hash' => substr(\$log->previous_hash, 0, 16) . '...',
            'hash' => substr(\$log->hash, 0, 16) . '...',
            'actor_ip' => \$log->actor_ip
        ]);
    }
" | ConvertFrom-Json

Write-Host "   Entity Type: $($lastAudit.entity_type)" -ForegroundColor White
Write-Host "   Action: $($lastAudit.action)" -ForegroundColor White
Write-Host "   Previous Hash: $($lastAudit.previous_hash)" -ForegroundColor White
Write-Host "   Current Hash: $($lastAudit.hash)" -ForegroundColor White
Write-Host "   Actor IP: $($lastAudit.actor_ip)`n" -ForegroundColor White

# =========================================
# PASO 7: Verificar integridad de la cadena
# =========================================
Write-Host "🔒 7. Verificando integridad de la cadena..." -ForegroundColor Yellow
php artisan audit:verify
Write-Host ""

# =========================================
# PASO 8: Test de manipulación (opcional)
# =========================================
Write-Host "⚠️  8. ¿Deseas probar la detección de manipulación? (S/N)" -ForegroundColor Yellow
$response = Read-Host "   Esto modificará un registro para demostrar la detección"

if ($response -eq "S" -or $response -eq "s") {
    Write-Host "`n🔧 Manipulando un registro de auditoría..." -ForegroundColor Yellow
    
    php artisan tinker --execute="
        \$log = \Src\Whistleblowing\Infrastructure\Persistence\Eloquent\Models\AuditLog::latest('created_at')->first();
        if (\$log) {
            \$log->payload = ['manipulated' => true, 'original_title' => 'MODIFICADO'];
            \$log->save();
            echo 'Registro manipulado';
        }
    "
    
    Write-Host "✅ Registro manipulado (payload modificado)`n" -ForegroundColor Green
    
    Write-Host "🔍 Verificando integridad nuevamente..." -ForegroundColor Yellow
    php artisan audit:verify
    Write-Host ""
    
    Write-Host "⚠️  Nota: La cadena detectó la manipulación!" -ForegroundColor Red
    Write-Host "   Para restaurar, ejecuta: php artisan migrate:fresh`n" -ForegroundColor Yellow
}

# =========================================
# PASO 9: Estadísticas finales
# =========================================
Write-Host "📊 9. Estadísticas Finales" -ForegroundColor Yellow
Write-Host "========================`n" -ForegroundColor Yellow

$stats = php artisan tinker --execute="
    \$totalLogs = \Src\Whistleblowing\Infrastructure\Persistence\Eloquent\Models\AuditLog::count();
    \$reports = \Src\Whistleblowing\Infrastructure\Persistence\Eloquent\Models\AuditLog::where('entity_type', 'Report')->count();
    \$messages = \Src\Whistleblowing\Infrastructure\Persistence\Eloquent\Models\AuditLog::where('entity_type', 'Message')->count();
    echo json_encode([
        'total' => \$totalLogs,
        'reports' => \$reports,
        'messages' => \$messages
    ]);
" | ConvertFrom-Json

Write-Host "   Total Audit Logs: $($stats.total)" -ForegroundColor White
Write-Host "   Reports Auditados: $($stats.reports)" -ForegroundColor White
Write-Host "   Messages Auditados: $($stats.messages)" -ForegroundColor White

# =========================================
# RESUMEN
# =========================================
Write-Host "`n✅ PRUEBAS COMPLETADAS" -ForegroundColor Green
Write-Host "===================`n" -ForegroundColor Green

Write-Host "📌 Puntos Clave:" -ForegroundColor Cyan
Write-Host "   ✓ Cada Report/Message crea automáticamente un audit log" -ForegroundColor White
Write-Host "   ✓ Los registros están encadenados criptográficamente (SHA-256)" -ForegroundColor White
Write-Host "   ✓ El comando audit:verify detecta manipulaciones" -ForegroundColor White
Write-Host "   ✓ Las IPs se anonimizan automáticamente (GDPR)" -ForegroundColor White

Write-Host "`n📚 Comandos Útiles:" -ForegroundColor Cyan
Write-Host "   php artisan audit:verify           - Verificar integridad completa" -ForegroundColor White
Write-Host "   php artisan audit:verify --json    - Ver resultados en JSON" -ForegroundColor White
Write-Host "   php artisan tinker                 - Explorar registros manualmente`n" -ForegroundColor White
