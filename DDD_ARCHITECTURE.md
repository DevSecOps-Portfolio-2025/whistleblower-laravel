# Arquitectura DDD - Módulo Whistleblowing

## 📋 Resumen de la Implementación

Este proyecto Laravel 11 ha sido reestructurado siguiendo los principios de **Domain-Driven Design (DDD)**.

## 🏗️ Estructura de Directorios

```
src/Whistleblowing/
├── Domain/                           # Capa de Dominio (Lógica de Negocio Pura)
│   ├── Entities/                     # Entidades ricas del dominio
│   │   └── Report.php               # Entidad Report (POJO)
│   ├── Repositories/                 # Interfaces de repositorios
│   │   └── ReportRepositoryInterface.php
│   └── ValueObjects/                 # Objetos de valor inmutables
│       └── ReportStatus.php
│
├── Application/                      # Capa de Aplicación (Casos de Uso)
│   └── UseCases/
│       ├── CreateReportUseCase.php
│       └── GetReportByIdUseCase.php
│
├── Infrastructure/                   # Capa de Infraestructura (Implementaciones técnicas)
│   └── Persistence/
│       └── Eloquent/
│           ├── ReportModel.php      # Modelo Eloquent
│           └── EloquentReportRepository.php  # Implementación concreta
│
└── Presentation/                     # Capa de Presentación (API/UI)
    ├── Http/
    │   └── Controllers/
    │       └── WhistleblowerController.php
    └── Routes/
        └── api.php                   # Rutas del módulo
```

## 📦 Configuración Realizada

### 1. Composer Autoload

**Archivo:** `composer.json`

```json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "Database\\Factories\\": "database/factories/",
        "Database\\Seeders\\": "database/seeders/",
        "Src\\": "src/"
    }
}
```

**Comando ejecutado:**
```powershell
composer dump-autoload
```

### 2. Service Provider

**Archivo:** `app/Providers/WhistleblowingServiceProvider.php`

- ✅ Registra el binding del repositorio (Interfaz → Implementación)
- ✅ Carga las rutas desde `src/Whistleblowing/Presentation/Routes/api.php`
- ✅ Prefix automático: `api/v1`

**Registrado en:** `bootstrap/providers.php`

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\WhistleblowingServiceProvider::class,
];
```

## 🎯 Endpoints API Disponibles

Base URL: `http://localhost/api/v1`

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/whistleblowing/health` | Health check del módulo |
| GET | `/whistleblowing/reports` | Listar todos los reportes |
| POST | `/whistleblowing/reports` | Crear un nuevo reporte |
| GET | `/whistleblowing/reports/{id}` | Obtener un reporte específico |
| PUT | `/whistleblowing/reports/{id}` | Actualizar un reporte |
| DELETE | `/whistleblowing/reports/{id}` | Eliminar un reporte |

### Ejemplo de Request (POST):

```json
POST /api/v1/whistleblowing/reports
Content-Type: application/json

{
    "title": "Reporte de ejemplo",
    "description": "Esta es una descripción detallada del reporte que debe tener al menos 20 caracteres",
    "reporter_id": "user_123"  // Opcional - si se omite, será anónimo
}
```

## 🗄️ Base de Datos

**Migración creada:** `database/migrations/2024_01_01_000000_create_reports_table.php`

**Ejecutar migración:**
```powershell
php artisan migrate
```

**Estructura de la tabla `reports`:**
- `id` (string, PK)
- `title` (string)
- `description` (text)
- `status` (string) - valores: pending, under_review, reviewed, resolved, rejected
- `reporter_id` (string, nullable) - NULL = reporte anónimo
- `created_at`, `updated_at` (timestamps)

## 🧪 Probar la Implementación

### 1. Iniciar el servidor:
```powershell
php artisan serve
```

### 2. Probar el health check:
```powershell
curl http://localhost:8000/api/v1/whistleblowing/health
```

### 3. Crear un reporte:
```powershell
curl -X POST http://localhost:8000/api/v1/whistleblowing/reports \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Reporte de prueba DDD",
    "description": "Esta es una descripción de prueba para validar la arquitectura DDD implementada",
    "reporter_id": null
  }'
```

## 📐 Principios DDD Aplicados

### 1. **Separación de Capas**
- ✅ Domain: Lógica de negocio pura (sin dependencias de framework)
- ✅ Application: Casos de uso y orquestación
- ✅ Infrastructure: Implementaciones técnicas (Eloquent, APIs, etc.)
- ✅ Presentation: Controllers, Requests, Resources

### 2. **Entidades Ricas**
- La clase `Report` en el dominio contiene lógica de negocio
- Métodos como `markAsReviewed()`, `isAnonymous()`
- No depende de Eloquent

### 3. **Value Objects**
- `ReportStatus` encapsula el concepto de estado
- Inmutable y con validación de negocio
- Factory methods para cada estado

### 4. **Repository Pattern**
- Interfaz en el dominio define el contrato
- Implementación concreta en Infrastructure
- Dependency Injection en el Service Provider

### 5. **Use Cases**
- Cada caso de uso tiene una responsabilidad única
- `CreateReportUseCase`: Crear reportes
- `GetReportByIdUseCase`: Consultar reportes
- Fácil de testear de forma unitaria

## 🔄 Flujo de una Request

```
HTTP Request
    ↓
WhistleblowerController (Presentation)
    ↓
CreateReportUseCase (Application)
    ↓
ReportRepositoryInterface (Domain)
    ↓
EloquentReportRepository (Infrastructure)
    ↓
ReportModel (Infrastructure/Eloquent)
    ↓
Database
```

## 📝 Comandos PowerShell Utilizados

```powershell
# 1. Crear estructura de directorios
New-Item -ItemType Directory -Force -Path "src\Whistleblowing\Domain\Entities"
New-Item -ItemType Directory -Force -Path "src\Whistleblowing\Domain\Repositories"
New-Item -ItemType Directory -Force -Path "src\Whistleblowing\Domain\ValueObjects"
New-Item -ItemType Directory -Force -Path "src\Whistleblowing\Application\UseCases"
New-Item -ItemType Directory -Force -Path "src\Whistleblowing\Infrastructure\Persistence\Eloquent"
New-Item -ItemType Directory -Force -Path "src\Whistleblowing\Presentation\Http\Controllers"
New-Item -ItemType Directory -Force -Path "src\Whistleblowing\Presentation\Routes"

# 2. Regenerar autoload de Composer
composer dump-autoload

# 3. Ejecutar migraciones
php artisan migrate

# 4. Iniciar servidor de desarrollo
php artisan serve
```

## 🚀 Próximos Pasos

1. **Testing:**
   - Unit tests para entidades del dominio
   - Tests de casos de uso
   - Integration tests para repositorios

2. **Eventos de Dominio:**
   - Implementar `ReportCreated`, `ReportReviewed`, etc.
   - Event Dispatcher

3. **DTOs (Data Transfer Objects):**
   - Para transferir datos entre capas
   - Validación más robusta

4. **Especificaciones (Specifications):**
   - Para queries complejas
   - Lógica de filtrado reutilizable

5. **CQRS (opcional):**
   - Separar comandos de queries
   - Query models optimizados

## 📚 Recursos

- [Domain-Driven Design - Eric Evans](https://www.domainlanguage.com/ddd/)
- [Laravel Best Practices](https://github.com/alexeymezenin/laravel-best-practices)
- [Implementing DDD in PHP](https://carlosbuenosvinos.com/domain-driven-design-in-php/)

---

**Autor:** Arquitecto de Software Senior en PHP  
**Fecha:** Diciembre 2025  
**Framework:** Laravel 11  
**Arquitectura:** Domain-Driven Design (DDD)
