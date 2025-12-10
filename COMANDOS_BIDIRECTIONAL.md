# Comandos - Implementación de Comunicación Bidireccional

## 🗄️ Migraciones de Base de Datos

### 1. Ejecutar las migraciones

**🐳 En Docker:**
```powershell
# Ejecutar migraciones (agregará access_code_hash a reports y creará tabla messages)
docker-compose exec app php artisan migrate

# Si hay problemas, hacer rollback y volver a migrar
docker-compose exec app php artisan migrate:rollback
docker-compose exec app php artisan migrate
```

**💻 Local (sin Docker):**
```powershell
php artisan migrate
php artisan migrate:rollback
php artisan migrate
```

### 2. Verificar las tablas creadas

**🐳 En Docker:**
```powershell
# Conectar a MySQL y verificar estructura
docker-compose exec app php artisan tinker

# En tinker:
DB::select('DESCRIBE reports');
DB::select('DESCRIBE messages');
exit
```

**💻 Local:**
```powershell
php artisan tinker
# ... mismo código ...
```

---

## 🧪 Pruebas en Tinker

### Test 1: Crear un reporte con AccessCode

```php
php artisan tinker

// Importar clases
use Src\Whistleblowing\Application\UseCases\CreateReportUseCase;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\EloquentReportRepository;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\ReportModel;

// Crear dependencias
$repository = new EloquentReportRepository(new ReportModel());
$useCase = new CreateReportUseCase($repository);

// Ejecutar caso de uso
$response = $useCase->execute([
    'title' => 'Fraude detectado en compras',
    'description' => 'Se detectaron irregularidades en el proceso de adquisiciones del departamento X. Hay facturas duplicadas y proveedores fantasma.',
    'reporterId' => null  // Anónimo
]);

// Ver respuesta (incluye AccessCode)
print_r($response->toArray());

// GUARDAR EL ACCESS_CODE para las siguientes pruebas
// Ejemplo: "a3B5kL9mP2qR7sT4"
```

### Test 2: Consultar estado del reporte con AccessCode

```php
php artisan tinker

use Src\Whistleblowing\Application\UseCases\CheckReportStatusUseCase;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\EloquentReportRepository;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\ReportModel;

$repository = new EloquentReportRepository(new ReportModel());
$useCase = new CheckReportStatusUseCase($repository);

// Reemplazar con el AccessCode del Test 1
$accessCode = 'a3B5kL9mP2qR7sT4';  // ⬅️ PONER TU CÓDIGO AQUÍ

$status = $useCase->execute($accessCode);
print_r($status->toArray());
```

### Test 3: Agregar mensaje del denunciante

```php
php artisan tinker

use Src\Whistleblowing\Application\UseCases\AddMessageToReportUseCase;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\EloquentReportRepository;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\ReportModel;

$repository = new EloquentReportRepository(new ReportModel());
$useCase = new AddMessageToReportUseCase($repository);

$accessCode = 'a3B5kL9mP2qR7sT4';  // ⬅️ TU CÓDIGO

$result = $useCase->execute(
    accessCodeString: $accessCode,
    content: 'Tengo evidencia fotográfica que puedo compartir. ¿Cómo procedo?',
    author: 'reporter'
);

print_r($result);
```

### Test 4: Respuesta del investigador

```php
php artisan tinker

use Src\Whistleblowing\Application\UseCases\AddMessageToReportUseCase;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\EloquentReportRepository;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\ReportModel;

$repository = new EloquentReportRepository(new ReportModel());
$useCase = new AddMessageToReportUseCase($repository);

$accessCode = 'a3B5kL9mP2qR7sT4';  // ⬅️ TU CÓDIGO

$result = $useCase->execute(
    accessCodeString: $accessCode,
    content: 'Hemos iniciado la investigación. Por favor, suba las fotos a través del portal seguro. Ref: INV-2025-001',
    author: 'investigator'
);

print_r($result);
```

### Test 5: Ver conversación completa

```php
php artisan tinker

use Src\Whistleblowing\Application\UseCases\CheckReportStatusUseCase;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\EloquentReportRepository;
use Src\Whistleblowing\Infrastructure\Persistence\Eloquent\ReportModel;

$repository = new EloquentReportRepository(new ReportModel());
$useCase = new CheckReportStatusUseCase($repository);

$accessCode = 'a3B5kL9mP2qR7sT4';  // ⬅️ TU CÓDIGO

$status = $useCase->execute($accessCode);

// Ver solo los mensajes
foreach ($status->messages as $msg) {
    echo "\n[{$msg->author}] ({$msg->createdAt}):\n";
    echo "{$msg->content}\n";
}
```

---

## 🔍 Verificación en Base de Datos

### Ver reportes con AccessCode hasheado

```sql
SELECT 
    id,
    title,
    status,
    access_code_hash,
    created_at
FROM reports;
```

### Ver mensajes de un reporte

```sql
SELECT 
    m.id,
    m.author,
    m.content,
    m.created_at,
    r.title as report_title
FROM messages m
JOIN reports r ON m.report_id = r.id
ORDER BY m.created_at ASC;
```

### Verificar hash del AccessCode

```php
php artisan tinker

// Hash de un código específico
$code = 'a3B5kL9mP2qR7sT4';
$hash = hash('sha256', $code);
echo $hash;

// Buscar en BD
DB::table('reports')->where('access_code_hash', $hash)->get();
```

---

## 🐛 Comandos de Debug

### Ver logs de Laravel

```powershell
# Windows PowerShell
Get-Content storage/logs/laravel.log -Tail 50 -Wait

# Ver último error
Get-Content storage/logs/laravel.log -Tail 100 | Select-String "ERROR"
```

### Limpiar caché

```powershell
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Verificar configuración

```powershell
php artisan config:show database
php artisan about
```

---

## 🧹 Limpieza de Testing

### Limpiar datos de prueba

```powershell
php artisan tinker

# Eliminar todos los mensajes
DB::table('messages')->truncate();

# Eliminar todos los reportes
DB::table('reports')->truncate();
```

### Rollback de migraciones

```powershell
# Rollback de las últimas 2 migraciones
php artisan migrate:rollback --step=2

# Ver estado de migraciones
php artisan migrate:status

# Recrear todo
php artisan migrate:fresh
```

---

## 📊 Estadísticas

### Contar registros

```php
php artisan tinker

echo "Reportes: " . DB::table('reports')->count() . "\n";
echo "Mensajes: " . DB::table('messages')->count() . "\n";

// Mensajes por autor
DB::table('messages')
    ->select('author', DB::raw('count(*) as total'))
    ->groupBy('author')
    ->get();
```

---

## 🔐 Seguridad - Verificación de Hash

### Comparar AccessCode con hash almacenado

```php
php artisan tinker

use Src\Whistleblowing\Domain\ValueObjects\AccessCode;

$plainCode = 'a3B5kL9mP2qR7sT4';
$storedHash = 'abc123...'; // Hash desde BD

// Verificar coincidencia
$calculatedHash = hash('sha256', $plainCode);
$matches = hash_equals($storedHash, $calculatedHash);

echo $matches ? "✅ Código válido" : "❌ Código inválido";
```

---

## 🚀 Testing Automatizado

### Ejecutar tests (cuando estén creados)

```powershell
# Todos los tests
php artisan test

# Tests específicos del dominio
php artisan test --filter=Whistleblowing

# Con coverage
php artisan test --coverage
```

---

## 📝 Notas Importantes

1. **AccessCode en texto plano**: Solo se muestra al crear el reporte. Después se hashea.
2. **SHA-256**: Se usa para hashear el AccessCode (64 caracteres hex).
3. **Mensajes**: Almacenados en texto plano (MVP). Considerar encriptación para producción.
4. **Relaciones**: Cascade delete - al borrar reporte se borran mensajes.
5. **Índices**: Optimizados para búsquedas por `access_code_hash` y `report_id`.

---

## 🔄 Flujo Completo de Prueba

```powershell
# 1. Migrar
php artisan migrate

# 2. Crear reporte y guardar AccessCode
php artisan tinker
# ... código del Test 1 ...
# AccessCode: XYZ123...

# 3. Consultar estado (debe estar vacío de mensajes)
# ... código del Test 2 ...

# 4. Agregar mensaje del reporter
# ... código del Test 3 ...

# 5. Agregar respuesta del investigator
# ... código del Test 4 ...

# 6. Ver conversación completa
# ... código del Test 5 ...

# 7. Verificar en BD
php artisan tinker
DB::table('messages')->count();
```
