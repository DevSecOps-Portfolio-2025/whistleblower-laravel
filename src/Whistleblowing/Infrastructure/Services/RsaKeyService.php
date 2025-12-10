<?php

declare(strict_types=1);

namespace Src\Whistleblowing\Infrastructure\Services;

use RuntimeException;

class RsaKeyService
{
    private const KEY_SIZE = 4096;
    private const PRIVATE_KEY_TYPE = OPENSSL_KEYTYPE_RSA;
    
    private string $privateKeyPath;
    private string $publicKeyPath;

    public function __construct()
    {
        $keysDirectory = storage_path('keys');
        $this->privateKeyPath = $keysDirectory . '/private.pem';
        $this->publicKeyPath = $keysDirectory . '/public.pem';
    }

    /**
     * Genera un par de llaves RSA de 4096 bits
     * 
     * @throws RuntimeException Si no se pueden generar o guardar las llaves
     */
    public function generateKeys(): void
    {
        // Crear directorio si no existe
        $keysDirectory = storage_path('keys');
        if (!is_dir($keysDirectory)) {
            if (!mkdir($keysDirectory, 0755, true)) {
                throw new RuntimeException("No se pudo crear el directorio de llaves: {$keysDirectory}");
            }
        }

        // Configuración para generar llaves RSA de 4096 bits
        $config = [
            "private_key_bits" => self::KEY_SIZE,
            "private_key_type" => self::PRIVATE_KEY_TYPE,
        ];

        // Generar par de llaves
        $resource = openssl_pkey_new($config);
        if ($resource === false) {
            throw new RuntimeException("Error al generar las llaves RSA: " . openssl_error_string());
        }

        // Exportar llave privada
        if (!openssl_pkey_export($resource, $privateKey)) {
            throw new RuntimeException("Error al exportar la llave privada: " . openssl_error_string());
        }

        // Guardar llave privada con permisos restrictivos
        if (file_put_contents($this->privateKeyPath, $privateKey) === false) {
            throw new RuntimeException("Error al guardar la llave privada en: {$this->privateKeyPath}");
        }
        chmod($this->privateKeyPath, 0600); // Solo lectura/escritura para el propietario

        // Obtener llave pública
        $details = openssl_pkey_get_details($resource);
        if ($details === false || !isset($details['key'])) {
            throw new RuntimeException("Error al obtener detalles de la llave pública: " . openssl_error_string());
        }
        $publicKey = $details['key'];

        // Guardar llave pública
        if (file_put_contents($this->publicKeyPath, $publicKey) === false) {
            throw new RuntimeException("Error al guardar la llave pública en: {$this->publicKeyPath}");
        }
        chmod($this->publicKeyPath, 0644); // Lectura para todos, escritura solo para propietario
    }

    /**
     * Retorna el contenido de la llave pública
     * 
     * @throws RuntimeException Si la llave pública no existe
     */
    public function getPublicKey(): string
    {
        if (!file_exists($this->publicKeyPath)) {
            throw new RuntimeException(
                "La llave pública no existe. Ejecute: php artisan whistleblowing:generate-keys"
            );
        }

        $publicKey = file_get_contents($this->publicKeyPath);
        if ($publicKey === false) {
            throw new RuntimeException("Error al leer la llave pública desde: {$this->publicKeyPath}");
        }

        return $publicKey;
    }

    /**
     * Verifica si las llaves ya existen
     */
    public function keysExist(): bool
    {
        return file_exists($this->privateKeyPath) && file_exists($this->publicKeyPath);
    }

    /**
     * Obtiene la ruta de la llave privada
     */
    public function getPrivateKeyPath(): string
    {
        return $this->privateKeyPath;
    }

    /**
     * Obtiene la ruta de la llave pública
     */
    public function getPublicKeyPath(): string
    {
        return $this->publicKeyPath;
    }

    /**
     * Desencripta datos usando la llave privada RSA
     * 
     * @param string $encryptedData Datos encriptados en Base64
     * @return string|null Texto plano desencriptado, o null si falla
     * @throws RuntimeException Si la llave privada no existe o es inválida
     */
    public function decrypt(string $encryptedData): ?string
    {
        // Verificar que la llave privada existe
        if (!file_exists($this->privateKeyPath)) {
            throw new RuntimeException(
                "La llave privada no existe. Ejecute: php artisan whistleblowing:generate-keys"
            );
        }

        // Leer la llave privada
        $privateKey = file_get_contents($this->privateKeyPath);
        if ($privateKey === false) {
            throw new RuntimeException("Error al leer la llave privada desde: {$this->privateKeyPath}");
        }

        // Obtener el recurso de la llave privada
        $privateKeyResource = openssl_pkey_get_private($privateKey);
        if ($privateKeyResource === false) {
            throw new RuntimeException("Error al cargar la llave privada: " . openssl_error_string());
        }

        try {
            // Decodificar el Base64
            $encryptedBinary = base64_decode($encryptedData, true);
            if ($encryptedBinary === false) {
                return null; // Datos inválidos en Base64
            }

            // Desencriptar usando la llave privada con padding OAEP
            $decrypted = '';
            $success = openssl_private_decrypt(
                $encryptedBinary,
                $decrypted,
                $privateKeyResource,
                OPENSSL_PKCS1_OAEP_PADDING
            );

            if (!$success) {
                // Log del error para debugging (sin exponer datos sensibles)
                error_log("Error al desencriptar: " . openssl_error_string());
                return null;
            }

            return $decrypted;
        } finally {
            // Liberar el recurso de la llave
            if ($privateKeyResource !== false) {
                openssl_free_key($privateKeyResource);
            }
        }
    }
}
