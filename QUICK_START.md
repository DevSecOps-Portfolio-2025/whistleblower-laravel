# 🚀 Guía de Inicio Rápido - DDD Laravel 11

## Comandos PowerShell Ejecutados

### 1. Crear Estructura de Directorios DDD

```powershell
# Navegar al proyecto
cd e:\proyectos\whistleblower-laravel

# Crear estructura del módulo Whistleblowing
New-Item -ItemType Directory -Force -Path "src\Whistleblowing\Domain\Entities"
New-Item -ItemType Directory -Force -Path "src\Whistleblowing\Domain\Repositories"
New-Item -ItemType Directory -Force -Path "src\Whistleblowing\Domain\ValueObjects"
New-Item -ItemType Directory -Force -Path "src\Whistleblowing\Application\UseCases"
New-Item -ItemType Directory -Force -Path "src\Whistleblowing\Infrastructure\Persistence\Eloquent"
New-Item -ItemType Directory -Force -Path "src\Whistleblowing\Presentation\Http\Controllers"
New-Item -ItemType Directory -Force -Path "src\Whistleblowing\Presentation\Routes"
```

### 2. Configurar Composer

El namespace `Src\\` ha sido agregado al `composer.json`:

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

Regenerar autoload:

```powershell
composer dump-autoload
```

### 3. Ejecutar Migraciones

```powershell
# Ejecutar migraciones
php artisan migrate

# Ver el estado de las migraciones
php artisan migrate:status
```

### 4. Iniciar el Servidor

```powershell
# Opción 1: Servidor de desarrollo de Laravel
php artisan serve

# Opción 2: Con puerto específico
php artisan serve --port=8000

# El servidor estará disponible en: http://localhost:8000
```

### 5. Probar la API

```powershell
# Ejecutar el script de pruebas automatizado
.\test-ddd-api.ps1

# O probar manualmente con curl/Invoke-RestMethod
```

## 📝 Verificar la Configuración

### Ver las rutas registradas:

```powershell
php artisan route:list --path=whistleblowing
```

### Ver los providers registrados:

```powershell
php artisan about
```

### Limpiar caché de configuración:

```powershell
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

## 🧪 Pruebas Manuales

### Health Check:

```powershell
Invoke-RestMethod -Uri "http://localhost:8000/api/v1/whistleblowing/health" -Method Get | ConvertTo-Json
```

### Crear un Reporte:

```powershell
$body = @{
    title = "Reporte de prueba"
    description = "Esta es una descripción de al menos 20 caracteres para el reporte"
    reporter_id = "user_123"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost:8000/api/v1/whistleblowing/reports" `
    -Method Post `
    -Body $body `
    -ContentType "application/json" | ConvertTo-Json
```

### Listar Reportes:

```powershell
Invoke-RestMethod -Uri "http://localhost:8000/api/v1/whistleblowing/reports" -Method Get | ConvertTo-Json
```

### Obtener un Reporte:

```powershell
$reportId = "report_xxxxx"
Invoke-RestMethod -Uri "http://localhost:8000/api/v1/whistleblowing/reports/$reportId" -Method Get | ConvertTo-Json
```

## 📦 Archivos Creados

### Domain Layer:
- ✅ `src/Whistleblowing/Domain/Entities/Report.php`
- ✅ `src/Whistleblowing/Domain/ValueObjects/ReportStatus.php`
- ✅ `src/Whistleblowing/Domain/Repositories/ReportRepositoryInterface.php`

### Application Layer:
- ✅ `src/Whistleblowing/Application/UseCases/CreateReportUseCase.php`
- ✅ `src/Whistleblowing/Application/UseCases/GetReportByIdUseCase.php`

### Infrastructure Layer:
- ✅ `src/Whistleblowing/Infrastructure/Persistence/Eloquent/ReportModel.php`
- ✅ `src/Whistleblowing/Infrastructure/Persistence/Eloquent/EloquentReportRepository.php`

### Presentation Layer:
- ✅ `src/Whistleblowing/Presentation/Http/Controllers/WhistleblowerController.php`
- ✅ `src/Whistleblowing/Presentation/Routes/api.php`

### Configuration:
- ✅ `app/Providers/WhistleblowingServiceProvider.php`
- ✅ `bootstrap/providers.php` (actualizado)
- ✅ `composer.json` (actualizado)

### Database:
- ✅ `database/migrations/2024_01_01_000000_create_reports_table.php`

### Documentation:
- ✅ `DDD_ARCHITECTURE.md`
- ✅ `QUICK_START.md` (este archivo)
- ✅ `test-ddd-api.ps1`

## 🎯 Endpoints Disponibles

| Método | URL | Descripción |
|--------|-----|-------------|
| GET | `/api/v1/whistleblowing/health` | Health check |
| GET | `/api/v1/whistleblowing/reports` | Listar reportes |
| POST | `/api/v1/whistleblowing/reports` | Crear reporte |
| GET | `/api/v1/whistleblowing/reports/{id}` | Ver reporte |
| PUT | `/api/v1/whistleblowing/reports/{id}` | Actualizar reporte |
| DELETE | `/api/v1/whistleblowing/reports/{id}` | Eliminar reporte |

## 🔧 Troubleshooting

### Error: "Class not found"
```powershell
composer dump-autoload
php artisan config:clear
```

### Error: "Target class does not exist"
```powershell
# Verificar que el provider esté registrado
cat bootstrap/providers.php

# Limpiar caché
php artisan cache:clear
php artisan config:clear
```

### Error de Base de Datos
```powershell
# Verificar configuración
cat .env | Select-String "DB_"

# Ejecutar migraciones
php artisan migrate:fresh
```

## 📚 Documentación Adicional

- Ver `DDD_ARCHITECTURE.md` para detalles completos de la arquitectura
- Ver `SOLUCION_IMPLEMENTADA.md` para el contexto original del proyecto

---

**¡La arquitectura DDD está lista para usar! 🎉**
