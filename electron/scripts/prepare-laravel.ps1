# Prepare Laravel for a production desktop build.
# Run from the electron folder before electron-builder.

$ErrorActionPreference = 'Stop'

$projectRoot = Resolve-Path (Join-Path $PSScriptRoot '..\..')

Write-Host "Preparing Laravel for production packaging..."
Set-Location $projectRoot

if (-not (Get-Command composer -ErrorAction SilentlyContinue)) {
    Write-Error "Composer was not found in PATH. Install Composer or run from Laragon terminal."
}

Write-Host "Installing PHP dependencies (no dev)..."
composer install --no-dev --optimize-autoloader --no-interaction --no-progress

if (-not (Test-Path (Join-Path $projectRoot 'public\build'))) {
    Write-Host "Building frontend assets..."
    npm run build
}

Write-Host "Done. Laravel is ready for electron-builder."
