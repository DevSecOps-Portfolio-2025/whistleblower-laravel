# ================================================
# WhistleBlower Vault - README
# Guía de instalación y uso
# ================================================

## 🚀 Setup Inicial

### Prerrequisitos
- Docker Desktop instalado y corriendo
- Git (opcional)

### Instalación Rápida

1. **Iniciar Docker Desktop**
   - Asegúrate de que Docker Desktop esté corriendo

2. **Ejecutar el script de setup**
   ```powershell
   .\setup.ps1
   ```

3. **Acceder a la aplicación**
   - Abre tu navegador en: http://localhost:8080

### Instalación Manual

Si prefieres hacerlo paso a paso:

```powershell
# 1. Construir y levantar contenedores
docker-compose up -d --build

# 2. Instalar dependencias
docker-compose exec app composer install

# 3. Generar clave de aplicación
docker-compose exec app php artisan key:generate

# 4. Ejecutar migraciones
docker-compose exec app php artisan migrate

# 5. Acceder a http://localhost:8080
```

## 📦 Servicios Incluidos

- **Web (Nginx)**: Puerto 8080
- **App (PHP-FPM)**: PHP 8.2 Alpine
- **Database (MySQL)**: Puerto 3309
- **Cache (Redis)**: Puerto 6379

## 🔧 Comandos Útiles

### Gestión de Contenedores
```powershell
# Ver estado de contenedores
docker-compose ps

# Ver logs en tiempo real
docker-compose logs -f

# Detener contenedores
docker-compose stop

# Reiniciar contenedores
docker-compose restart

# Eliminar contenedores
docker-compose down

# Reconstruir contenedores
docker-compose up -d --build
```

### Comandos de Laravel
```powershell
# Ejecutar Artisan
docker-compose exec app php artisan

# Crear migración
docker-compose exec app php artisan make:migration create_users_table

# Ejecutar migraciones
docker-compose exec app php artisan migrate

# Crear controlador
docker-compose exec app php artisan make:controller UserController

# Limpiar cache
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Ejecutar tests
docker-compose exec app php artisan test
```

### Composer
```powershell
# Instalar paquete
docker-compose exec app composer require vendor/package

# Actualizar dependencias
docker-compose exec app composer update

# Autoload
docker-compose exec app composer dump-autoload
```

### Acceso a Contenedores
```powershell
# Shell en contenedor PHP
docker-compose exec app sh

# Shell en contenedor MySQL
docker-compose exec db mysql -u wb_user -p

# Shell en contenedor Redis
docker-compose exec redis redis-cli -a redis_secret
```

### Base de Datos
```powershell
# Conectar a MySQL desde host (puerto 3309)
mysql -h 127.0.0.1 -P 3309 -u wb_user -p

# Backup de base de datos
docker-compose exec db mysqldump -u wb_user -p whistleblower > backup.sql

# Restaurar backup
docker-compose exec -T db mysql -u wb_user -p whistleblower < backup.sql
```

## 🔒 Características de Seguridad

- ✅ Usuario no-root (`wb_user`) ejecuta la aplicación
- ✅ Bloqueo de archivos sensibles (.env, .git)
- ✅ Headers de seguridad en Nginx
- ✅ Bloqueo de ejecución PHP en /storage
- ✅ Health checks para MySQL y Redis
- ✅ Red aislada whistleblower-network
- ✅ Contraseñas configurables vía .env

## 📁 Estructura de Archivos Docker

```
whistleblower-laravel/
├── docker/
│   ├── nginx/
│   │   └── default.conf      # Configuración Nginx
│   ├── php/
│   │   └── php.ini            # Configuración PHP
│   └── mysql/
│       └── my.cnf             # Configuración MySQL
├── docker-compose.yml         # Orquestación de servicios
├── Dockerfile                 # Imagen PHP personalizada
├── .dockerignore              # Archivos excluidos del build
├── .env                       # Variables de entorno
└── setup.ps1                  # Script de instalación
```

## 🐛 Solución de Problemas

### Docker Desktop no inicia
```powershell
# Reiniciar servicio de Docker
Restart-Service docker
```

### Puerto ocupado
Si el puerto 8080, 3309 o 6379 está ocupado, edita `docker-compose.yml`:
```yaml
ports:
  - "8081:80"  # Cambiar 8080 a 8081
```

### Permisos en storage (Error 500 - Permission Denied)
**Este es el problema más común en Windows + Docker**

**Solución rápida:**
```powershell
# Opción 1: Ejecutar script automatizado
.\fix-permissions.ps1

# Opción 2: Comando manual
icacls storage /grant Todos:F /T
icacls bootstrap\cache /grant Todos:F /T
docker-compose exec -T app php artisan cache:clear
docker-compose restart app
```

**Explicación:** Windows no respeta los permisos de Linux en volúmenes Docker. Debes dar permisos desde Windows con `icacls`.

### Reinstalar dependencias
```powershell
docker-compose exec app rm -rf vendor
docker-compose exec app composer install
```

### Base de datos no conecta
1. Verifica que MySQL esté corriendo: `docker-compose ps`
2. Verifica las credenciales en `.env`
3. Espera a que MySQL termine de inicializar (primera vez puede tomar 30-60 segundos)

## 🔄 Actualizar Proyecto

```powershell
# Detener contenedores
docker-compose down

# Pull cambios de Git
git pull origin main

# Reconstruir y levantar
docker-compose up -d --build

# Actualizar dependencias
docker-compose exec app composer install

# Ejecutar migraciones
docker-compose exec app php artisan migrate
```

## 📞 Soporte

Para reportar problemas o sugerencias, contacta al equipo de DevOps.
