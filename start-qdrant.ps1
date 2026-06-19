param(
    [switch]$NoTelemetry
)

$ProjectRoot = $PSScriptRoot
$QdrantPath = Join-Path $ProjectRoot "tools\qdrant\runtime\qdrant.exe"
$QdrantWorkdir = Split-Path $QdrantPath -Parent

if (!(Test-Path $QdrantPath)) {
    Write-Host "Qdrant binary not found:" -ForegroundColor Red
    Write-Host $QdrantPath -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Download the Windows binary from the official qdrant/qdrant GitHub release, then extract qdrant.exe to tools\qdrant\runtime." -ForegroundColor Yellow
    exit 1
}

$Existing = Get-Process qdrant -ErrorAction SilentlyContinue
if ($Existing) {
    Write-Host "Qdrant is already running:" -ForegroundColor Green
    $Existing | Select-Object Id, ProcessName, StartTime
    Write-Host "Health: http://127.0.0.1:6333/" -ForegroundColor White
    exit 0
}

$Args = @()
if ($NoTelemetry) {
    $Args += "--disable-telemetry"
}

Write-Host "Starting Qdrant..." -ForegroundColor Green
Write-Host "Path: $QdrantPath" -ForegroundColor White
Write-Host "Health: http://127.0.0.1:6333/" -ForegroundColor White

Start-Process powershell -ArgumentList @(
    "-NoExit",
    "-Command",
    "cd '$QdrantWorkdir'; & '$QdrantPath' $($Args -join ' ')"
)
