# 🚀 Quick Start: Auditoría Inmutable

## Instalación Rápida

```powershell
# 1. Ejecutar migración
php artisan migrate

# 2. Generar salt de auditoría (CRÍTICO)
$salt = [Convert]::ToBase64String([System.Security.Cryptography.RandomNumberGenerator]::GetBytes(64))
Write-Host "APP_AUDIT_SALT=$salt"

# 3. Agregar a .env
# Copia el salt generado arriba y agrégalo a tu archivo .env
# APP_AUDIT_SALT=tu-salt-aqui

# 4. Verificar instalación
php artisan audit:verify
```

---

## Uso Básico

### La auditoría funciona AUTOMÁTICAMENTE

```powershell
# 1. Iniciar servidor
php artisan serve

# 2. Crear un reporte (genera audit log automáticamente)
Invoke-RestMethod -Uri "http://localhost:8000/api/v1/whistleblowing/reports" `
    -Method POST `
    -ContentType "application/json" `
    -Body '{"title":"Test Audit","description":"Este reporte genera un log de auditoría automáticamente"}'

# 3. Verificar que se creó el audit log
php artisan audit:verify
```

---

## Comandos Disponibles

### Verificar Integridad
```powershell
# Ver resultados en consola (formato bonito)
php artisan audit:verify

# Ver resultados en JSON
php artisan audit:verify --json
```

### Consultar Logs (usando Tinker)
```powershell
php artisan tinker
```

```php
// Ver todos los logs
\Src\Whistleblowing\Infrastructure\Persistence\Eloquent\Models\AuditLog::all();

// Ver el último log
\Src\Whistleblowing\Infrastructure\Persistence\Eloquent\Models\AuditLog::latest()->first();

// Contar logs por tipo
\Src\Whistleblowing\Infrastructure\Persistence\Eloquent\Models\AuditLog::groupBy('entity_type')->selectRaw('entity_type, count(*) as total')->get();

// Ver logs de un Report específico
\Src\Whistleblowing\Infrastructure\Persistence\Eloquent\Models\AuditLog::where('entity_id', 'tu-uuid-aqui')->get();
```

---

## Script de Pruebas Automatizado

```powershell
# Ejecutar todas las pruebas
.\test-audit-trail.ps1

# El script verifica:
# ✅ Servidor funcionando
# ✅ Tabla existe
# ✅ Registros se crean automáticamente
# ✅ Integridad de la cadena
# ✅ Detección de manipulaciones (opcional)
```

---

## Verificación de Seguridad

### Test de Manipulación

```powershell
# 1. Crear algunos registros
.\test-audit-trail.ps1

# 2. Modificar un registro manualmente
php artisan tinker
```

```php
$log = \Src\Whistleblowing\Infrastructure\Persistence\Eloquent\Models\AuditLog::first();
$log->payload = ['manipulated' => true];
$log->save();
exit;
```

```powershell
# 3. Verificar (debe detectar corrupción)
php artisan audit:verify
```

**Resultado esperado:**
```
❌ CORRUPTION DETECTED IN AUDIT CHAIN
⚠️  The following entries have been tampered with:
...
```

---

## Integración en tu Código

### Opción 1: Automática (Recomendada)

**Ya está implementada!** Los Domain Events se disparan automáticamente.

### Opción 2: Manual (Avanzada)

Si necesitas registrar auditoría manualmente:

```php
use Src\Whistleblowing\Infrastructure\Services\ImmutableAuditService;

// En tu controlador o servicio
public function __construct(
    private readonly ImmutableAuditService $auditService
) {}

// Registrar una acción
$this->auditService->log(
    action: 'Create',
    entity: $report,
    ip: $request->ip()
);
```

---

## Configuración Avanzada

### Variables de Entorno

```env
# .env
APP_AUDIT_SALT=tu-salt-secreto-de-64-caracteres-minimo
```

**⚠️ IMPORTANTE:**
- El salt debe ser secreto
- **NUNCA** cambies el salt después del primer uso
- Cambiar el salt invalida toda la cadena existente

### Generar Salt Seguro

```powershell
# PowerShell
$salt = [Convert]::ToBase64String([System.Security.Cryptography.RandomNumberGenerator]::GetBytes(64))
Write-Host "APP_AUDIT_SALT=$salt"

# Bash/Linux
openssl rand -base64 64
```

---

## Monitoreo en Producción

### Verificación Periódica

```bash
# Cron job (Linux) - Verificar cada 6 horas
0 */6 * * * cd /path/to/project && php artisan audit:verify --json >> /var/log/audit-verify.log 2>&1
```

```powershell
# Task Scheduler (Windows) - Script de verificación
$result = php artisan audit:verify --json | ConvertFrom-Json
if (-not $result.valid) {
    # Enviar alerta
    Send-MailMessage -To "security@example.com" -Subject "AUDIT CORRUPTION DETECTED" -Body "..."
}
```

### Alertas Automáticas

```php
// En tu aplicación Laravel
use Illuminate\Console\Scheduling\Schedule;

protected function schedule(Schedule $schedule)
{
    // Verificar integridad cada 6 horas
    $schedule->command('audit:verify --json')
        ->everySixHours()
        ->sendOutputTo(storage_path('logs/audit-verify.log'))
        ->emailOutputOnFailure('security@example.com');
}
```

---

## Resolución de Problemas

### Error: "Tabla audit_logs no existe"
```powershell
php artisan migrate
```

### Error: "APP_AUDIT_SALT no configurado"
```powershell
# Generar salt
$salt = [Convert]::ToBase64String([System.Security.Cryptography.RandomNumberGenerator]::GetBytes(64))

# Agregar a .env
Add-Content -Path .env -Value "`nAPP_AUDIT_SALT=$salt"
```

### Error: "Corruption detected"
```powershell
# Ver detalles
php artisan audit:verify --json

# Si es esperado (cambio de salt, migración, etc.)
php artisan migrate:fresh --seed

# Si es manipulación real:
# 1. Revisar logs
# 2. Contactar equipo de seguridad
# 3. Investigar registros corruptos
```

### No se crean audit logs
```powershell
# Verificar que los eventos están registrados
php artisan event:list | Select-String "Report"

# Verificar Service Provider
php artisan config:clear
php artisan cache:clear
```

---

## Performance

### Optimización de Consultas

```php
// Obtener logs con paginación
\Src\Whistleblowing\Infrastructure\Persistence\Eloquent\Models\AuditLog::latest()
    ->paginate(50);

// Filtrar por rango de fechas
\Src\Whistleblowing\Infrastructure\Persistence\Eloquent\Models\AuditLog::whereBetween('created_at', [$start, $end])
    ->get();

// Índices recomendados (ya incluidos en migración)
// - created_at
// - entity_type
// - entity_id
// - hash
```

### Archivado de Logs Antiguos

```sql
-- Crear tabla de archivo
CREATE TABLE audit_logs_archive LIKE audit_logs;

-- Mover logs antiguos (> 1 año)
INSERT INTO audit_logs_archive 
SELECT * FROM audit_logs 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- Eliminar después de verificar
-- DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

---

## Recursos Adicionales

- **Documentación completa:** [IMMUTABLE_AUDIT.md](IMMUTABLE_AUDIT.md)
- **Resumen ejecutivo:** [RESUMEN_AUDIT.md](RESUMEN_AUDIT.md)
- **Índice general:** [INDEX.md](INDEX.md)

---

## Checklist de Producción

- [ ] Migración ejecutada
- [ ] `APP_AUDIT_SALT` configurado y secreto
- [ ] Verificación manual ejecutada: `php artisan audit:verify`
- [ ] Backup de base de datos configurado
- [ ] Monitoreo automático configurado
- [ ] Alertas de seguridad configuradas
- [ ] Equipo entrenado en uso del comando
- [ ] Procedimiento de respuesta a incidentes documentado

---

**¿Preguntas?** Consulta [IMMUTABLE_AUDIT.md](IMMUTABLE_AUDIT.md) para documentación detallada.
