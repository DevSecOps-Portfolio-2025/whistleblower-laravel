# 🐳 Guía Rápida - Docker + Comunicación Bidireccional

## ⚡ Setup en 3 Pasos

### 1️⃣ Levantar Docker

```powershell
docker-compose up -d
```

### 2️⃣ Ejecutar Migraciones

```powershell
docker-compose exec app php artisan migrate
```

### 3️⃣ Ejecutar Tests

```powershell
.\test-bidirectional-docker.ps1
```

---

## 🎯 Comandos Más Usados

### Verificar Estado

```powershell
# Ver contenedores corriendo
docker-compose ps

# Ver logs de la app
docker-compose logs -f app

# Estado de migraciones
docker-compose exec app php artisan migrate:status
```

### Tinker Rápido

```powershell
# Entrar a Tinker
docker-compose exec app php artisan tinker

# Ejecutar comando único
docker-compose exec app php artisan tinker --execute="echo DB::table('reports')->count();"
```

### Crear Reporte (One-liner)

```powershell
$code = @"
use Src\Whistleblowing\Application\UseCases\CreateReportUseCase;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\EloquentReportRepository;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\ReportModel;
`$repository = new EloquentReportRepository(new ReportModel());
`$useCase = new CreateReportUseCase(`$repository);
`$response = `$useCase->execute(['title' => 'Test', 'description' => 'Test description with more than 20 chars', 'reporterId' => null]);
echo `$response->toArray()['access_code'];
"@

$accessCode = $code | docker-compose exec -T app php artisan tinker | Select-String -Pattern "[A-Za-z0-9]{16}"
Write-Host "AccessCode: $accessCode"
```

### Consultar Reporte

```powershell
# Reemplazar YOUR_CODE con tu AccessCode
$code = @"
use Src\Whistleblowing\Application\UseCases\CheckReportStatusUseCase;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\EloquentReportRepository;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\ReportModel;
`$repository = new EloquentReportRepository(new ReportModel());
`$useCase = new CheckReportStatusUseCase(`$repository);
print_r(`$useCase->execute('YOUR_CODE')->toArray());
"@

$code | docker-compose exec -T app php artisan tinker
```

### Agregar Mensaje

```powershell
# Reemplazar YOUR_CODE con tu AccessCode
$code = @"
use Src\Whistleblowing\Application\UseCases\AddMessageToReportUseCase;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\EloquentReportRepository;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\ReportModel;
`$repository = new EloquentReportRepository(new ReportModel());
`$useCase = new AddMessageToReportUseCase(`$repository);
print_r(`$useCase->execute('YOUR_CODE', 'Mi mensaje', 'reporter'));
"@

$code | docker-compose exec -T app php artisan tinker
```

---

## 🗄️ Base de Datos

### Acceso Directo

```powershell
# MySQL CLI
docker-compose exec db mysql -u wb_user -psecret whistleblower
```

### Consultas Rápidas

```powershell
# Contar reportes
docker-compose exec db mysql -u wb_user -psecret whistleblower -e "SELECT COUNT(*) as total FROM reports;"

# Ver últimos reportes
docker-compose exec db mysql -u wb_user -psecret whistleblower -e "SELECT id, title, status, created_at FROM reports ORDER BY created_at DESC LIMIT 5;"

# Contar mensajes por autor
docker-compose exec db mysql -u wb_user -psecret whistleblower -e "SELECT author, COUNT(*) as total FROM messages GROUP BY author;"

# Ver mensajes de un reporte (reemplazar REPORT_ID)
docker-compose exec db mysql -u wb_user -psecret whistleblower -e "SELECT * FROM messages WHERE report_id='REPORT_ID' ORDER BY created_at;"
```

---

## 🧹 Mantenimiento

### Limpiar Datos de Prueba

```powershell
# Truncar tablas
docker-compose exec app php artisan tinker --execute="DB::table('messages')->truncate(); DB::table('reports')->truncate();"

# O vía MySQL
docker-compose exec db mysql -u wb_user -psecret whistleblower -e "TRUNCATE TABLE messages; TRUNCATE TABLE reports;"
```

### Reiniciar Todo

```powershell
# Rollback y re-migrar
docker-compose exec app php artisan migrate:rollback --step=2
docker-compose exec app php artisan migrate

# O recrear todo
docker-compose exec app php artisan migrate:fresh
```

### Limpiar Cache

```powershell
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
docker-compose exec app composer dump-autoload
```

---

## 🐛 Troubleshooting

### Contenedor no inicia

```powershell
# Ver logs
docker-compose logs app

# Reconstruir imagen
docker-compose build --no-cache app
docker-compose up -d
```

### Error de conexión a BD

```powershell
# Verificar que MySQL esté corriendo
docker-compose ps db

# Ver logs de MySQL
docker-compose logs db

# Test de conexión
docker-compose exec app php artisan tinker --execute="DB::connection()->getPdo();"
```

### Permisos en storage/

```powershell
# Arreglar permisos
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

---

## 📊 Estadísticas Rápidas

```powershell
# Crear script de stats
$stats = @"
echo '📊 ESTADÍSTICAS DEL SISTEMA';
echo '===========================';
echo 'Reportes: ' . DB::table('reports')->count();
echo 'Mensajes: ' . DB::table('messages')->count();
echo '';
echo 'Por autor:';
foreach(DB::table('messages')->select('author', DB::raw('count(*) as total'))->groupBy('author')->get() as `$s) {
    echo '  ' . ucfirst(`$s->author) . ': ' . `$s->total;
}
"@

$stats | docker-compose exec -T app php artisan tinker
```

---

## 🔐 Verificar Hash

```powershell
# Verificar que un AccessCode específico existe
$verify = @"
`$code = 'TU_ACCESS_CODE_AQUI';
`$hash = hash('sha256', `$code);
`$exists = DB::table('reports')->where('access_code_hash', `$hash)->exists();
echo `$exists ? '✅ Código válido' : '❌ Código inválido';
"@

$verify | docker-compose exec -T app php artisan tinker
```

---

## 🚀 Workflow Completo

```powershell
# 1. Asegurar que Docker está corriendo
docker-compose up -d

# 2. Ejecutar migraciones si es primera vez
docker-compose exec app php artisan migrate

# 3. Ejecutar tests automatizados
.\test-bidirectional-docker.ps1

# 4. Crear reporte manual si quieres
docker-compose exec app php artisan tinker
# ... crear reporte ...
# ... guardar AccessCode ...

# 5. Ver en BD
docker-compose exec db mysql -u wb_user -psecret whistleblower -e "SELECT * FROM reports\G"

# 6. Agregar mensajes
docker-compose exec app php artisan tinker
# ... agregar mensajes ...

# 7. Ver conversación
docker-compose exec db mysql -u wb_user -psecret whistleblower -e "SELECT * FROM messages ORDER BY created_at\G"

# 8. Limpiar cuando termines
docker-compose exec app php artisan tinker --execute="DB::table('messages')->truncate(); DB::table('reports')->truncate();"
```

---

## 💡 Tips

1. **Alias útiles**: Agrega esto a tu perfil de PowerShell:
```powershell
function dce { docker-compose exec app $args }
function dcl { docker-compose logs -f $args }
function dct { docker-compose exec app php artisan tinker }
```

Uso:
```powershell
dce php artisan migrate
dce composer dump-autoload
dct  # Abre tinker directamente
```

2. **Guardar AccessCodes**: Usa un archivo de texto temporal:
```powershell
# Al crear reporte, guardar código
$accessCode | Out-File -FilePath "my_codes.txt" -Append

# Leer códigos guardados
Get-Content "my_codes.txt"
```

3. **Logs en vivo**: Mantén una ventana con logs abierta:
```powershell
docker-compose logs -f app
```

---

**Última actualización:** 9 de diciembre de 2025  
**Versión Docker:** Compatible con `docker-compose.yml` v3.8
