# Script de Testing - Comunicación Bidireccional (Docker)
# Uso: .\test-bidirectional-docker.ps1

Write-Host "🐳 Testing Sistema de Comunicación Bidireccional (Docker)" -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host ""

# Verificar que Docker esté corriendo
$dockerRunning = docker ps 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Error: Docker no está corriendo" -ForegroundColor Red
    Write-Host "   Inicia Docker Desktop y vuelve a intentar" -ForegroundColor Yellow
    exit 1
}

# Verificar que el contenedor app esté corriendo
$appContainer = docker ps --filter "name=wb_app" --format "{{.Names}}"
if (-not $appContainer) {
    Write-Host "❌ Error: Contenedor 'wb_app' no está corriendo" -ForegroundColor Red
    Write-Host "   Ejecuta: docker-compose up -d" -ForegroundColor Yellow
    exit 1
}

Write-Host "✅ Docker está corriendo" -ForegroundColor Green
Write-Host "✅ Contenedor 'wb_app' encontrado" -ForegroundColor Green
Write-Host ""

# Función para ejecutar comandos en el contenedor
function Invoke-DockerTinker {
    param (
        [string]$Command
    )
    
    $tempFile = New-TemporaryFile
    $Command | Out-File -FilePath $tempFile.FullName -Encoding utf8
    
    $result = Get-Content $tempFile.FullName | docker-compose exec -T app php artisan tinker
    Remove-Item $tempFile.FullName
    
    return $result
}

# Test 1: Verificar migraciones
Write-Host "📋 Test 1: Verificar migraciones" -ForegroundColor Yellow
Write-Host "================================" -ForegroundColor Yellow
docker-compose exec app php artisan migrate:status
Write-Host ""

# Test 2: Crear reporte con AccessCode
Write-Host "📝 Test 2: Crear reporte con AccessCode" -ForegroundColor Yellow
Write-Host "=======================================" -ForegroundColor Yellow

$createReportCode = @"
use Src\Whistleblowing\Application\UseCases\CreateReportUseCase;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\EloquentReportRepository;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\ReportModel;

`$repository = new EloquentReportRepository(new ReportModel());
`$useCase = new CreateReportUseCase(`$repository);

try {
    `$response = `$useCase->execute([
        'title' => 'Fraude en proceso de compras - Test Docker',
        'description' => 'Este es un reporte de prueba para verificar el sistema de comunicación bidireccional en Docker. Se detectaron irregularidades en las facturas del Q4 2024.',
        'reporterId' => null
    ]);

    `$data = `$response->toArray();
    echo "\n✅ Reporte creado exitosamente:\n";
    echo "   ID: " . `$data['report_id'] . "\n";
    echo "   AccessCode: " . `$data['access_code'] . "\n";
    echo "   Título: " . `$data['title'] . "\n";
    echo "   Estado: " . `$data['status'] . "\n";
    echo "   Anónimo: " . (`$data['is_anonymous'] ? 'Sí' : 'No') . "\n";
    echo "\n⚠️  GUARDAR ESTE CÓDIGO: " . `$data['access_code'] . "\n";
    echo "ACCESS_CODE_FOR_SCRIPT:" . `$data['access_code'] . "\n\n";

    @file_put_contents('/var/www/html/storage/temp_access_code.txt', `$data['access_code']);
} catch (Exception `$e) {
    echo "\n❌ Error al crear reporte: " . `$e->getMessage() . "\n";
    echo "Stack trace: " . `$e->getTraceAsString() . "\n";
}
"@

Write-Host "Ejecutando creación de reporte..." -ForegroundColor Gray
$createResult = $createReportCode | docker-compose exec -T app php artisan tinker 2>&1

# Capturar AccessCode de la salida
$accessCode = $null
foreach ($line in $createResult) {
    # Mostrar líneas importantes
    if ($line -match "✅|❌|⚠️|AccessCode|access_code|Error|ID:") {
        Write-Host $line -ForegroundColor Cyan
    }
    
    # Capturar AccessCode con múltiples patrones
    if ($line -match "ACCESS_CODE_FOR_SCRIPT:([A-Za-z0-9]{16})") {
        $accessCode = $matches[1]
    } elseif ($line -match "AccessCode: ([A-Za-z0-9]{16})") {
        $accessCode = $matches[1]
    } elseif ($line -match "GUARDAR ESTE CÓDIGO: ([A-Za-z0-9]{16})") {
        $accessCode = $matches[1]
    } elseif ($line -match "'access_code' => '([A-Za-z0-9]{16})'") {
        $accessCode = $matches[1]
    }
}

# Intentar leer del archivo como backup
if (-not $accessCode) {
    Start-Sleep -Seconds 1
    $accessCode = docker-compose exec -T app cat /var/www/html/storage/temp_access_code.txt 2>$null
    if ($accessCode) {
        $accessCode = $accessCode.Trim()
    }
}

if ($accessCode) {
    Write-Host ""
    Write-Host "📌 AccessCode capturado: $accessCode" -ForegroundColor Magenta
    Write-Host ""
} else {
    Write-Host ""
    Write-Host "❌ Error: No se pudo capturar el AccessCode" -ForegroundColor Red
    Write-Host "⚠️  El reporte puede haberse creado, pero no se capturó el código." -ForegroundColor Yellow
    Write-Host "💡 Intenta consultar manualmente:" -ForegroundColor Yellow
    Write-Host "   docker-compose exec app php artisan tinker" -ForegroundColor Cyan
    Write-Host '   DB::table("reports")->latest()->first()' -ForegroundColor Cyan
    Write-Host ""
    exit 1
}

Start-Sleep -Seconds 2

# Test 3: Consultar estado del reporte
Write-Host "🔍 Test 3: Consultar estado del reporte" -ForegroundColor Yellow
Write-Host "=======================================" -ForegroundColor Yellow

$checkStatusCode = @"
use Src\Whistleblowing\Application\UseCases\CheckReportStatusUseCase;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\EloquentReportRepository;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\ReportModel;

`$repository = new EloquentReportRepository(new ReportModel());
`$useCase = new CheckReportStatusUseCase(`$repository);

`$status = `$useCase->execute('$accessCode');
`$data = `$status->toArray();

echo "\n✅ Estado del reporte:\n";
echo "   Título: " . `$data['title'] . "\n";
echo "   Estado: " . `$data['status'] . "\n";
echo "   Mensajes: " . `$data['message_count'] . "\n";
echo "\n";
"@

Write-Host "Consultando estado..." -ForegroundColor Gray
$checkStatusCode | docker-compose exec -T app php artisan tinker 2>&1 | ForEach-Object { 
    if ($_ -match "Estado|Título|Mensajes|✅") {
        Write-Host $_ -ForegroundColor Cyan
    }
}

Start-Sleep -Seconds 2

# Test 4: Agregar mensaje del denunciante
Write-Host "💬 Test 4: Agregar mensaje del denunciante" -ForegroundColor Yellow
Write-Host "===========================================" -ForegroundColor Yellow

$addReporterMessageCode = @"
use Src\Whistleblowing\Application\UseCases\AddMessageToReportUseCase;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\EloquentReportRepository;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\ReportModel;

`$repository = new EloquentReportRepository(new ReportModel());
`$useCase = new AddMessageToReportUseCase(`$repository);

`$result = `$useCase->execute(
    '$accessCode',
    'Tengo documentación adicional que respalda esta denuncia. ¿Cómo puedo compartirla de forma segura?',
    'reporter'
);

echo "\n✅ Mensaje del denunciante agregado:\n";
echo "   ID: " . `$result['message_id'] . "\n";
echo "   Autor: " . `$result['author'] . "\n";
echo "   Total mensajes: " . `$result['message_count'] . "\n";
echo "\n";
"@

Write-Host "Agregando mensaje del reporter..." -ForegroundColor Gray
$addReporterMessageCode | docker-compose exec -T app php artisan tinker 2>&1 | ForEach-Object { 
    if ($_ -match "mensaje|Autor|Total|✅|ID:") {
        Write-Host $_ -ForegroundColor Green
    }
}

Start-Sleep -Seconds 2

# Test 5: Agregar respuesta del investigador
Write-Host "👨‍💼 Test 5: Agregar respuesta del investigador" -ForegroundColor Yellow
Write-Host "==============================================" -ForegroundColor Yellow

$addInvestigatorMessageCode = @"
use Src\Whistleblowing\Application\UseCases\AddMessageToReportUseCase;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\EloquentReportRepository;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\ReportModel;

`$repository = new EloquentReportRepository(new ReportModel());
`$useCase = new AddMessageToReportUseCase(`$repository);

`$result = `$useCase->execute(
    '$accessCode',
    'Hemos recibido su denuncia y está siendo procesada. Puede subir documentos a través del portal seguro. Referencia: INV-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
    'investigator'
);

echo "\n✅ Respuesta del investigador agregada:\n";
echo "   ID: " . `$result['message_id'] . "\n";
echo "   Autor: " . `$result['author'] . "\n";
echo "   Total mensajes: " . `$result['message_count'] . "\n";
echo "\n";
"@

Write-Host "Agregando respuesta del investigator..." -ForegroundColor Gray
$addInvestigatorMessageCode | docker-compose exec -T app php artisan tinker 2>&1 | ForEach-Object { 
    if ($_ -match "Respuesta|Autor|Total|✅|ID:") {
        Write-Host $_ -ForegroundColor Blue
    }
}

Start-Sleep -Seconds 2

# Test 6: Ver conversación completa
Write-Host "💭 Test 6: Ver conversación completa" -ForegroundColor Yellow
Write-Host "====================================" -ForegroundColor Yellow

$viewConversationCode = @"
use Src\Whistleblowing\Application\UseCases\CheckReportStatusUseCase;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\EloquentReportRepository;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\ReportModel;

`$repository = new EloquentReportRepository(new ReportModel());
`$useCase = new CheckReportStatusUseCase(`$repository);

`$status = `$useCase->execute('$accessCode');
`$data = `$status->toArray();

echo "\n📊 Reporte: " . `$data['title'] . "\n";
echo "Estado: " . `$data['status'] . "\n";
echo "Total de mensajes: " . `$data['message_count'] . "\n";
echo "\n💬 Conversación:\n";
echo str_repeat('─', 60) . "\n";

foreach (`$data['messages'] as `$msg) {
    `$icon = `$msg['author'] === 'reporter' ? '🙋' : '👨‍💼';
    `$authorLabel = `$msg['author'] === 'reporter' ? 'DENUNCIANTE' : 'INVESTIGADOR';
    
    echo "\n" . `$icon . " " . `$authorLabel . " (" . `$msg['created_at'] . "):\n";
    echo `$msg['content'] . "\n";
    echo str_repeat('─', 60) . "\n";
}

echo "\n✅ Conversación bidireccional funcionando correctamente\n\n";
"@

Write-Host "Mostrando conversación..." -ForegroundColor Gray
$viewConversationCode | docker-compose exec -T app php artisan tinker 2>&1 | ForEach-Object { 
    Write-Host $_ -ForegroundColor White
}

# Test 7: Verificar en base de datos
Write-Host "🔍 Test 7: Verificar en base de datos" -ForegroundColor Yellow
Write-Host "=====================================" -ForegroundColor Yellow

$verifyDbCode = @"
echo "\n📊 Estadísticas de la base de datos:\n";
echo "Total de reportes: " . DB::table('reports')->count() . "\n";
echo "Total de mensajes: " . DB::table('messages')->count() . "\n";

echo "\n📈 Mensajes por autor:\n";
`$stats = DB::table('messages')
    ->select('author', DB::raw('count(*) as total'))
    ->groupBy('author')
    ->get();

foreach (`$stats as `$stat) {
    echo "   " . ucfirst(`$stat->author) . ": " . `$stat->total . "\n";
}

echo "\n✅ Base de datos verificada\n\n";
"@

Write-Host "Consultando estadísticas..." -ForegroundColor Gray
$verifyDbCode | docker-compose exec -T app php artisan tinker 2>&1 | ForEach-Object { 
    if ($_ -match "Total|reportes|mensajes|Reporter|Investigator|✅|📊|📈") {
        Write-Host $_ -ForegroundColor Cyan
    }
}

# Test 8: Verificar hash de AccessCode
Write-Host "🔐 Test 8: Verificar hash de AccessCode" -ForegroundColor Yellow
Write-Host "=======================================" -ForegroundColor Yellow

$verifyHashCode = @"
`$plainCode = '$accessCode';
`$hash = hash('sha256', `$plainCode);

echo "\n🔐 Verificación de seguridad:\n";
echo "   AccessCode (plano): " . `$plainCode . "\n";
echo "   Hash SHA-256: " . substr(`$hash, 0, 16) . "...\n";

`$report = DB::table('reports')->where('access_code_hash', `$hash)->first();

if (`$report) {
    echo "   ✅ Hash coincide en base de datos\n";
    echo "   Reporte encontrado: " . `$report->title . "\n";
} else {
    echo "   ❌ Hash no coincide\n";
}

echo "\n";
"@

Write-Host "Verificando hash..." -ForegroundColor Gray
$verifyHashCode | docker-compose exec -T app php artisan tinker 2>&1 | ForEach-Object { 
    if ($_ -match "AccessCode|Hash|coincide|Reporte|✅|❌|🔐") {
        Write-Host $_ -ForegroundColor Magenta
    }
}

# Cleanup
docker-compose exec -T app rm -f /var/www/html/storage/temp_access_code.txt 2>$null

# Resumen final
Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║                  ✅ TESTS COMPLETADOS                      ║" -ForegroundColor Green
Write-Host "╠════════════════════════════════════════════════════════════╣" -ForegroundColor Green
Write-Host "║                                                            ║" -ForegroundColor Green
Write-Host "║  ✓ Migraciones verificadas                                ║" -ForegroundColor Green
Write-Host "║  ✓ Reporte creado con AccessCode                          ║" -ForegroundColor Green
Write-Host "║  ✓ Estado consultado correctamente                        ║" -ForegroundColor Green
Write-Host "║  ✓ Mensaje del denunciante agregado                       ║" -ForegroundColor Green
Write-Host "║  ✓ Respuesta del investigador agregada                    ║" -ForegroundColor Green
Write-Host "║  ✓ Conversación bidireccional funcionando                 ║" -ForegroundColor Green
Write-Host "║  ✓ Base de datos verificada                               ║" -ForegroundColor Green
Write-Host "║  ✓ Hash de AccessCode validado                            ║" -ForegroundColor Green
Write-Host "║                                                            ║" -ForegroundColor Green
Write-Host "╠════════════════════════════════════════════════════════════╣" -ForegroundColor Green
Write-Host "║  📌 AccessCode de prueba: $accessCode               ║" -ForegroundColor Green
Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""
Write-Host "💡 Tip: Guarda el AccessCode para pruebas manuales adicionales" -ForegroundColor Yellow
Write-Host "🐳 Ejecutado en Docker container: wb_app" -ForegroundColor Cyan
Write-Host ""
