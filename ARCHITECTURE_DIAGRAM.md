# 🏛️ Diagrama de Arquitectura DDD - Whistleblowing

## Estructura de Capas

```
┌─────────────────────────────────────────────────────────────────┐
│                     PRESENTATION LAYER                          │
│  (HTTP Controllers, Routes, Requests, Resources)                │
│                                                                 │
│  • WhistleblowerController.php                                  │
│  • api.php (rutas)                                              │
│                                                                 │
│  Responsabilidad:                                               │
│  - Manejar requests HTTP                                        │
│  - Validación de entrada                                        │
│  - Formatear respuestas                                         │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ↓
┌─────────────────────────────────────────────────────────────────┐
│                    APPLICATION LAYER                            │
│  (Use Cases, Application Services)                              │
│                                                                 │
│  • CreateReportUseCase.php                                      │
│  • GetReportByIdUseCase.php                                     │
│                                                                 │
│  Responsabilidad:                                               │
│  - Orquestar lógica de negocio                                  │
│  - Coordinar entre capas                                        │
│  - Implementar casos de uso                                     │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ↓
┌─────────────────────────────────────────────────────────────────┐
│                      DOMAIN LAYER                               │
│  (Entities, Value Objects, Repository Interfaces)               │
│                                                                 │
│  Entities:                                                      │
│  • Report.php (entidad rica)                                    │
│                                                                 │
│  Value Objects:                                                 │
│  • ReportStatus.php                                             │
│                                                                 │
│  Repositories (interfaces):                                     │
│  • ReportRepositoryInterface.php                                │
│                                                                 │
│  Responsabilidad:                                               │
│  - Lógica de negocio PURA                                       │
│  - Reglas del dominio                                           │
│  - Sin dependencias externas                                    │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ↓
┌─────────────────────────────────────────────────────────────────┐
│                   INFRASTRUCTURE LAYER                          │
│  (Eloquent Models, Repositories, External Services)             │
│                                                                 │
│  • ReportModel.php (Eloquent)                                   │
│  • EloquentReportRepository.php                                 │
│                                                                 │
│  Responsabilidad:                                               │
│  - Implementaciones técnicas                                    │
│  - Persistencia (Base de datos)                                 │
│  - APIs externas                                                │
│  - Infraestructura técnica                                      │
└─────────────────────────────────────────────────────────────────┘
```

## Flujo de una Request (POST /api/v1/whistleblowing/reports)

```
   HTTP Request
   POST /api/v1/whistleblowing/reports
   Body: { title: "...", description: "..." }
        │
        ↓
┌───────────────────────────────────────┐
│  1. WhistleblowerController           │
│     (Presentation Layer)              │
│                                       │
│  - Valida request                     │
│  - Extrae datos                       │
└───────────────┬───────────────────────┘
                │
                ↓
┌───────────────────────────────────────┐
│  2. CreateReportUseCase               │
│     (Application Layer)               │
│                                       │
│  - Valida reglas de negocio           │
│  - Crea entidad Report                │
└───────────────┬───────────────────────┘
                │
                ↓
┌───────────────────────────────────────┐
│  3. Report (Entity)                   │
│     (Domain Layer)                    │
│                                       │
│  - Aplica lógica del dominio          │
│  - Mantiene estado consistente        │
└───────────────┬───────────────────────┘
                │
                ↓
┌───────────────────────────────────────┐
│  4. ReportRepositoryInterface         │
│     (Domain Layer - Interface)        │
│                                       │
│  - Define contrato de persistencia    │
└───────────────┬───────────────────────┘
                │
                ↓
┌───────────────────────────────────────┐
│  5. EloquentReportRepository          │
│     (Infrastructure Layer)            │
│                                       │
│  - Implementa interface               │
│  - Traduce Entity → Model             │
└───────────────┬───────────────────────┘
                │
                ↓
┌───────────────────────────────────────┐
│  6. ReportModel (Eloquent)            │
│     (Infrastructure Layer)            │
│                                       │
│  - Persiste en base de datos          │
└───────────────┬───────────────────────┘
                │
                ↓
         MySQL Database
         (reports table)
```

## Principios de Dependencia

```
┌─────────────────────────────────────────────┐
│         DIRECCIÓN DE DEPENDENCIAS           │
└─────────────────────────────────────────────┘

    Presentation Layer
           │
           │ depende de
           ↓
    Application Layer
           │
           │ depende de
           ↓
      Domain Layer
           ↑
           │ NO depende de nadie
           │ (núcleo del negocio)
           
    Infrastructure Layer
           │
           │ implementa contratos de
           ↓
      Domain Layer

REGLA DE ORO:
Las capas externas dependen de las internas,
NUNCA al revés.
```

## Dependency Injection en Laravel

```
┌──────────────────────────────────────────────────┐
│  WhistleblowingServiceProvider                   │
│                                                  │
│  register() {                                    │
│    $this->app->bind(                             │
│      ReportRepositoryInterface::class,           │
│      EloquentReportRepository::class             │
│    );                                            │
│  }                                               │
└──────────────────────────────────────────────────┘
                    │
                    │ Resuelve dependencias
                    ↓
┌──────────────────────────────────────────────────┐
│  WhistleblowerController                         │
│                                                  │
│  __construct(                                    │
│    ReportRepositoryInterface $repo               │
│  ) {                                             │
│    // Laravel inyecta automáticamente            │
│    // EloquentReportRepository                   │
│  }                                               │
└──────────────────────────────────────────────────┘
```

## Ventajas de esta Arquitectura

```
✅ TESTABILITY
   ┌─────────────────────┐
   │ Unit Tests          │  ← Fácil testear sin BD
   │ - Entities          │
   │ - Value Objects     │
   │ - Use Cases (mock)  │
   └─────────────────────┘

✅ MAINTAINABILITY
   ┌─────────────────────┐
   │ Separación clara    │  ← Cada capa tiene su
   │ de responsabilidades│     responsabilidad
   └─────────────────────┘

✅ FLEXIBILITY
   ┌─────────────────────┐
   │ Cambiar BD es fácil │  ← Solo cambias Infrastructure
   │ Eloquent → Doctrine │     Domain no se afecta
   │ MySQL → PostgreSQL  │
   └─────────────────────┘

✅ SCALABILITY
   ┌─────────────────────┐
   │ Módulos             │  ← Cada Bounded Context
   │ independientes      │     crece independiente
   │ (Bounded Contexts)  │
   └─────────────────────┘
```

## Bounded Context: Whistleblowing

```
╔═══════════════════════════════════════════════╗
║      BOUNDED CONTEXT: WHISTLEBLOWING         ║
╠═══════════════════════════════════════════════╣
║                                               ║
║  Lenguaje Ubicuo (Ubiquitous Language):      ║
║  - Report (Reporte)                           ║
║  - Reporter (Denunciante)                     ║
║  - Anonymous Report (Reporte Anónimo)         ║
║  - Status (pending, under_review, etc.)       ║
║                                               ║
║  Entidades del Dominio:                       ║
║  • Report                                     ║
║                                               ║
║  Value Objects:                               ║
║  • ReportStatus                               ║
║                                               ║
║  Casos de Uso:                                ║
║  • CreateReport                               ║
║  • GetReportById                              ║
║  • ReviewReport (futuro)                      ║
║  • ResolveReport (futuro)                     ║
║                                               ║
╚═══════════════════════════════════════════════╝
```

## Ejemplo de Agregado (Aggregate)

```
┌───────────────────────────────────────────┐
│        Report (Aggregate Root)            │
├───────────────────────────────────────────┤
│  + id: string                             │
│  + title: string                          │
│  + description: string                    │
│  + status: ReportStatus (Value Object)    │
│  + reporterId: ?string                    │
│  + createdAt: DateTimeImmutable           │
│  + updatedAt: DateTimeImmutable           │
├───────────────────────────────────────────┤
│  Métodos de Negocio:                      │
│  + markAsReviewed(): void                 │
│  + markAsResolved(): void                 │
│  + isAnonymous(): bool                    │
└───────────────────────────────────────────┘
        │
        │ contiene
        ↓
┌───────────────────────────────────────────┐
│      ReportStatus (Value Object)          │
├───────────────────────────────────────────┤
│  - value: string                          │
├───────────────────────────────────────────┤
│  + pending(): ReportStatus                │
│  + underReview(): ReportStatus            │
│  + reviewed(): ReportStatus               │
│  + resolved(): ReportStatus               │
│  + rejected(): ReportStatus               │
│  + equals(other): bool                    │
└───────────────────────────────────────────┘
```

## Próximos Pasos para Expandir

```
1. Domain Events
   ┌─────────────────────┐
   │ ReportCreated       │
   │ ReportReviewed      │
   │ ReportResolved      │
   └─────────────────────┘

2. Especificaciones
   ┌─────────────────────┐
   │ ReportSpecification │
   │ - byStatus()        │
   │ - byDateRange()     │
   │ - anonymous()       │
   └─────────────────────┘

3. DTOs
   ┌─────────────────────┐
   │ CreateReportDTO     │
   │ ReportDTO           │
   └─────────────────────┘

4. Nuevos Bounded Contexts
   ┌─────────────────────┐
   │ Investigation       │
   │ Notification        │
   │ Analytics           │
   └─────────────────────┘
```

---

**Este diagrama muestra la arquitectura hexagonal (ports & adapters) aplicada al proyecto.**
