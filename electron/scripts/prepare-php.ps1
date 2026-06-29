# Bundle a portable PHP runtime for the Class Sync desktop installer.
# Run from the project root or electron folder before `npm run build:win`.

$ErrorActionPreference = 'Stop'

$projectRoot = Resolve-Path (Join-Path $PSScriptRoot '..\..')
$targetDir = Join-Path $projectRoot 'bin\php'
$laragonPhpRoot = 'C:\laragon\bin\php'

if (-not (Test-Path $laragonPhpRoot)) {
    Write-Error "Laragon PHP was not found at $laragonPhpRoot. Install Laragon or set PHP_BINARY manually."
}

$sourceDir = Get-ChildItem $laragonPhpRoot -Directory |
    Where-Object { $_.Name -like 'php-*' } |
    Sort-Object Name -Descending |
    ForEach-Object {
        $phpExe = Join-Path $_.FullName 'php.exe'
        if (-not (Test-Path $phpExe)) {
            return
        }

        & $phpExe -r "exit(extension_loaded('pdo_mysql') ? 0 : 1);" 2>$null
        if ($LASTEXITCODE -eq 0) {
            $_
        }
    } |
    Select-Object -First 1

if (-not $sourceDir) {
    $sourceDir = Get-ChildItem $laragonPhpRoot -Directory |
        Where-Object { $_.Name -like 'php-*' } |
        Sort-Object Name -Descending |
        Select-Object -First 1
}

if (-not $sourceDir) {
    Write-Error "No PHP version folder was found under $laragonPhpRoot."
}

Write-Host "Preparing desktop PHP runtime..."
Write-Host "Source: $($sourceDir.FullName)"
Write-Host "Target: $targetDir"

if (Test-Path $targetDir) {
    Remove-Item $targetDir -Recurse -Force
}

New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
Copy-Item -Path (Join-Path $sourceDir.FullName '*') -Destination $targetDir -Recurse -Force

if (-not (Test-Path (Join-Path $targetDir 'php.exe'))) {
    Write-Error 'php.exe was not copied into bin/php.'
}

Write-Host "Trimming portable PHP runtime..."

$removeFiles = @(
    'php-cgi.exe',
    'phpdbg.exe',
    'deplister.exe',
    'php-win.exe',
    'php.ini-development',
    'php.ini-production',
    'readme-redist-bins.txt',
    'snapshot.txt',
    'phpspec-dist.ini'
)

foreach ($file in $removeFiles) {
    $path = Join-Path $targetDir $file
    if (Test-Path $path) {
        Remove-Item $path -Force
    }
}

$keepExtensions = @(
    'php_curl.dll',
    'php_exif.dll',
    'php_fileinfo.dll',
    'php_gd.dll',
    'php_intl.dll',
    'php_mbstring.dll',
    'php_mysqli.dll',
    'php_openssl.dll',
    'php_pdo_mysql.dll',
    'php_pdo_sqlite.dll',
    'php_sodium.dll',
    'php_sqlite3.dll',
    'php_zip.dll'
)

$extDir = Join-Path $targetDir 'ext'
if (Test-Path $extDir) {
    Get-ChildItem $extDir -Filter '*.dll' | Where-Object { $keepExtensions -notcontains $_.Name } | Remove-Item -Force
}

$phpIni = Join-Path $targetDir 'php.ini'
if (-not (Test-Path $phpIni)) {
    @(
        "[PHP]",
        "extension_dir = `"$($extDir -replace '\\', '/')`"",
        "extension=curl",
        "extension=exif",
        "extension=fileinfo",
        "extension=gd",
        "extension=intl",
        "extension=mbstring",
        "extension=mysqli",
        "extension=openssl",
        "extension=pdo_mysql",
        "extension=pdo_sqlite",
        "extension=sodium",
        "extension=sqlite3",
        "extension=zip"
    ) | Set-Content -Path $phpIni -Encoding ASCII
}

Write-Host "Done. Portable PHP is ready for electron-builder."
