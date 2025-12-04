# ================================================
# WhistleBlower Vault - Setup Script
# Script de inicialización del entorno Docker
# ================================================

Write-Host "🚀 WhistleBlower Vault - Setup Inicial" -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""

# Verificar si Docker está corriendo
Write-Host "📋 Verificando Docker Desktop..." -ForegroundColor Yellow
try {
    docker info | Out-Null
    Write-Host "✅ Docker está corriendo" -ForegroundColor Green
} catch {
    Write-Host "❌ Docker Desktop no está corriendo o no responde" -ForegroundColor Red
    Write-Host "Por favor, inicia Docker Desktop y ejecuta este script nuevamente" -ForegroundColor Yellow
    exit 1
}

Write-Host ""
Write-Host "🔐 Configurando permisos para Docker volumes..." -ForegroundColor Yellow
icacls storage /grant Todos:F /T 2>&1 | Out-Null
icacls bootstrap\cache /grant Todos:F /T 2>&1 | Out-Null
Write-Host "✅ Permisos Windows configurados" -ForegroundColor Green

Write-Host ""
Write-Host "🛑 Deteniendo contenedores existentes..." -ForegroundColor Yellow
docker-compose down

Write-Host ""
Write-Host "🏗️  Construyendo imágenes Docker..." -ForegroundColor Yellow
docker-compose build --no-cache

Write-Host ""
Write-Host "🚀 Levantando contenedores..." -ForegroundColor Yellow
docker-compose up -d

Write-Host ""
Write-Host "⏳ Esperando a que los servicios estén listos..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

Write-Host ""
Write-Host "🔐 Aplicando permisos desde Docker..." -ForegroundColor Yellow
docker-compose exec -T app chmod -R 777 storage bootstrap/cache 2>&1 | Out-Null
Write-Host "✅ Permisos Docker configurados" -ForegroundColor Green

Write-Host ""
Write-Host "📦 Instalando dependencias de Composer..." -ForegroundColor Yellow
docker-compose exec -T app composer install --no-interaction --optimize-autoloader 2>&1 | Out-Null
if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Dependencias instaladas correctamente" -ForegroundColor Green
} else {
    Write-Host "⚠️  Las dependencias ya estaban instaladas" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "🔑 Generando clave de aplicación..." -ForegroundColor Yellow
$keyGenResult = docker-compose exec -T app php artisan key:generate 2>&1
if ($keyGenResult -match "set successfully") {
    Write-Host "✅ Clave generada correctamente" -ForegroundColor Green
} else {
    Write-Host "⚠️  $keyGenResult" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "🗄️  Ejecutando migraciones de base de datos..." -ForegroundColor Yellow
$migrateResult = docker-compose exec -T app php artisan migrate --force 2>&1
if ($migrateResult -match "Nothing to migrate" -or $migrateResult -match "Migrated") {
    Write-Host "✅ Migraciones ejecutadas correctamente" -ForegroundColor Green
} else {
    Write-Host "⚠️  $migrateResult" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "🎨 Optimizando configuración..." -ForegroundColor Yellow
docker-compose exec -T app php artisan config:cache 2>&1 | Out-Null
docker-compose exec -T app php artisan route:cache 2>&1 | Out-Null
Write-Host "✅ Configuración optimizada" -ForegroundColor Green

Write-Host ""
Write-Host "✅ ¡Setup completado exitosamente!" -ForegroundColor Green
Write-Host ""
Write-Host "🌐 Accede a la aplicación en: http://localhost:8080" -ForegroundColor Cyan
Write-Host ""
Write-Host "📊 Comandos útiles:" -ForegroundColor Yellow
Write-Host "  - Ver logs:        docker-compose logs -f" -ForegroundColor Gray
Write-Host "  - Detener:         docker-compose stop" -ForegroundColor Gray
Write-Host "  - Reiniciar:       docker-compose restart" -ForegroundColor Gray
Write-Host "  - Ejecutar bash:   docker-compose exec app sh" -ForegroundColor Gray
Write-Host ""
