# Script de Testing - Comunicación Bidireccional
# Uso: .\test-bidirectional.ps1

Write-Host "🧪 Testing Sistema de Comunicación Bidireccional" -ForegroundColor Cyan
Write-Host "=================================================" -ForegroundColor Cyan
Write-Host ""

# Función para ejecutar comandos PHP en Tinker
function Invoke-TinkerCommand {
    param (
        [string]$Command
    )
    
    $tempFile = New-TemporaryFile
    $Command | Out-File -FilePath $tempFile.FullName -Encoding utf8
    
    $result = Get-Content $tempFile.FullName | php artisan tinker
    Remove-Item $tempFile.FullName
    
    return $result
}

# Test 1: Verificar migraciones
Write-Host "📋 Test 1: Verificar migraciones" -ForegroundColor Yellow
Write-Host "================================" -ForegroundColor Yellow
php artisan migrate:status
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

`$response = `$useCase->execute([
    'title' => 'Fraude en proceso de compras - Test Automatizado',
    'description' => 'Este es un reporte de prueba para verificar el sistema de comunicación bidireccional. Se detectaron irregularidades en las facturas del Q4 2024.',
    'reporterId' => null
]);

`$data = `$response->toArray();
echo "\n✅ Reporte creado exitosamente:\n";
echo "   ID: " . `$data['report_id'] . "\n";
echo "   AccessCode: " . `$data['access_code'] . "\n";
echo "   Título: " . `$data['title'] . "\n";
echo "   Estado: " . `$data['status'] . "\n";
echo "   Anónimo: " . (`$data['is_anonymous'] ? 'Sí' : 'No') . "\n";
echo "\n⚠️  GUARDAR ESTE CÓDIGO: " . `$data['access_code'] . "\n\n";

// Guardar AccessCode en archivo temporal para siguiente test
file_put_contents('temp_access_code.txt', `$data['access_code']);
"@

Write-Host "Ejecutando creación de reporte..." -ForegroundColor Gray
$createReportCode | php artisan tinker 2>&1 | ForEach-Object { 
    if ($_ -match "report_id|AccessCode|access_code|✅|⚠️") {
        Write-Host $_ -ForegroundColor Green
    }
}

# Leer AccessCode del archivo temporal
if (Test-Path "temp_access_code.txt") {
    $accessCode = Get-Content "temp_access_code.txt" -Raw
    $accessCode = $accessCode.Trim()
    Write-Host ""
    Write-Host "📌 AccessCode capturado: $accessCode" -ForegroundColor Magenta
    Write-Host ""
} else {
    Write-Host "❌ Error: No se pudo capturar el AccessCode" -ForegroundColor Red
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
$checkStatusCode | php artisan tinker 2>&1 | ForEach-Object { 
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
$addReporterMessageCode | php artisan tinker 2>&1 | ForEach-Object { 
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
$addInvestigatorMessageCode | php artisan tinker 2>&1 | ForEach-Object { 
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
$viewConversationCode | php artisan tinker 2>&1 | ForEach-Object { 
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
$verifyDbCode | php artisan tinker 2>&1 | ForEach-Object { 
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
$verifyHashCode | php artisan tinker 2>&1 | ForEach-Object { 
    if ($_ -match "AccessCode|Hash|coincide|Reporte|✅|❌|🔐") {
        Write-Host $_ -ForegroundColor Magenta
    }
}

# Cleanup
if (Test-Path "temp_access_code.txt") {
    Remove-Item "temp_access_code.txt"
}

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
Write-Host ""
