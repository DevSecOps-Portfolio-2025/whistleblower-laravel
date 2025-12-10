# 🔐 US-005: Auditoría Inmutable - Sistema de Logs Encadenados

## 📋 Descripción

Sistema de auditoría blockchain-like que registra cada acción en una cadena criptográfica inmutable. Cada registro está encadenado con el anterior mediante hashes SHA-256, lo que hace imposible modificar registros históricos sin que sea detectado.

## 🏗️ Arquitectura

### Componentes Implementados

1. **Tabla `audit_logs`** (Migración)
2. **Modelo `AuditLog`** (Eloquent)
3. **Servicio `ImmutableAuditService`** (Infrastructure)
4. **Domain Events** (`ReportCreated`, `MessageCreated`)
5. **Listener `AuditLogListener`** (Infrastructure)
6. **Comando `audit:verify`** (Console)

## 📊 Esquema de Base de Datos

```sql
CREATE TABLE audit_logs (
    id UUID PRIMARY KEY,
    entity_type VARCHAR(50),    -- Report, Message
    entity_id UUID,
    action ENUM,                -- Create, Read, Update, Delete
    payload JSON,               -- Snapshot completo de la entidad
    actor_ip VARCHAR(45),       -- IP anonimizada (GDPR)
    previous_hash VARCHAR(64),  -- Hash del registro anterior
    hash VARCHAR(64) UNIQUE,    -- Hash de este registro
    created_at TIMESTAMP
);
```

## 🔗 Cadena Criptográfica

### Algoritmo de Encadenado

```
Hash = SHA256(
    previous_hash +
    action +
    entity_type +
    entity_id +
    timestamp +
    JSON(payload) +
    secret_salt
)
```

### Genesis Block

El primer registro usa un "Genesis Hash" de ceros:
```
0000000000000000000000000000000000000000000000000000000000000000
```

## 🚀 Instalación y Configuración

### 1. Ejecutar la Migración

```powershell
php artisan migrate
```

### 2. Configurar el Salt (Requerido)

Agrega a tu archivo `.env`:

```env
APP_AUDIT_SALT=tu-salt-secreto-aleatorio-de-64-caracteres-minimo
```

**⚠️ IMPORTANTE:** 
- Genera un salt seguro y manténlo secreto
- Cambiar el salt invalida toda la cadena de auditoría existente
- Usa: `openssl rand -base64 64` para generar uno

### 3. Verificar Configuración

El servicio está automáticamente registrado en `WhistleblowingServiceProvider`.

## 📝 Uso Automático

La auditoría funciona **automáticamente** mediante Domain Events:

### Eventos Auditados

1. **Creación de Report**
   ```php
   // Se dispara automáticamente cuando se crea un Report
   Event::dispatch(new ReportCreated($report, $actorIp));
   ```

2. **Creación de Message**
   ```php
   // Se dispara automáticamente cuando se crea un Message
   Event::dispatch(new MessageCreated($message, $actorIp));
   ```

### Captura Automática de IP

Los controladores capturan automáticamente la IP del actor:

```php
// En CreateReportUseCase
$report = $useCase->execute([
    'title' => $data['title'],
    'description' => $data['description'],
    'actorIp' => $request->ip(), // ← Capturado automáticamente
]);
```

## 🔍 Verificación de Integridad

### Comando de Verificación

```powershell
# Verificar toda la cadena de auditoría
php artisan audit:verify

# Ver resultados en formato JSON
php artisan audit:verify --json
```

### Salida en Caso de Éxito

```
🔍 Verifying audit chain integrity...

📊 Total audit log entries: 150

✅ AUDIT CHAIN INTEGRITY VERIFIED
All hashes match. No corruption detected.
```

### Salida en Caso de Corrupción

```
🔍 Verifying audit chain integrity...

📊 Total audit log entries: 150

❌ CORRUPTION DETECTED IN AUDIT CHAIN

⚠️  The following entries have been tampered with:

Found 2 corrupted entries:

┌──────────────┬─────────────┬──────────────┬────────┬─────────────────────┬──────────────────┐
│ ID           │ Entity Type │ Entity ID    │ Action │ Created At          │ Stored Hash      │
├──────────────┼─────────────┼──────────────┼────────┼─────────────────────┼──────────────────┤
│ abc-123-...  │ Report      │ xyz-456-...  │ Update │ 2025-12-10T14:30:00 │ a1b2c3d4e5f6...  │
│ def-789-...  │ Message     │ uvw-012-...  │ Create │ 2025-12-10T15:45:00 │ 1a2b3c4d5e6f...  │
└──────────────┴─────────────┴──────────────┴────────┴─────────────────────┴──────────────────┘

🚨 SECURITY ALERT: Audit log manipulation detected!
Immediate investigation required.
```

## 🔒 Características de Seguridad

### 1. Anonimización de IP (GDPR)

```php
// IPv4: 192.168.1.123 → 192.168.1.0
// IPv6: 2001:0db8:85a3:0000:0000:8a2e:0370:7334 → 2001:0db8:85a3:0000::
```

### 2. Transacciones Atómicas

Usa transacciones de base de datos para garantizar consistencia:

```php
DB::transaction(function () {
    // 1. Obtener último registro (con lock)
    // 2. Calcular nuevo hash
    // 3. Guardar registro
});
```

### 3. Lock Optimista

Usa `lockForUpdate()` para prevenir condiciones de carrera.

### 4. Payload Completo

Guarda un snapshot completo de la entidad:

```json
{
    "id": "abc-123-456",
    "title": "Reporte de Fraude",
    "description": "Descripción del reporte",
    "status": "pending",
    "created_at": "2025-12-10T10:00:00Z"
}
```

## 🧪 Testing

### Test Manual

```powershell
# 1. Crear un reporte
$response = Invoke-RestMethod -Uri "http://localhost:8000/api/v1/whistleblowing/reports" `
    -Method POST `
    -ContentType "application/json" `
    -Body '{"title":"Test Audit","description":"Testing immutable audit trail"}'

# 2. Verificar que se creó el registro de auditoría
php artisan tinker
>>> App\Models\AuditLog::count()
=> 1

# 3. Verificar integridad
php artisan audit:verify
```

### Test de Manipulación

```powershell
# 1. Crear varios registros
# 2. Modificar manualmente un registro en la BD
php artisan tinker
>>> $log = Src\Whistleblowing\Infrastructure\Persistence\Eloquent\Models\AuditLog::first()
>>> $log->payload = ['modified' => true]
>>> $log->save()

# 3. Verificar (debe detectar corrupción)
php artisan audit:verify
```

## 📖 API del Servicio

### Método `log()`

```php
use Src\Whistleblowing\Infrastructure\Services\ImmutableAuditService;

$auditService->log(
    action: 'Create',      // Create, Read, Update, Delete
    entity: $report,       // Entidad de dominio
    ip: '192.168.1.123'    // IP del actor (opcional)
);
```

### Método `verifyEntry()`

```php
$isValid = $auditService->verifyEntry($auditLog);
// Returns: true/false
```

### Método `verifyChain()`

```php
$result = $auditService->verifyChain();
// Returns:
// [
//     'valid' => bool,
//     'corrupted_entries' => array,
//     'total_entries' => int
// ]
```

## 🎯 Casos de Uso

### 1. Cumplimiento Normativo

- **GDPR**: Anonimización automática de IPs
- **SOX/HIPAA**: Trail de auditoría inmutable
- **ISO 27001**: Evidencia de integridad de logs

### 2. Investigaciones Forenses

- Timeline completo de acciones
- Payload histórico de entidades
- Detección de manipulaciones

### 3. Transparencia Organizacional

- Prueba criptográfica de no-manipulación
- Confianza en el sistema de denuncias
- Auditorías externas verificables

## ⚠️ Consideraciones

### Performance

- **Transacciones**: Cada log usa una transacción DB
- **Locks**: Usa locks para prevenir race conditions
- **Chunking**: La verificación procesa en lotes de 100

### Almacenamiento

- Cada registro incluye payload completo (JSON)
- Considerar particionamiento para gran volumen
- Implementar archivado para registros antiguos

### Mantenimiento

- **Nunca** eliminar registros de `audit_logs`
- Backup frecuente de la tabla
- Monitorear el comando `audit:verify` en producción

## 🔄 Integración con Otros Bounded Contexts

Para auditar otras entidades:

1. **Crear Domain Event**
   ```php
   class EntityCreated {
       public function __construct(
           public readonly Entity $entity,
           public readonly ?string $actorIp = null
       ) {}
   }
   ```

2. **Registrar Listener**
   ```php
   // En WhistleblowingServiceProvider::registerEventListeners()
   Event::listen(
       EntityCreated::class,
       [AuditLogListener::class, 'handleEntityCreated']
   );
   ```

3. **Agregar Método al Listener**
   ```php
   public function handleEntityCreated(EntityCreated $event): void {
       $this->auditService->log('Create', $event->entity, $event->actorIp);
   }
   ```

## 📚 Referencias

- [Blockchain Basics](https://en.wikipedia.org/wiki/Blockchain)
- [SHA-256 Algorithm](https://en.wikipedia.org/wiki/SHA-2)
- [GDPR IP Anonymization](https://ico.org.uk/for-organisations/guide-to-data-protection/guide-to-the-general-data-protection-regulation-gdpr/lawful-basis-for-processing/legitimate-interests/)

## ✅ Checklist de Implementación

- [x] Migración `audit_logs` creada
- [x] Modelo `AuditLog` implementado
- [x] Servicio `ImmutableAuditService` con encadenado
- [x] Domain Events creados
- [x] Listener configurado
- [x] Comando `audit:verify` implementado
- [x] Service Provider actualizado
- [x] Configuración `audit_salt` agregada
- [x] Anonimización de IP implementada
- [x] Documentación completa

---

**Autor:** Ingeniero de Seguridad Blockchain  
**Fecha:** Diciembre 10, 2025  
**Versión:** 1.0.0
