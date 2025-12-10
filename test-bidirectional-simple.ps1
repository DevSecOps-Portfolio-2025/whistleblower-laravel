# Script Simplificado de Testing - Comunicación Bidireccional (Docker)
# Uso: .\test-bidirectional-simple.ps1

Write-Host "🐳 Testing Sistema de Comunicación Bidireccional (Docker - Simplificado)" -ForegroundColor Cyan
Write-Host "=========================================================================" -ForegroundColor Cyan
Write-Host ""

# Verificar Docker
$dockerRunning = docker ps 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Error: Docker no está corriendo" -ForegroundColor Red
    exit 1
}

$appContainer = docker ps --filter "name=wb_app" --format "{{.Names}}"
if (-not $appContainer) {
    Write-Host "❌ Error: Contenedor 'wb_app' no está corriendo" -ForegroundColor Red
    exit 1
}

Write-Host "✅ Docker OK" -ForegroundColor Green
Write-Host ""

# Test 1: Migraciones
Write-Host "📋 Test 1: Verificar migraciones" -ForegroundColor Yellow
docker-compose exec app php artisan migrate:status | Select-String "Ran"
Write-Host ""

# Test 2: Crear reporte y guardar AccessCode en variable
Write-Host "📝 Test 2: Crear reporte" -ForegroundColor Yellow
Write-Host "========================" -ForegroundColor Yellow

# Crear archivo PHP temporal en el contenedor
$phpCode = @'
<?php
require '/var/www/html/vendor/autoload.php';

$app = require_once '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Src\Whistleblowing\Application\UseCases\CreateReportUseCase;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\EloquentReportRepository;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\ReportModel;

$repository = new EloquentReportRepository(new ReportModel());
$useCase = new CreateReportUseCase($repository);

try {
    $response = $useCase->execute([
        'title' => 'Fraude en proceso de compras - Test Docker Simple',
        'description' => 'Test de comunicación bidireccional en Docker con script simplificado.',
        'reporterId' => null
    ]);

    $data = $response->toArray();
    echo "SUCCESS\n";
    echo $data['access_code'] . "\n";
    echo $data['report_id'] . "\n";
} catch (Exception $e) {
    echo "ERROR\n";
    echo $e->getMessage() . "\n";
}
'@

# Guardar en el contenedor
$phpCode | docker-compose exec -T app tee /tmp/create_report.php > $null

# Ejecutar
$result = docker-compose exec -T app php /tmp/create_report.php

if ($result[0] -eq "SUCCESS") {
    $accessCode = $result[1].Trim()
    $reportId = $result[2].Trim()
    
    Write-Host "✅ Reporte creado exitosamente" -ForegroundColor Green
    Write-Host "   ID: $reportId" -ForegroundColor Cyan
    Write-Host "   AccessCode: $accessCode" -ForegroundColor Magenta
    Write-Host ""
} else {
    Write-Host "❌ Error al crear reporte" -ForegroundColor Red
    Write-Host $result[1] -ForegroundColor Red
    exit 1
}

Start-Sleep -Seconds 2

# Test 3: Consultar estado
Write-Host "🔍 Test 3: Consultar estado" -ForegroundColor Yellow
Write-Host "===========================" -ForegroundColor Yellow

$checkCode = @"
<?php
require '/var/www/html/vendor/autoload.php';
`$app = require_once '/var/www/html/bootstrap/app.php';
`$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Src\Whistleblowing\Application\UseCases\CheckReportStatusUseCase;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\EloquentReportRepository;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\ReportModel;

`$repository = new EloquentReportRepository(new ReportModel());
`$useCase = new CheckReportStatusUseCase(`$repository);

try {
    `$status = `$useCase->execute('$accessCode');
    `$data = `$status->toArray();
    echo "SUCCESS\n";
    echo `$data['title'] . "\n";
    echo `$data['status'] . "\n";
    echo `$data['message_count'] . "\n";
} catch (Exception `$e) {
    echo "ERROR\n";
    echo `$e->getMessage() . "\n";
}
"@

$checkCode | docker-compose exec -T app tee /tmp/check_status.php > $null
$checkResult = docker-compose exec -T app php /tmp/check_status.php

if ($checkResult[0] -eq "SUCCESS") {
    Write-Host "✅ Estado consultado" -ForegroundColor Green
    Write-Host "   Título: $($checkResult[1])" -ForegroundColor Cyan
    Write-Host "   Estado: $($checkResult[2])" -ForegroundColor Cyan
    Write-Host "   Mensajes: $($checkResult[3])" -ForegroundColor Cyan
    Write-Host ""
} else {
    Write-Host "❌ Error: $($checkResult[1])" -ForegroundColor Red
}

Start-Sleep -Seconds 2

# Test 4: Agregar mensaje del reporter
Write-Host "💬 Test 4: Mensaje del denunciante" -ForegroundColor Yellow
Write-Host "===================================" -ForegroundColor Yellow

$addMsgCode = @"
<?php
require '/var/www/html/vendor/autoload.php';
`$app = require_once '/var/www/html/bootstrap/app.php';
`$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Src\Whistleblowing\Application\UseCases\AddMessageToReportUseCase;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\EloquentReportRepository;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\ReportModel;

`$repository = new EloquentReportRepository(new ReportModel());
`$useCase = new AddMessageToReportUseCase(`$repository);

try {
    `$result = `$useCase->execute(
        '$accessCode',
        'Tengo documentación adicional que respalda mi denuncia.',
        'reporter'
    );
    echo "SUCCESS\n";
    echo `$result['author'] . "\n";
    echo `$result['message_count'] . "\n";
} catch (Exception `$e) {
    echo "ERROR\n";
    echo `$e->getMessage() . "\n";
}
"@

$addMsgCode | docker-compose exec -T app tee /tmp/add_message.php > $null
$msgResult = docker-compose exec -T app php /tmp/add_message.php

if ($msgResult[0] -eq "SUCCESS") {
    Write-Host "✅ Mensaje agregado" -ForegroundColor Green
    Write-Host "   Autor: $($msgResult[1])" -ForegroundColor Cyan
    Write-Host "   Total mensajes: $($msgResult[2])" -ForegroundColor Cyan
    Write-Host ""
}

Start-Sleep -Seconds 2

# Test 5: Respuesta del investigator
Write-Host "👨‍💼 Test 5: Respuesta del investigador" -ForegroundColor Yellow
Write-Host "=======================================" -ForegroundColor Yellow

$addInvCode = @"
<?php
require '/var/www/html/vendor/autoload.php';
`$app = require_once '/var/www/html/bootstrap/app.php';
`$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Src\Whistleblowing\Application\UseCases\AddMessageToReportUseCase;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\EloquentReportRepository;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\ReportModel;

`$repository = new EloquentReportRepository(new ReportModel());
`$useCase = new AddMessageToReportUseCase(`$repository);

try {
    `$result = `$useCase->execute(
        '$accessCode',
        'Hemos iniciado la investigación. Referencia: INV-2025-001',
        'investigator'
    );
    echo "SUCCESS\n";
    echo `$result['author'] . "\n";
    echo `$result['message_count'] . "\n";
} catch (Exception `$e) {
    echo "ERROR\n";
    echo `$e->getMessage() . "\n";
}
"@

$addInvCode | docker-compose exec -T app tee /tmp/add_investigator.php > $null
$invResult = docker-compose exec -T app php /tmp/add_investigator.php

if ($invResult[0] -eq "SUCCESS") {
    Write-Host "✅ Respuesta agregada" -ForegroundColor Green
    Write-Host "   Autor: $($invResult[1])" -ForegroundColor Cyan
    Write-Host "   Total mensajes: $($invResult[2])" -ForegroundColor Cyan
    Write-Host ""
}

# Test 6: Ver conversación
Write-Host "💭 Test 6: Ver conversación completa" -ForegroundColor Yellow
Write-Host "====================================" -ForegroundColor Yellow

docker-compose exec -T app php artisan tinker --execute="
use Src\Whistleblowing\Application\UseCases\CheckReportStatusUseCase;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\EloquentReportRepository;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\ReportModel;
`$repository = new EloquentReportRepository(new ReportModel());
`$useCase = new CheckReportStatusUseCase(`$repository);
`$status = `$useCase->execute('$accessCode');
foreach (`$status->messages as `$msg) {
    echo '[' . strtoupper(`$msg->author) . '] ' . `$msg->content . PHP_EOL;
}
" 2>&1 | Select-String -Pattern "^\[" | ForEach-Object { Write-Host $_ -ForegroundColor White }

# Test 7: Estadísticas
Write-Host ""
Write-Host "📊 Test 7: Estadísticas" -ForegroundColor Yellow
Write-Host "=======================" -ForegroundColor Yellow

docker-compose exec db mysql -u wb_user -psecret whistleblower -e "SELECT COUNT(*) as 'Total Reportes' FROM reports;"
docker-compose exec db mysql -u wb_user -psecret whistleblower -e "SELECT COUNT(*) as 'Total Mensajes' FROM messages;"
docker-compose exec db mysql -u wb_user -psecret whistleblower -e "SELECT author as 'Autor', COUNT(*) as 'Total' FROM messages GROUP BY author;"

# Resumen
Write-Host ""
Write-Host "╔════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║           ✅ TESTS COMPLETADOS                 ║" -ForegroundColor Green
Write-Host "╠════════════════════════════════════════════════╣" -ForegroundColor Green
Write-Host "║  📌 AccessCode: $accessCode           ║" -ForegroundColor Green
Write-Host "╚════════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""

# Cleanup
docker-compose exec -T app rm -f /tmp/*.php 2>$null
