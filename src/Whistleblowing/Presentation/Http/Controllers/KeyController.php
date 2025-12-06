<?php

declare(strict_types=1);

namespace Src\Whistleblowing\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Src\Whistleblowing\Infrastructure\Services\RsaKeyService;
use RuntimeException;

class KeyController
{
    public function __construct(
        private readonly RsaKeyService $keyService
    ) {}

    /**
     * Obtiene la llave pública RSA
     * 
     * GET /api/v1/whistleblowing/keys/public
     * 
     * @return JsonResponse
     */
    public function getPublicKey(): JsonResponse
    {
        try {
            $publicKey = $this->keyService->getPublicKey();

            return response()->json([
                'publicKey' => $publicKey,
                'keySize' => 4096,
                'algorithm' => 'RSA',
                'usage' => 'encryption',
                'timestamp' => now()->toIso8601String(),
            ], 200);

        } catch (RuntimeException $e) {
            return response()->json([
                'error' => 'Public key not available',
                'message' => $e->getMessage(),
                'hint' => 'Run: php artisan whistleblowing:generate-keys',
            ], 503);
        }
    }
}
