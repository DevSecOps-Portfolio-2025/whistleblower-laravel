#!/usr/bin/env pwsh
# ================================================
# Script para ejecutar test de cifrado en Docker
# ================================================

Write-Host "🐳 Ejecutando test de cifrado dentro de Docker..." -ForegroundColor Cyan
Write-Host ""

# Ejecutar dentro del contenedor con la URL interna de Docker
docker-compose exec -e API_BASE_URL="http://web:80/api/v1/whistleblowing" app php test-encryption-flow.php

Write-Host ""
Write-Host "✅ Test completado" -ForegroundColor Green
