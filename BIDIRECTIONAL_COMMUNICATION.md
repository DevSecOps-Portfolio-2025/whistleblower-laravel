# Extensión del Dominio Whistleblowing - Comunicación Bidireccional

## 📋 Resumen de Implementación

Se ha extendido el dominio **Whistleblowing** para soportar comunicación bidireccional segura entre denunciantes e investigadores mediante un sistema de **AccessCode** y mensajería.

---

## 🆕 Nuevos Componentes

### 1. Value Objects

#### **AccessCode** (`src/Whistleblowing/Domain/ValueObjects/AccessCode.php`)
- **Propósito**: Código de acceso seguro para recuperar reportes anónimamente
- **Características**:
  - Genera strings alfanuméricos de 16 caracteres
  - Usa `random_int()` criptográficamente seguro
  - Validación estricta de formato
  - Comparación segura contra timing attacks (`hash_equals`)
  - Inmutable

**Métodos principales:**
```php
AccessCode::generate(): self                    // Generar código aleatorio
AccessCode::fromString(string $code): self      // Desde string existente
$code->value(): string                          // Obtener valor
$code->equals(AccessCode $other): bool          // Comparación simple
$code->equalsSecure(AccessCode $other): bool    // Comparación segura
```

#### **MessageId** (`src/Whistleblowing/Domain/ValueObjects/MessageId.php`)
- **Propósito**: Identificador único para mensajes (UUID v4)
- **Características**: Similar a `SubmissionId`, inmutable, validación UUID

#### **MessageAuthor** (`src/Whistleblowing/Domain/ValueObjects/MessageAuthor.php`)
- **Propósito**: Enum-like para el autor del mensaje
- **Valores permitidos**: `'reporter'` | `'investigator'`
- **Características**:
  - Factory methods: `MessageAuthor::reporter()`, `MessageAuthor::investigator()`
  - Métodos de verificación: `isReporter()`, `isInvestigator()`
  - Validación estricta

---

### 2. Entidad: Message

**Archivo:** `src/Whistleblowing/Domain/Entities/Message.php`

**Propiedades:**
- `MessageId $id` - Identificador único
- `string $content` - Contenido del mensaje (texto plano para MVP)
- `MessageAuthor $author` - Autor (reporter/investigator)
- `DateTimeImmutable $createdAt` - Fecha de creación

**Factory Methods:**
```php
Message::create(string $content, MessageAuthor $author): self
Message::fromReporter(string $content): self
Message::fromInvestigator(string $content): self
```

**Métodos principales:**
```php
$message->getId(): MessageId
$message->getContent(): string
$message->getAuthor(): MessageAuthor
$message->getCreatedAt(): DateTimeImmutable
$message->isFromReporter(): bool
$message->isFromInvestigator(): bool
$message->getContentPreview(int $length = 100): string
```

**Nota de seguridad:**
> El contenido se almacena en texto plano para simplificar el MVP. Para producción, considerar encriptación simétrica del contenido usando una clave derivada del AccessCode.

---

## 🔄 Modificaciones a Componentes Existentes

### 1. Entidad Report

**Archivo:** `src/Whistleblowing/Domain/Entities/Report.php`

**Nuevas propiedades:**
```php
private AccessCode $accessCode;  // Código de acceso único
private array $messages = [];    // Colección de Message
```

**Constructor actualizado:**
```php
public function __construct(
    string $id,
    string $title,
    string $description,
    AccessCode $accessCode,        // ⬅️ NUEVO
    string $status = 'pending',
    ?string $reporterId = null,
    array $messages = [],          // ⬅️ NUEVO
    ?DateTimeImmutable $createdAt = null,
    ?DateTimeImmutable $updatedAt = null
)
```

**Nuevo Factory Method:**
```php
// Genera automáticamente el AccessCode
Report::create(
    string $id,
    string $title,
    string $description,
    ?string $reporterId = null
): self
```

**Nuevos métodos:**
```php
// Getters
$report->getAccessCode(): AccessCode
$report->getMessages(): array              // Message[]
$report->getMessageCount(): int
$report->getLastMessage(): ?Message

// Métodos de negocio
$report->addMessage(Message $message): void
$report->hasAccessCode(AccessCode $code): bool
$report->hasMessages(): bool
```

---

### 2. ReportRepositoryInterface

**Archivo:** `src/Whistleblowing/Domain/Repositories/ReportRepositoryInterface.php`

**Nuevo método:**
```php
/**
 * Encontrar un reporte por su AccessCode
 */
public function findByAccessCode(AccessCode $code): ?Report;
```

Este método permitirá a los denunciantes recuperar su reporte usando solo el código de acceso, sin autenticación tradicional.

---

## 🔐 Consideraciones de Seguridad

### AccessCode
1. ✅ Generación criptográficamente segura (`random_int`)
2. ✅ 16 caracteres = ~95 bits de entropía (62^16)
3. ✅ Comparación segura contra timing attacks
4. ✅ Inmutable y validado

### Mensajes
- **MVP**: Contenido en texto plano
- **Producción recomendada**: 
  - Encriptar contenido con AES-256-GCM
  - Derivar clave de encriptación del AccessCode usando PBKDF2/Argon2
  - Almacenar IV/nonce por mensaje

---

## 📊 Diagrama de Relaciones

```
Report (1) ──────────── (1) AccessCode
   │
   │ (1:N)
   │
   └──> Message (N)
            │
            └──> MessageAuthor (enum)
            └──> MessageId (UUID)
```

---

## 🎯 Casos de Uso Habilitados

### 1. Crear Reporte con AccessCode
```php
$report = Report::create(
    id: Uuid::uuid4()->toString(),
    title: 'Fraude detectado',
    description: 'Detalles del fraude...',
    reporterId: null  // Anónimo
);

// El AccessCode se genera automáticamente
$accessCode = $report->getAccessCode();
// Ejemplo: "a3B5kL9mP2qR7sT4"
```

### 2. Recuperar Reporte por AccessCode
```php
$code = AccessCode::fromString('a3B5kL9mP2qR7sT4');
$report = $repository->findByAccessCode($code);

if ($report && $report->hasAccessCode($code)) {
    // Acceso concedido
}
```

### 3. Agregar Mensajes
```php
// Denunciante envía mensaje
$message1 = Message::fromReporter('Tengo evidencia adicional');
$report->addMessage($message1);

// Investigador responde
$message2 = Message::fromInvestigator('¿Puede compartir los documentos?');
$report->addMessage($message2);

$repository->update($report);
```

---

## ✅ Checklist de Implementación

- [x] Value Object: `AccessCode`
  - [x] Generación segura
  - [x] Validación
  - [x] Comparación segura
- [x] Value Object: `MessageId`
- [x] Value Object: `MessageAuthor`
- [x] Entidad: `Message`
  - [x] Propiedades
  - [x] Factory methods
  - [x] Métodos de negocio
- [x] Entidad `Report` actualizada
  - [x] Propiedad `accessCode`
  - [x] Colección `messages`
  - [x] Método `addMessage()`
  - [x] Factory method con AccessCode generado
- [x] Repositorio actualizado
  - [x] Método `findByAccessCode()`

---

## 📝 Próximos Pasos (Capas Superiores)

### Application Layer
1. **Use Cases**:
   - `RetrieveReportByAccessCodeUseCase`
   - `AddMessageToReportUseCase`
   - `GetReportMessagesUseCase`

### Infrastructure Layer
2. **Eloquent Models**:
   - Actualizar `ReportEloquentModel` con campo `access_code`
   - Crear `MessageEloquentModel`
   - Relación `hasMany` para mensajes

3. **Migrations**:
   - Agregar columna `access_code` a tabla `reports`
   - Crear tabla `messages` (id, report_id, content, author, created_at)

4. **Repository Implementation**:
   - Implementar `findByAccessCode()` en `EloquentReportRepository`
   - Mapear mensajes entre entidad y Eloquent

### Presentation Layer
5. **API Endpoints**:
   - `GET /api/reports/{accessCode}` - Recuperar reporte
   - `POST /api/reports/{accessCode}/messages` - Enviar mensaje
   - `GET /api/reports/{accessCode}/messages` - Listar mensajes

---

## 🏗️ Arquitectura DDD Respetada

✅ **Domain Layer** (esta implementación):
- Entities puras sin dependencias de framework
- Value Objects inmutables
- Lógica de negocio en entidades
- Interfaces de repositorio

✅ **Principios aplicados**:
- **Ubiquitous Language**: AccessCode, Message, MessageAuthor
- **Encapsulation**: Propiedades privadas, getters
- **Immutability**: Value Objects
- **Type Safety**: Strict types, validaciones
- **Factory Methods**: Creación controlada

---

## 🧪 Testing Sugerido

```php
// AccessCodeTest.php
- testGenerateCreatesValidCode()
- testFromStringWithValidCode()
- testFromStringThrowsExceptionForInvalidCode()
- testEqualsReturnsTrueForSameCode()
- testEqualsSecureProtectsAgainstTimingAttacks()

// MessageTest.php
- testCreateWithReporter()
- testCreateWithInvestigator()
- testFactoryMethodsWork()
- testIsFromReporter()
- testGetContentPreview()

// ReportTest.php
- testCreateGeneratesAccessCode()
- testAddMessage()
- testGetMessageCount()
- testHasAccessCode()
- testGetLastMessage()
```

---

**Implementado por:** GitHub Copilot  
**Fecha:** 9 de diciembre de 2025  
**Arquitectura:** DDD (Domain-Driven Design)  
**Estándar:** PHP 8.x strict types
