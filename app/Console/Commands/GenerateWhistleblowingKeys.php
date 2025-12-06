<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Src\Whistleblowing\Infrastructure\Services\RsaKeyService;
use RuntimeException;

class GenerateWhistleblowingKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whistleblowing:generate-keys 
                            {--force : Forzar regeneración de llaves existentes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera un par de llaves RSA de 4096 bits para el módulo Whistleblowing';

    /**
     * Execute the console command.
     */
    public function handle(RsaKeyService $keyService): int
    {
        try {
            // Verificar si las llaves ya existen
            if ($keyService->keysExist() && !$this->option('force')) {
                $this->warn('⚠️  Las llaves RSA ya existen.');
                $this->info("Ubicaciones:");
                $this->line("  - Privada: {$keyService->getPrivateKeyPath()}");
                $this->line("  - Pública: {$keyService->getPublicKeyPath()}");
                $this->newLine();
                
                if (!$this->confirm('¿Desea regenerar las llaves? (Esto invalidará reportes cifrados con la llave anterior)', false)) {
                    $this->info('✅ Operación cancelada. Las llaves existentes se mantienen.');
                    return self::SUCCESS;
                }
            }

            $this->info('🔐 Generando par de llaves RSA de 4096 bits...');
            $this->newLine();

            // Generar llaves
            $keyService->generateKeys();

            $this->info('✅ Llaves RSA generadas exitosamente!');
            $this->newLine();
            $this->info('📁 Ubicaciones:');
            $this->line("  - Llave privada: {$keyService->getPrivateKeyPath()}");
            $this->line("  - Llave pública:  {$keyService->getPublicKeyPath()}");
            $this->newLine();
            
            $this->warn('⚠️  IMPORTANTE:');
            $this->warn('   - La llave privada NUNCA debe ser compartida');
            $this->warn('   - La llave privada NO debe subirse al repositorio');
            $this->warn('   - Asegúrese de hacer backup seguro de la llave privada');
            $this->newLine();
            
            $this->info('💡 La llave pública está disponible en:');
            $this->line('   GET /api/v1/whistleblowing/keys/public');

            return self::SUCCESS;

        } catch (RuntimeException $e) {
            $this->error('❌ Error al generar las llaves RSA:');
            $this->error("   {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
