# ================================================
# WhistleBlower Vault - Fix Permissions Script
# Soluciona problemas de permisos en Windows
# ================================================

Write-Host "🔧 Reparando Permisos de Laravel en Windows" -ForegroundColor Cyan
Write-Host "===========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "📁 Aplicando permisos desde Windows..." -ForegroundColor Yellow
try {
    icacls storage /grant Todos:F /T 2>&1 | Out-Null
    icacls bootstrap\cache /grant Todos:F /T 2>&1 | Out-Null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Permisos Windows aplicados" -ForegroundColor Green
    }
} catch {
    Write-Host "❌ Error aplicando permisos desde Windows" -ForegroundColor Red
}

Write-Host ""
Write-Host "📁 Aplicando permisos desde Docker..." -ForegroundColor Yellow
try {
    docker-compose exec -T app chmod -R 777 storage bootstrap/cache 2>&1 | Out-Null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Permisos Docker aplicados (777)" -ForegroundColor Green
    }
} catch {
    Write-Host "❌ Error aplicando permisos desde Docker" -ForegroundColor Red
}

Write-Host ""
Write-Host "🧹 Limpiando cache de Laravel..." -ForegroundColor Yellow
docker-compose exec -T app php artisan cache:clear 2>&1 | Out-Null
docker-compose exec -T app php artisan config:clear 2>&1 | Out-Null
docker-compose exec -T app php artisan view:clear 2>&1 | Out-Null
docker-compose exec -T app php artisan route:clear 2>&1 | Out-Null
Write-Host "✅ Cache limpiado" -ForegroundColor Green

Write-Host ""
Write-Host "🔄 Reiniciando contenedor PHP-FPM..." -ForegroundColor Yellow
docker-compose restart app 2>&1 | Out-Null
Start-Sleep -Seconds 3
Write-Host "✅ Contenedor reiniciado" -ForegroundColor Green

Write-Host ""
Write-Host "✅ ¡Permisos reparados exitosamente!" -ForegroundColor Green
Write-Host ""
Write-Host "🌐 Prueba acceder nuevamente a: http://localhost:8080" -ForegroundColor Cyan
Write-Host ""
