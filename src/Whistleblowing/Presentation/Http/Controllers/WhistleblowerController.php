<?php

namespace Src\Whistleblowing\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Src\Whistleblowing\Application\UseCases\CreateReportUseCase;
use Src\Whistleblowing\Application\UseCases\GetReportByIdUseCase;
use Src\Whistleblowing\Domain\Repositories\ReportRepositoryInterface;

/**
 * Controller: WhistleblowerController
 * 
 * Controlador de presentación que maneja las peticiones HTTP.
 * Delega la lógica de negocio a los casos de uso.
 */
class WhistleblowerController extends Controller
{
    private ReportRepositoryInterface $reportRepository;

    public function __construct(ReportRepositoryInterface $reportRepository)
    {
        $this->reportRepository = $reportRepository;
    }

    /**
     * Listar todos los reportes
     */
    public function index(): JsonResponse
    {
        $reports = $this->reportRepository->findAll();

        return response()->json([
            'success' => true,
            'data' => array_map(fn($report) => [
                'id' => $report->getId(),
                'title' => $report->getTitle(),
                'description' => $report->getDescription(),
                'status' => $report->getStatus(),
                'is_anonymous' => $report->isAnonymous(),
                'created_at' => $report->getCreatedAt()->format('Y-m-d H:i:s'),
            ], $reports)
        ]);
    }

    /**
     * Crear un nuevo reporte
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|min:5|max:255',
                'description' => 'required|string|min:20',
                'reporter_id' => 'nullable|string',
            ]);

            $useCase = new CreateReportUseCase($this->reportRepository);
            $report = $useCase->execute([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'reporterId' => $validated['reporter_id'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reporte creado exitosamente',
                'data' => [
                    'id' => $report->getId(),
                    'title' => $report->getTitle(),
                    'status' => $report->getStatus(),
                    'is_anonymous' => $report->isAnonymous(),
                ]
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el reporte',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener un reporte específico
     */
    public function show(string $id): JsonResponse
    {
        $useCase = new GetReportByIdUseCase($this->reportRepository);
        $report = $useCase->execute($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Reporte no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $report->getId(),
                'title' => $report->getTitle(),
                'description' => $report->getDescription(),
                'status' => $report->getStatus(),
                'is_anonymous' => $report->isAnonymous(),
                'created_at' => $report->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $report->getUpdatedAt()->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    /**
     * Actualizar un reporte
     */
    public function update(Request $request, string $id): JsonResponse
    {
        // Implementar lógica de actualización
        return response()->json([
            'success' => true,
            'message' => 'Funcionalidad de actualización pendiente de implementar',
        ]);
    }

    /**
     * Eliminar un reporte
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->reportRepository->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Reporte eliminado exitosamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el reporte',
            ], 500);
        }
    }
}
