# Resumen de Implementación - Comunicación Bidireccional

## ✅ Implementación Completada

Se ha extendido exitosamente el sistema Whistleblowing para soportar **comunicación bidireccional segura** entre denunciantes e investigadores.

---

## 📦 Componentes Implementados

### 🗄️ Base de Datos (Migrations)

#### 1. **Migración: `add_access_code_to_reports_table`**
- Archivo: `database/migrations/2025_12_10_023622_add_access_code_to_reports_table.php`
- Cambios:
  - Columna `access_code_hash` (string, 64 caracteres)
  - Índice en `access_code_hash` para búsquedas rápidas
- **Seguridad**: Almacena SHA-256 del AccessCode, nunca el valor plano

#### 2. **Migración: `create_messages_table`**
- Archivo: `database/migrations/2025_12_10_023630_create_messages_table.php`
- Estructura:
  ```sql
  - id (string, PK) - UUID del mensaje
  - report_id (string, FK) - Referencia al reporte
  - content (text) - Contenido del mensaje
  - author (enum) - 'reporter' | 'investigator'
  - created_at (timestamp) - Fecha de creación
  ```
- **Relación**: Foreign key con `reports.id`, cascade delete
- **Índices**: `report_id`, `created_at`

---

### 🏗️ Capa de Infraestructura

#### 3. **MessageModel** (Eloquent)
- Archivo: `src/Whistleblowing/Infrastructure/Persistence/Eloquent/MessageModel.php`
- Características:
  - Modelo Eloquent para tabla `messages`
  - Relación `belongsTo` con `ReportModel`
  - UUID como primary key (no auto-incremento)
  - Sin timestamp `updated_at`

#### 4. **ReportModel Actualizado**
- Archivo: `src/Whistleblowing/Infrastructure/Persistence/Eloquent/ReportModel.php`
- Cambios:
  - Campo `access_code_hash` en `$fillable`
  - Relación `hasMany` con `MessageModel`
  - Ordenamiento de mensajes por `created_at ASC`

#### 5. **EloquentReportRepository Actualizado**
- Archivo: `src/Whistleblowing/Infrastructure/Persistence/Eloquent/EloquentReportRepository.php`
- Cambios principales:

**Nuevo método: `findByAccessCode(AccessCode $code)`**
```php
// 1. Hashea el código plano con SHA-256
// 2. Busca en BD por access_code_hash
// 3. Carga mensajes eager (with('messages'))
// 4. Retorna entidad Report con AccessCode original
```

**Método `save()` actualizado:**
- Guarda `access_code_hash` hasheado
- Persiste mensajes nuevos

**Método `update()` actualizado:**
- Actualiza hash si cambia AccessCode
- Sincroniza mensajes (delete + create)

**Método privado: `toDomainEntity()`**
- Convierte `MessageModel` → `Message` entity
- Mapea todos los mensajes del reporte
- Acepta `AccessCode` opcional (para `findByAccessCode`)

**Método privado: `hashAccessCode()`**
- Implementa: `hash('sha256', $code->value())`

---

### 🎯 Capa de Aplicación

#### 6. **DTOs Creados**

**CreateReportResponseDTO**
- Archivo: `src/Whistleblowing/Application/DTOs/CreateReportResponseDTO.php`
- Propósito: Respuesta de creación de reporte
- Campos:
  - `reportId`, `accessCode` (⚠️ texto plano, única vez)
  - `title`, `status`, `isAnonymous`, `createdAt`
  - `message` con advertencia de guardar el código

**MessageDTO**
- Archivo: `src/Whistleblowing/Application/DTOs/MessageDTO.php`
- Propósito: Representar un mensaje individual
- Campos: `id`, `content`, `author`, `createdAt`

**ReportStatusDTO**
- Archivo: `src/Whistleblowing/Application/DTOs/ReportStatusDTO.php`
- Propósito: Estado completo del reporte con mensajes
- Campos:
  - Info del reporte: `reportId`, `title`, `description`, `status`
  - Metadatos: `isAnonymous`, `messageCount`, `createdAt`, `updatedAt`
  - Conversación: `messages[]` (array de `MessageDTO`)

#### 7. **Use Cases**

**CreateReportUseCase Actualizado**
- Archivo: `src/Whistleblowing/Application/UseCases/CreateReportUseCase.php`
- Cambios:
  - Usa `Report::create()` factory method (genera AccessCode automático)
  - UUID v4 para IDs
  - Retorna `CreateReportResponseDTO` con AccessCode en texto plano
  - ⚠️ **Crítico**: Esta es la única vez que se muestra el código sin hashear

**CheckReportStatusUseCase** (NUEVO)
- Archivo: `src/Whistleblowing/Application/UseCases/CheckReportStatusUseCase.php`
- Propósito: Consultar estado del reporte por AccessCode
- Flujo:
  1. Valida AccessCode (16 caracteres alfanuméricos)
  2. Busca reporte en repositorio (hash coincidente)
  3. Verifica seguridad adicional con `hasAccessCode()`
  4. Convierte mensajes a DTOs
  5. Retorna `ReportStatusDTO`
- **Excepciones**:
  - `InvalidArgumentException`: AccessCode inválido
  - `RuntimeException`: Reporte no encontrado

**AddMessageToReportUseCase** (NUEVO)
- Archivo: `src/Whistleblowing/Application/UseCases/AddMessageToReportUseCase.php`
- Propósito: Agregar mensaje a reporte existente
- Parámetros:
  - `accessCodeString`: Código de acceso
  - `content`: Contenido del mensaje (3-5000 caracteres)
  - `author`: 'reporter' | 'investigator'
- Flujo:
  1. Valida contenido del mensaje
  2. Valida y reconstituye AccessCode
  3. Busca reporte por AccessCode
  4. Verifica seguridad
  5. Crea mensaje con `Message::create()`
  6. Agrega mensaje al reporte
  7. Persiste con `repository->update()`
- Retorna: Información del mensaje creado

---

## 🔐 Seguridad Implementada

### 1. **AccessCode**
- ✅ Generación criptográficamente segura (`random_int`)
- ✅ 16 caracteres = ~95 bits de entropía
- ✅ Nunca almacenado en texto plano
- ✅ SHA-256 para hashing (64 caracteres hex)
- ✅ Comparación con `hash_equals()` (timing-safe)

### 2. **Búsqueda Segura**
```php
// ❌ INSEGURO: No se puede hacer
SELECT * FROM reports WHERE access_code = 'plain_code'

// ✅ SEGURO: Implementado
$hash = hash('sha256', $plainCode);
SELECT * FROM reports WHERE access_code_hash = $hash
```

### 3. **Validaciones**
- AccessCode: 16 caracteres alfanuméricos
- Mensajes: 3-5000 caracteres
- Autor: Solo 'reporter' o 'investigator'
- Doble verificación en `CheckReportStatusUseCase`

---

## 📊 Flujo de Datos

### Crear Reporte
```
Controller
  ↓
CreateReportUseCase
  ↓
Report::create() → AccessCode::generate()
  ↓
EloquentReportRepository::save()
  → hash('sha256', accessCode)
  → INSERT INTO reports (access_code_hash, ...)
  ↓
CreateReportResponseDTO (AccessCode texto plano)
  ↓
JSON Response con advertencia
```

### Consultar Estado
```
Controller (recibe AccessCode plano)
  ↓
CheckReportStatusUseCase
  ↓
AccessCode::fromString() → valida formato
  ↓
repository->findByAccessCode()
  → hash('sha256', accessCode)
  → SELECT * WHERE access_code_hash = $hash
  → Eager load messages
  ↓
Report entity con Messages
  ↓
Mapear a DTOs
  ↓
ReportStatusDTO
  ↓
JSON Response
```

### Agregar Mensaje
```
Controller
  ↓
AddMessageToReportUseCase
  ↓
Buscar Report por AccessCode (hasheado)
  ↓
Message::create(content, MessageAuthor)
  ↓
report->addMessage($message)
  ↓
repository->update($report)
  → UPDATE reports ...
  → DELETE FROM messages WHERE report_id = ...
  → INSERT INTO messages (...)
  ↓
JSON Response
```

---

## 🧪 Casos de Uso Soportados

### 1. **Denunciante Anónimo Crea Reporte**
```php
$response = $createUseCase->execute([
    'title' => 'Fraude detectado',
    'description' => 'Detalles...',
    'reporterId' => null
]);

// Respuesta incluye AccessCode: "a3B5kL9mP2qR7sT4"
// ⚠️ Usuario debe guardarlo - NO se puede recuperar
```

### 2. **Denunciante Consulta Su Reporte**
```php
$status = $checkUseCase->execute('a3B5kL9mP2qR7sT4');

// Retorna: título, descripción, estado, mensajes
```

### 3. **Denunciante Agrega Información**
```php
$result = $addMessageUseCase->execute(
    'a3B5kL9mP2qR7sT4',
    'Tengo evidencia adicional',
    'reporter'
);
```

### 4. **Investigador Responde**
```php
$result = $addMessageUseCase->execute(
    'a3B5kL9mP2qR7sT4',
    'Hemos iniciado la investigación',
    'investigator'
);
```

### 5. **Verificar Conversación Completa**
```php
$status = $checkUseCase->execute('a3B5kL9mP2qR7sT4');

foreach ($status->messages as $msg) {
    echo "[{$msg->author}]: {$msg->content}\n";
}
```

---

## 📁 Archivos Creados/Modificados

### Creados (14 archivos)
```
Domain Layer:
├── ValueObjects/AccessCode.php
├── ValueObjects/MessageId.php
├── ValueObjects/MessageAuthor.php
└── Entities/Message.php

Application Layer:
├── DTOs/CreateReportResponseDTO.php
├── DTOs/MessageDTO.php
├── DTOs/ReportStatusDTO.php
├── UseCases/CheckReportStatusUseCase.php
└── UseCases/AddMessageToReportUseCase.php

Infrastructure Layer:
├── Persistence/Eloquent/MessageModel.php

Migrations:
├── 2025_12_10_023622_add_access_code_to_reports_table.php
└── 2025_12_10_023630_create_messages_table.php

Documentación:
├── BIDIRECTIONAL_COMMUNICATION.md
└── COMANDOS_BIDIRECTIONAL.md
```

### Modificados (5 archivos)
```
Domain Layer:
├── Entities/Report.php (+ accessCode, messages, addMessage())
└── Repositories/ReportRepositoryInterface.php (+ findByAccessCode())

Application Layer:
└── UseCases/CreateReportUseCase.php (+ DTO, AccessCode)

Infrastructure Layer:
├── Persistence/Eloquent/ReportModel.php (+ access_code_hash, messages relation)
└── Persistence/Eloquent/EloquentReportRepository.php (+ findByAccessCode, hash logic)
```

---

## 🚀 Próximos Pasos

### 1. **Ejecutar Migraciones**
```powershell
php artisan migrate
```

### 2. **Probar en Tinker**
Ver archivo `COMANDOS_BIDIRECTIONAL.md` para tests completos.

### 3. **Presentation Layer** (Pendiente)
- Crear `ReportController` con endpoints:
  - `POST /api/reports` - Crear reporte
  - `GET /api/reports/{accessCode}` - Consultar estado
  - `POST /api/reports/{accessCode}/messages` - Enviar mensaje

### 4. **Testing Unitario** (Recomendado)
```
tests/Unit/Whistleblowing/
├── Domain/
│   ├── ValueObjects/
│   │   ├── AccessCodeTest.php
│   │   ├── MessageIdTest.php
│   │   └── MessageAuthorTest.php
│   └── Entities/
│       ├── MessageTest.php
│       └── ReportTest.php (actualizar)
└── Application/
    ├── UseCases/
    │   ├── CreateReportUseCaseTest.php (actualizar)
    │   ├── CheckReportStatusUseCaseTest.php
    │   └── AddMessageToReportUseCaseTest.php
```

### 5. **Mejoras de Seguridad (Producción)**
- Encriptar contenido de mensajes con AES-256-GCM
- Rate limiting en endpoints de mensajes
- Logs de auditoría para accesos
- CAPTCHA para prevenir abuse

---

## ✅ Checklist de Verificación

- [x] Migraciones creadas
- [x] Modelos Eloquent actualizados
- [x] Repositorio implementa `findByAccessCode`
- [x] AccessCode hasheado con SHA-256
- [x] DTOs para respuestas estructuradas
- [x] `CreateReportUseCase` retorna AccessCode
- [x] `CheckReportStatusUseCase` implementado
- [x] `AddMessageToReportUseCase` implementado
- [x] Validaciones de seguridad
- [x] Documentación completa
- [ ] Tests unitarios (pendiente)
- [ ] Controllers API (pendiente)
- [ ] Integración frontend (pendiente)

---

## 📊 Métricas de Implementación

- **Archivos creados**: 14
- **Archivos modificados**: 5
- **Líneas de código**: ~1,500
- **Migraciones**: 2
- **Use Cases**: 3 (1 actualizado, 2 nuevos)
- **DTOs**: 3
- **Value Objects**: 3 nuevos
- **Entities**: 1 nueva (Message)

---

**Estado**: ✅ **IMPLEMENTACIÓN COMPLETA**  
**Fecha**: 9 de diciembre de 2025  
**Arquitectura**: DDD (Domain-Driven Design)  
**Próximo paso**: Ejecutar migraciones y probar en Tinker
