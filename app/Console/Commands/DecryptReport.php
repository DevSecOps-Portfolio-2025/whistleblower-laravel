<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use RuntimeException;
use Src\Whistleblowing\Application\UseCases\DecryptReportUseCase;

/**
 * Command: DecryptReport
 * 
 * Comando de consola para desencriptar el contenido de un reporte
 * usando la llave privada del servidor.
 * 
 * ⚠️ ADVERTENCIA CRÍTICA DE SEGURIDAD:
 * Este comando debe ser ejecutado ÚNICAMENTE por administradores autorizados.
 * El uso expone información confidencial y debe cumplir con:
 * - Políticas de privacidad de la organización
 * - Regulaciones de protección de datos (GDPR, etc.)
 * - Procedimientos de auditoría y logging
 * - Autorización explícita de personal autorizado
 */
class DecryptReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whistleblowing:decrypt {id : ID del reporte a desencriptar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Decrypts a report content using the server private key (Admin Only)';

    /**
     * Execute the console command.
     */
    public function handle(DecryptReportUseCase $decryptReportUseCase): int
    {
        $reportId = $this->argument('id');

        try {
            // ═══════════════════════════════════════════════════════════
            // ⚠️  ADVERTENCIA DE SEGURIDAD CRÍTICA
            // ═══════════════════════════════════════════════════════════
            $this->newLine();
            $this->error('╔═══════════════════════════════════════════════════════════════╗');
            $this->error('║            ⚠️  ADVERTENCIA DE SEGURIDAD CRÍTICA  ⚠️           ║');
            $this->error('╠═══════════════════════════════════════════════════════════════╣');
            $this->error('║  Este comando desencripta información ALTAMENTE CONFIDENCIAL  ║');
            $this->error('║                                                               ║');
            $this->error('║  • Solo debe ser ejecutado por administradores autorizados   ║');
            $this->error('║  • El acceso indebido puede violar leyes de privacidad       ║');
            $this->error('║  • Toda ejecución debe cumplir políticas de la organización  ║');
            $this->error('║  • Se recomienda implementar logging de auditoría            ║');
            $this->error('╚═══════════════════════════════════════════════════════════════╝');
            $this->newLine();

            // Confirmar que el usuario entiende las implicaciones
            if (!$this->confirm('¿Está autorizado para desencriptar este reporte y comprende las implicaciones?', false)) {
                $this->warn('⚠️  Operación cancelada por el usuario.');
                return self::FAILURE;
            }

            $this->newLine();
            $this->info("🔓 Desencriptando reporte ID: {$reportId}...");
            $this->newLine();

            // Ejecutar el caso de uso de desencriptado
            $decryptedContent = $decryptReportUseCase->execute($reportId);

            // Mostrar el contenido desencriptado
            $this->line('════════════════════════════════════════════════════════════════');
            $this->line('                  CONTENIDO DESENCRIPTADO                      ');
            $this->line('════════════════════════════════════════════════════════════════');
            $this->newLine();
            $this->line($decryptedContent);
            $this->newLine();
            $this->line('════════════════════════════════════════════════════════════════');
            $this->newLine();

            $this->info('✅ Reporte desencriptado exitosamente.');
            $this->newLine();

            // Recordatorio de seguridad final
            $this->comment('📋 Recordatorio: Documente este acceso según las políticas de la organización.');
            
            return self::SUCCESS;

        } catch (RuntimeException $e) {
            $this->newLine();
            $this->error('❌ Error al desencriptar el reporte:');
            $this->error($e->getMessage());
            $this->newLine();
            
            return self::FAILURE;
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Error inesperado:');
            $this->error($e->getMessage());
            $this->newLine();
            
            return self::FAILURE;
        }
    }
}
