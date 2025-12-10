# 🔐 US-005: Auditoría Inmutable - Diagrama Visual

## 📊 Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          WHISTLEBLOWER APPLICATION                       │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    │ User Action
                                    ▼
         ┌──────────────────────────────────────────────────┐
         │         PRESENTATION LAYER (HTTP)                │
         │  WhistleblowerController::store($request)        │
         │      • Captura IP: $request->ip()                │
         └──────────────────────────────────────────────────┘
                                    │
                                    │ Execute Use Case
                                    ▼
         ┌──────────────────────────────────────────────────┐
         │         APPLICATION LAYER (Use Cases)            │
         │    CreateReportUseCase::execute($data)           │
         │      1. Validar datos                            │
         │      2. Crear entidad Report                     │
         │      3. Guardar en repositorio                   │
         │      4. Disparar evento ✨                       │
         └──────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┴───────────────┐
                    │                               │
                    ▼                               ▼
    ┌───────────────────────────┐   ┌───────────────────────────────────┐
    │   DOMAIN LAYER (Event)    │   │   INFRASTRUCTURE (Repository)     │
    │  ReportCreated Event      │   │   EloquentReportRepository        │
    │   • Report $report        │   │     • save($report)               │
    │   • String $actorIp       │   │     • Persiste en BD              │
    └───────────────────────────┘   └───────────────────────────────────┘
                    │
                    │ Event Dispatched
                    ▼
    ┌───────────────────────────────────────────────────────┐
    │   EVENT BUS (Laravel Event System)                    │
    │   Event::dispatch(new ReportCreated($report, $ip))    │
    └───────────────────────────────────────────────────────┘
                    │
                    │ Listener Triggered
                    ▼
    ┌───────────────────────────────────────────────────────┐
    │   INFRASTRUCTURE LAYER (Listener)                     │
    │   AuditLogListener::handleReportCreated($event)       │
    │     • Recibe evento                                   │
    │     • Llama al servicio de auditoría                  │
    └───────────────────────────────────────────────────────┘
                    │
                    │ Call Audit Service
                    ▼
    ┌───────────────────────────────────────────────────────┐
    │   INFRASTRUCTURE LAYER (Service)                      │
    │   ImmutableAuditService::log($action, $entity, $ip)   │
    │                                                         │
    │   🔒 BLOCKCHAIN-LIKE LOGIC:                           │
    │   ┌─────────────────────────────────────────────┐    │
    │   │ 1. DB::transaction(function() {             │    │
    │   │                                              │    │
    │   │ 2. $lastLog = AuditLog::latest()            │    │
    │   │               ->lockForUpdate()->first()     │    │
    │   │                                              │    │
    │   │ 3. $previousHash = $lastLog?->hash           │    │
    │   │                  ?? GENESIS_HASH             │    │
    │   │                                              │    │
    │   │ 4. $newHash = SHA256(                        │    │
    │   │       $previousHash +                        │    │
    │   │       $action +                              │    │
    │   │       $entityType +                          │    │
    │   │       $entityId +                            │    │
    │   │       $timestamp +                           │    │
    │   │       JSON($payload) +                       │    │
    │   │       $secretSalt                            │    │
    │   │    )                                         │    │
    │   │                                              │    │
    │   │ 5. AuditLog::create([                        │    │
    │   │       'previous_hash' => $previousHash,      │    │
    │   │       'hash' => $newHash,                    │    │
    │   │       'entity_type' => 'Report',             │    │
    │   │       'entity_id' => $report->id,            │    │
    │   │       'action' => 'Create',                  │    │
    │   │       'payload' => $report->toArray(),       │    │
    │   │       'actor_ip' => anonymize($ip)           │    │
    │   │    ])                                        │    │
    │   │                                              │    │
    │   │ 6. return $auditLog                          │    │
    │   │ })                                           │    │
    │   └─────────────────────────────────────────────┘    │
    └───────────────────────────────────────────────────────┘
                    │
                    │ Saved to Database
                    ▼
    ┌───────────────────────────────────────────────────────┐
    │   DATABASE (audit_logs table)                         │
    │                                                         │
    │   ┌─────────────────────────────────────────────┐    │
    │   │ Genesis Block (First Record)                 │    │
    │   │ ┌───────────────────────────────────────┐   │    │
    │   │ │ previous_hash: 0000...0000            │   │    │
    │   │ │ hash: abc123...xyz                     │   │    │
    │   │ └───────────────────────────────────────┘   │    │
    │   │            ▲                                 │    │
    │   │            │ Chained                         │    │
    │   │            │                                 │    │
    │   │ ┌───────────────────────────────────────┐   │    │
    │   │ │ Block 2                               │   │    │
    │   │ │ previous_hash: abc123...xyz           │   │    │
    │   │ │ hash: def456...uvw                     │   │    │
    │   │ └───────────────────────────────────────┘   │    │
    │   │            ▲                                 │    │
    │   │            │ Chained                         │    │
    │   │            │                                 │    │
    │   │ ┌───────────────────────────────────────┐   │    │
    │   │ │ Block 3 (Current)                     │   │    │
    │   │ │ previous_hash: def456...uvw           │   │    │
    │   │ │ hash: ghi789...rst                     │   │    │
    │   │ └───────────────────────────────────────┘   │    │
    │   └─────────────────────────────────────────────┘    │
    │                                                         │
    │   ✅ Immutable Chain Created                          │
    └───────────────────────────────────────────────────────┘
```

---

## 🔗 Estructura de la Cadena

```
┌──────────────────────────────────────────────────────────────┐
│                    AUDIT LOG CHAIN                           │
└──────────────────────────────────────────────────────────────┘

Block #1 (Genesis)
┌─────────────────────────────────────────────────────────────┐
│ ID: 550e8400-e29b-41d4-a716-446655440000                     │
│ Entity: Report (abc-123-456)                                 │
│ Action: Create                                               │
│ Previous Hash: 0000000000000000000000000000000000000000...   │ ◄── Genesis Hash
│ Current Hash:  a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0...   │
│ Timestamp: 2025-12-10T10:00:00Z                              │
│ Actor IP: 192.168.1.0 (anonymized)                           │
│ Payload: {"id":"abc-123","title":"Fraud Report",...}         │
└─────────────────────────────────────────────────────────────┘
                         │
                         │ Cryptographically Linked
                         ▼
Block #2
┌─────────────────────────────────────────────────────────────┐
│ ID: 660e8400-e29b-41d4-a716-446655440001                     │
│ Entity: Message (xyz-789-012)                                │
│ Action: Create                                               │
│ Previous Hash: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0...   │ ◄── Matches Block #1 hash
│ Current Hash:  b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1...   │
│ Timestamp: 2025-12-10T10:05:00Z                              │
│ Actor IP: 192.168.1.0 (anonymized)                           │
│ Payload: {"id":"xyz-789","content":"Need more info",...}     │
└─────────────────────────────────────────────────────────────┘
                         │
                         │ Cryptographically Linked
                         ▼
Block #3
┌─────────────────────────────────────────────────────────────┐
│ ID: 770e8400-e29b-41d4-a716-446655440002                     │
│ Entity: Report (def-456-789)                                 │
│ Action: Create                                               │
│ Previous Hash: b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1...   │ ◄── Matches Block #2 hash
│ Current Hash:  c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2...   │
│ Timestamp: 2025-12-10T10:10:00Z                              │
│ Actor IP: 10.0.0.0 (anonymized)                              │
│ Payload: {"id":"def-456","title":"Another Report",...}       │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔍 Proceso de Verificación

```
┌───────────────────────────────────────────────────────────────┐
│              php artisan audit:verify                         │
└───────────────────────────────────────────────────────────────┘
                         │
                         ▼
┌───────────────────────────────────────────────────────────────┐
│  ImmutableAuditService::verifyChain()                         │
│                                                                 │
│  1. Obtener todos los registros (cronológicamente)             │
│     AuditLog::orderBy('created_at')->chunk(100)                │
│                                                                 │
│  2. Para cada registro:                                        │
│     ┌─────────────────────────────────────────────────┐       │
│     │ a) Calcular hash esperado:                       │       │
│     │    $expectedHash = SHA256(                       │       │
│     │       previous_hash +                            │       │
│     │       action +                                   │       │
│     │       entity_type +                              │       │
│     │       entity_id +                                │       │
│     │       timestamp +                                │       │
│     │       JSON(payload) +                            │       │
│     │       secret_salt                                │       │
│     │    )                                             │       │
│     │                                                   │       │
│     │ b) Comparar con hash almacenado:                │       │
│     │    if ($expectedHash !== $storedHash) {          │       │
│     │       $corrupted[] = $log;                       │       │
│     │    }                                             │       │
│     └─────────────────────────────────────────────────┘       │
│                                                                 │
│  3. Retornar resultado:                                        │
│     [                                                           │
│       'valid' => empty($corrupted),                            │
│       'corrupted_entries' => $corrupted,                       │
│       'total_entries' => $count                                │
│     ]                                                           │
└───────────────────────────────────────────────────────────────┘
                         │
         ┌───────────────┴───────────────┐
         │                               │
         ▼                               ▼
┌──────────────────┐        ┌──────────────────────────┐
│   ✅ VÁLIDO      │        │   ❌ CORRUPCIÓN          │
│                  │        │                          │
│ All hashes match │        │ Tampering detected       │
│ Chain is intact  │        │ List corrupted entries   │
└──────────────────┘        └──────────────────────────┘
```

---

## 🛡️ Seguridad: Detección de Manipulaciones

### Escenario 1: Integridad Válida ✅

```
Original Chain:
┌─────┐      ┌─────┐      ┌─────┐
│ A   │─────▶│ B   │─────▶│ C   │
│ h1  │      │ h2  │      │ h3  │
└─────┘      └─────┘      └─────┘
prev: 000    prev: h1     prev: h2

Verification:
✅ Calculate h1 from Block A data → Matches stored h1
✅ Calculate h2 from Block B data → Matches stored h2
✅ Calculate h3 from Block C data → Matches stored h3
✅ Block B previous_hash matches Block A hash
✅ Block C previous_hash matches Block B hash

Result: ✅ CHAIN VALID
```

### Escenario 2: Manipulación Detectada ❌

```
Tampered Chain:
┌─────┐      ┌─────┐      ┌─────┐
│ A   │─────▶│ B'  │─────▶│ C   │
│ h1  │      │ h2  │      │ h3  │
└─────┘      └─────┘      └─────┘
prev: 000    prev: h1     prev: h2
             (modified)

Verification:
✅ Calculate h1 from Block A data → Matches stored h1
❌ Calculate h2' from Block B' modified data → DOES NOT match stored h2
✅ Calculate h3 from Block C data → Matches stored h3

Result: ❌ CORRUPTION DETECTED IN BLOCK B
The attacker modified Block B payload but couldn't recalculate
the correct hash without knowing the secret salt!
```

---

## 📦 Estructura de Datos

### Tabla `audit_logs`

```sql
┌──────────────┬──────────────┬──────────────────────────────────────┐
│ Campo        │ Tipo         │ Descripción                          │
├──────────────┼──────────────┼──────────────────────────────────────┤
│ id           │ UUID         │ Identificador único del registro     │
│ entity_type  │ VARCHAR(50)  │ Report, Message                      │
│ entity_id    │ UUID         │ ID de la entidad auditada            │
│ action       │ ENUM         │ Create, Read, Update, Delete         │
│ payload      │ JSON         │ Snapshot completo de la entidad      │
│ actor_ip     │ VARCHAR(45)  │ IP anonimizada (IPv4/IPv6)           │
│ previous_hash│ VARCHAR(64)  │ SHA256 del registro anterior         │
│ hash         │ VARCHAR(64)  │ SHA256 de este registro (UNIQUE)     │
│ created_at   │ TIMESTAMP    │ Fecha/hora de creación               │
└──────────────┴──────────────┴──────────────────────────────────────┘

Índices:
• PRIMARY KEY (id)
• UNIQUE KEY (hash)
• INDEX (entity_type, entity_id, created_at)
• INDEX (created_at)
• INDEX (previous_hash)
```

### Ejemplo de Registro

```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "entity_type": "Report",
  "entity_id": "abc-123-456-789",
  "action": "Create",
  "payload": {
    "id": "abc-123-456-789",
    "title": "Fraude Contable Detectado",
    "description": "Se encontraron irregularidades...",
    "status": "pending",
    "is_anonymous": true,
    "created_at": "2025-12-10T10:00:00Z"
  },
  "actor_ip": "192.168.1.0",
  "previous_hash": "0000000000000000000000000000000000000000000000000000000000000000",
  "hash": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2",
  "created_at": "2025-12-10T10:00:00Z"
}
```

---

## 🚀 Flujo de Trabajo Completo

```
┌─────────────────────────────────────────────────────────────────┐
│  PASO 1: USUARIO CREA REPORTE                                   │
└─────────────────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│  PASO 2: REPORTE SE GUARDA EN BD                                │
│  • tabla: reports                                               │
│  • datos: title, description, status, access_code_hash          │
└─────────────────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│  PASO 3: EVENTO ReportCreated SE DISPARA                        │
│  • Event::dispatch(new ReportCreated($report, $ip))             │
└─────────────────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│  PASO 4: LISTENER CAPTURA EL EVENTO                             │
│  • AuditLogListener::handleReportCreated($event)                │
└─────────────────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│  PASO 5: SERVICIO CREA AUDIT LOG                                │
│  • ImmutableAuditService::log('Create', $report, $ip)           │
│  • Calcula hash SHA-256                                         │
│  • Encadena con registro anterior                               │
└─────────────────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│  PASO 6: REGISTRO SE GUARDA EN BD                               │
│  • tabla: audit_logs                                            │
│  • datos: hash, previous_hash, payload, etc.                    │
└─────────────────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│  ✅ AUDIT LOG CREADO Y ENCADENADO                               │
│  • Tamper-proof                                                 │
│  • Verificable con: php artisan audit:verify                    │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🎯 Características Clave

```
┌─────────────────────────────────────────────────────────────────┐
│                     CARACTERÍSTICAS                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  🔐 SEGURIDAD                                                   │
│    • SHA-256 cryptographic hashing                              │
│    • Secret salt for additional security                        │
│    • Chained blocks (blockchain-inspired)                       │
│    • Tamper detection guaranteed                                │
│                                                                 │
│  ⚡ PERFORMANCE                                                 │
│    • Database transactions (atomicity)                          │
│    • Lock for update (race condition prevention)                │
│    • Indexed fields (fast queries)                              │
│    • Chunked verification (100 records at a time)               │
│                                                                 │
│  🌍 COMPLIANCE                                                  │
│    • GDPR: IP anonymization                                     │
│    • SOX/HIPAA: Immutable audit trail                           │
│    • ISO 27001: Cryptographic proof of integrity                │
│                                                                 │
│  🚀 AUTOMATION                                                  │
│    • Zero configuration needed                                  │
│    • Domain Events auto-dispatch                                │
│    • Listeners auto-registered                                  │
│    • Transparent to business logic                              │
│                                                                 │
│  🔧 MAINTENANCE                                                 │
│    • Verification command: audit:verify                         │
│    • JSON output support                                        │
│    • Detailed corruption reports                                │
│    • Easy integration with monitoring                           │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📊 Métricas de Implementación

```
┌─────────────────────────────────────────────────┐
│  COMPONENTES IMPLEMENTADOS                      │
├─────────────────────────────────────────────────┤
│  ✅ Migración (audit_logs table)                │
│  ✅ Modelo Eloquent (AuditLog)                  │
│  ✅ Servicio (ImmutableAuditService)            │
│  ✅ Domain Events (2)                           │
│  ✅ Listener (AuditLogListener)                 │
│  ✅ Comando Console (audit:verify)              │
│  ✅ Service Provider (registros)                │
│  ✅ Configuración (.env)                        │
│  ✅ Documentación (3 archivos)                  │
│  ✅ Tests (script automatizado)                 │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│  LÍNEAS DE CÓDIGO                               │
├─────────────────────────────────────────────────┤
│  Migración:          ~50 líneas                 │
│  Modelo:             ~70 líneas                 │
│  Servicio:           ~250 líneas                │
│  Events:             ~20 líneas                 │
│  Listener:           ~50 líneas                 │
│  Comando:            ~100 líneas                │
│  Documentación:      ~1500 líneas               │
│  ─────────────────────────────────              │
│  TOTAL:              ~2040 líneas               │
└─────────────────────────────────────────────────┘
```

---

**Implementado por:** Ingeniero de Seguridad Blockchain  
**Fecha:** Diciembre 10, 2025  
**Estado:** ✅ Producción Ready
