# Remove previous electron-builder output before a fresh build.
# Close Class Sync / Electron and GitHub Desktop if deletion fails.

$ErrorActionPreference = 'Stop'

$distDir = Join-Path (Join-Path $PSScriptRoot '..') 'dist'

Write-Host "Cleaning $distDir ..."

for ($attempt = 1; $attempt -le 3; $attempt++) {
    try {
        Remove-Item $distDir -Recurse -Force -ErrorAction Stop
        Write-Host "Done."
        exit 0
    } catch {
        if ($attempt -eq 3) {
            Write-Error @"
Could not delete electron\dist. Another app is using files in that folder.

Close these, then run the build again:
  - Class Sync / Electron (npm start)
  - GitHub Desktop (if it is showing electron\dist files)
  - File Explorer windows open inside electron\dist

Original error: $($_.Exception.Message)
"@
        }

        Write-Host "Attempt $attempt failed, retrying in 2s..."
        Start-Sleep -Seconds 2
    }
}
