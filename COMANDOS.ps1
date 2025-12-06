# ═══════════════════════════════════════════════════════════════════
# COMANDOS POWERSHELL - COPIAR Y PEGAR
# Proyecto: Whistleblower Laravel 11 + DDD
# ═══════════════════════════════════════════════════════════════════

# ┌─────────────────────────────────────────────────────────────────┐
# │ 1. CREAR ESTRUCTURA DE DIRECTORIOS (Ya ejecutado)              │
# └─────────────────────────────────────────────────────────────────┘

# Nota: Esta estructura ya está creada. Si necesitas recrearla:

New-Item -ItemType Directory -Force -Path "src\Whistleblowing\Domain\Entities"
New-Item -ItemType Directory -Force -Path "src\Whistleblowing\Domain\Repositories"
New-Item -ItemType Directory -Force -Path "src\Whistleblowing\Domain\ValueObjects"
New-Item -ItemType Directory -Force -Path "src\Whistleblowing\Application\UseCases"
New-Item -ItemType Directory -Force -Path "src\Whistleblowing\Infrastructure\Persistence\Eloquent"
New-Item -ItemType Directory -Force -Path "src\Whistleblowing\Presentation\Http\Controllers"
New-Item -ItemType Directory -Force -Path "src\Whistleblowing\Presentation\Routes"


# ┌─────────────────────────────────────────────────────────────────┐
# │ 2. REGENERAR AUTOLOAD DE COMPOSER (Ya ejecutado)               │
# └─────────────────────────────────────────────────────────────────┘

composer dump-autoload


# ┌─────────────────────────────────────────────────────────────────┐
# │ 3. EJECUTAR MIGRACIONES                                         │
# └─────────────────────────────────────────────────────────────────┘

# Ver estado de migraciones
php artisan migrate:status

# Ejecutar migraciones pendientes
php artisan migrate

# Si necesitas recrear todas las tablas (¡CUIDADO: Borra datos!)
# php artisan migrate:fresh


# ┌─────────────────────────────────────────────────────────────────┐
# │ 4. INICIAR SERVIDOR DE DESARROLLO                              │
# └─────────────────────────────────────────────────────────────────┘

# Servidor en puerto predeterminado (8000)
php artisan serve

# O especificar puerto manualmente
# php artisan serve --port=8000

# El servidor estará en: http://localhost:8000


# ┌─────────────────────────────────────────────────────────────────┐
# │ 5. VERIFICAR RUTAS REGISTRADAS                                  │
# └─────────────────────────────────────────────────────────────────┘

# Ver solo rutas del módulo Whistleblowing
php artisan route:list --path=whistleblowing

# Ver todas las rutas
php artisan route:list


# ┌─────────────────────────────────────────────────────────────────┐
# │ 6. EJECUTAR PRUEBAS AUTOMATIZADAS                               │
# └─────────────────────────────────────────────────────────────────┘

# Ejecutar script de pruebas
.\test-ddd-api.ps1


# ┌─────────────────────────────────────────────────────────────────┐
# │ 7. PRUEBAS MANUALES CON POWERSHELL                             │
# └─────────────────────────────────────────────────────────────────┘

# NOTA: Asegúrate de tener el servidor corriendo primero

# ──────────────────────────────────────────────────────────────────
# 7.1. Health Check
# ──────────────────────────────────────────────────────────────────

Invoke-RestMethod -Uri "http://localhost:8000/api/v1/whistleblowing/health" -Method Get | ConvertTo-Json


# ──────────────────────────────────────────────────────────────────
# 7.2. Crear Reporte Anónimo
# ──────────────────────────────────────────────────────────────────

$reporteAnonimo = @{
    title = "Reporte de prueba anónimo"
    description = "Esta es una descripción de prueba con más de 20 caracteres para pasar validación"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost:8000/api/v1/whistleblowing/reports" `
    -Method Post `
    -Body $reporteAnonimo `
    -ContentType "application/json" | ConvertTo-Json


# ──────────────────────────────────────────────────────────────────
# 7.3. Crear Reporte con Reporter ID
# ──────────────────────────────────────────────────────────────────

$reporteConID = @{
    title = "Reporte de prueba identificado"
    description = "Este reporte tiene un reporter_id para demostrar reportes no anónimos"
    reporter_id = "user_12345"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost:8000/api/v1/whistleblowing/reports" `
    -Method Post `
    -Body $reporteConID `
    -ContentType "application/json" | ConvertTo-Json


# ──────────────────────────────────────────────────────────────────
# 7.4. Listar Todos los Reportes
# ──────────────────────────────────────────────────────────────────

Invoke-RestMethod -Uri "http://localhost:8000/api/v1/whistleblowing/reports" -Method Get | ConvertTo-Json


# ──────────────────────────────────────────────────────────────────
# 7.5. Obtener un Reporte Específico
# ──────────────────────────────────────────────────────────────────

# Reemplaza {id} con el ID real del reporte
$reportId = "report_xxxxx"  # Obtén este ID de la respuesta anterior
Invoke-RestMethod -Uri "http://localhost:8000/api/v1/whistleblowing/reports/$reportId" -Method Get | ConvertTo-Json


# ──────────────────────────────────────────────────────────────────
# 7.6. Eliminar un Reporte
# ──────────────────────────────────────────────────────────────────

# Reemplaza {id} con el ID real del reporte
$reportId = "report_xxxxx"
Invoke-RestMethod -Uri "http://localhost:8000/api/v1/whistleblowing/reports/$reportId" -Method Delete | ConvertTo-Json


# ┌─────────────────────────────────────────────────────────────────┐
# │ 8. LIMPIAR CACHÉ (Si hay problemas)                            │
# └─────────────────────────────────────────────────────────────────┘

php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear


# ┌─────────────────────────────────────────────────────────────────┐
# │ 9. COMANDOS DE INFORMACIÓN                                      │
# └─────────────────────────────────────────────────────────────────┘

# Ver información general del proyecto
php artisan about

# Ver providers registrados
php artisan list

# Ver estructura de directorios
Get-ChildItem -Path "src" -Recurse -Directory

# Ver archivos PHP creados
Get-ChildItem -Path "src" -Recurse -Filter "*.php"


# ┌─────────────────────────────────────────────────────────────────┐
# │ 10. COMANDOS DE BASE DE DATOS                                   │
# └─────────────────────────────────────────────────────────────────┘

# Ver estado de migraciones
php artisan migrate:status

# Rollback última migración
# php artisan migrate:rollback

# Rollback todas las migraciones
# php artisan migrate:reset

# Recrear todas las tablas (¡CUIDADO!)
# php artisan migrate:fresh

# Recrear y ejecutar seeders
# php artisan migrate:fresh --seed


# ┌─────────────────────────────────────────────────────────────────┐
# │ 11. VERIFICAR ARCHIVOS DE CONFIGURACIÓN                         │
# └─────────────────────────────────────────────────────────────────┘

# Ver composer.json (autoload)
cat composer.json | Select-String -Pattern "autoload" -Context 5

# Ver bootstrap/providers.php
cat bootstrap/providers.php

# Ver variables de entorno
cat .env | Select-String "DB_"


# ┌─────────────────────────────────────────────────────────────────┐
# │ 12. TROUBLESHOOTING                                             │
# └─────────────────────────────────────────────────────────────────┘

# Si hay error "Class not found":
composer dump-autoload
php artisan config:clear

# Si hay error "Target class does not exist":
php artisan cache:clear
php artisan route:clear
php artisan config:clear

# Si hay error de base de datos:
cat .env | Select-String "DB_"
php artisan migrate

# Si el servidor no inicia:
# 1. Verifica que el puerto 8000 esté libre
# 2. Verifica que PHP esté instalado: php --version
# 3. Intenta con otro puerto: php artisan serve --port=8080


# ┌─────────────────────────────────────────────────────────────────┐
# │ 13. COMANDOS ÚTILES PARA DESARROLLO                             │
# └─────────────────────────────────────────────────────────────────┘

# Crear un nuevo controlador
# php artisan make:controller Nombre/DelController

# Crear una nueva migración
# php artisan make:migration nombre_de_la_migracion

# Crear un nuevo modelo
# php artisan make:model NombreModelo

# Crear un test
# php artisan make:test NombreTest

# Ejecutar tests (cuando los crees)
# php artisan test


# ┌─────────────────────────────────────────────────────────────────┐
# │ 14. COMANDOS GIT (Opcional)                                     │
# └─────────────────────────────────────────────────────────────────┘

# Ver el estado
# git status

# Agregar todos los archivos
# git add .

# Commit con mensaje
# git commit -m "feat: Implementar arquitectura DDD para módulo Whistleblowing"

# Push al repositorio
# git push origin main


# ═══════════════════════════════════════════════════════════════════
# FLUJO COMPLETO DE INICIO RÁPIDO
# ═══════════════════════════════════════════════════════════════════

# 1. Ejecutar migraciones
php artisan migrate

# 2. Iniciar servidor (en una terminal)
php artisan serve

# 3. En OTRA terminal, ejecutar pruebas
.\test-ddd-api.ps1

# 4. Verificar rutas
php artisan route:list --path=whistleblowing

# ═══════════════════════════════════════════════════════════════════
# URLS IMPORTANTES
# ═══════════════════════════════════════════════════════════════════

# Servidor local:        http://localhost:8000
# Health Check:          http://localhost:8000/api/v1/whistleblowing/health
# Listar Reportes:       http://localhost:8000/api/v1/whistleblowing/reports
# Crear Reporte (POST):  http://localhost:8000/api/v1/whistleblowing/reports

# ═══════════════════════════════════════════════════════════════════
# ARCHIVOS DE DOCUMENTACIÓN
# ═══════════════════════════════════════════════════════════════════

# INDEX.md                    → Índice de documentación ⭐
# RESUMEN_IMPLEMENTACION.md   → Resumen completo
# QUICK_START.md              → Inicio rápido
# DDD_ARCHITECTURE.md         → Arquitectura detallada
# ARCHITECTURE_DIAGRAM.md     → Diagramas visuales
# RESUMEN_VISUAL.txt          → Resumen visual ASCII
# COMANDOS.ps1                → Este archivo

# ═══════════════════════════════════════════════════════════════════
# NOTAS FINALES
# ═══════════════════════════════════════════════════════════════════

# • El servidor debe estar corriendo para probar la API
# • Los comandos con # al inicio están comentados (no se ejecutan)
# • Reemplaza {id} con IDs reales en los comandos de prueba
# • Para reportes anónimos, no incluyas reporter_id o ponlo en null
# • La validación requiere:
#   - Título: mínimo 5 caracteres
#   - Descripción: mínimo 20 caracteres

# ═══════════════════════════════════════════════════════════════════
# ¡LISTO PARA USAR! 🚀
# ═══════════════════════════════════════════════════════════════════
