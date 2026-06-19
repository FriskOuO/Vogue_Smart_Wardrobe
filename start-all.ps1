param(
    [switch]$SkipDemoCheck
)

# VogueAI Smart Wardrobe - Start All Services
# This script opens three PowerShell windows:
# 1. Laravel server
# 2. Vite dev server
# 3. Python FastAPI AI Service
#
# Qdrant is started separately when real vector search is needed:
# .\start-qdrant.ps1 -NoTelemetry

$ProjectRoot = $PSScriptRoot
$AiServicePath = Join-Path $ProjectRoot "ai_service"

# Some Windows shells expose both Path and PATH in the same process. .NET's
# process launcher treats that as duplicate keys, so normalize before launching.
$ProcessEnv = [System.Environment]::GetEnvironmentVariables("Process")
$NormalizedPath = $ProcessEnv["Path"]
if (!$NormalizedPath) {
    $NormalizedPath = $ProcessEnv["PATH"]
}
if ($ProcessEnv.Contains("Path") -and $ProcessEnv.Contains("PATH")) {
    [System.Environment]::SetEnvironmentVariable("PATH", $null, "Process")
    [System.Environment]::SetEnvironmentVariable("Path", $NormalizedPath, "Process")
}

Write-Host "======================================" -ForegroundColor Cyan
Write-Host " VogueAI Smart Wardrobe Start Script" -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""

# Check project root
if (!(Test-Path $ProjectRoot)) {
    Write-Host "Project root not found: $ProjectRoot" -ForegroundColor Red
    exit 1
}

# Check artisan
if (!(Test-Path (Join-Path $ProjectRoot "artisan"))) {
    Write-Host "artisan not found. Please check Laravel project path." -ForegroundColor Red
    exit 1
}

# Check ai_service
if (!(Test-Path $AiServicePath)) {
    Write-Host "ai_service folder not found." -ForegroundColor Red
    exit 1
}

# Check AI virtual environment
$VenvPython = Join-Path $AiServicePath ".venv\Scripts\python.exe"
if (!(Test-Path $VenvPython)) {
    Write-Host "AI Service virtual environment not found: ai_service\.venv" -ForegroundColor Yellow
    Write-Host "Please create it first:" -ForegroundColor Yellow
    Write-Host "cd ai_service"
    Write-Host "python -m venv .venv"
    Write-Host ".\.venv\Scripts\python.exe -m pip install -r requirements.txt"
    exit 1
}

$NpmCmd = (Get-Command npm.cmd -ErrorAction SilentlyContinue).Source
if (!$NpmCmd) {
    Write-Host "npm.cmd not found. Please install Node.js and npm." -ForegroundColor Red
    exit 1
}

$PhpCmd = (Get-Command php.exe -ErrorAction SilentlyContinue).Source
if (!$PhpCmd) {
    Write-Host "php.exe not found. Please install PHP or add it to Path." -ForegroundColor Red
    exit 1
}

if (!$SkipDemoCheck) {
    Write-Host "Running demo readiness check..." -ForegroundColor Green
    Push-Location $ProjectRoot
    & $PhpCmd artisan vogueai:demo-check
    $DemoCheckExitCode = $LASTEXITCODE
    Pop-Location

    if ($DemoCheckExitCode -ne 0) {
        Write-Host "Demo readiness check failed. Fix required items before starting services." -ForegroundColor Red
        Write-Host "You can bypass this check with: .\start-all.ps1 -SkipDemoCheck" -ForegroundColor Yellow
        exit $DemoCheckExitCode
    }
}

Write-Host "Starting Laravel server..." -ForegroundColor Green
Start-Process powershell -ArgumentList @(
    "-NoExit",
    "-Command",
    "cd '$ProjectRoot'; & '$PhpCmd' artisan serve"
)

Start-Sleep -Seconds 2

Write-Host "Starting Vite dev server..." -ForegroundColor Green
Start-Process powershell -ArgumentList @(
    "-NoExit",
    "-Command",
    "cd '$ProjectRoot'; & '$NpmCmd' run dev"
)

Start-Sleep -Seconds 2

Write-Host "Starting Python AI Service..." -ForegroundColor Green
Start-Process powershell -ArgumentList @(
    "-NoExit",
    "-Command",
    "cd '$AiServicePath'; & '$VenvPython' -m uvicorn main:app --host 127.0.0.1 --port 8001 --reload"
)

Write-Host ""
Write-Host "All services are starting..." -ForegroundColor Cyan
Write-Host ""
Write-Host "Laravel:   http://127.0.0.1:8000" -ForegroundColor White
Write-Host "Vite:      http://127.0.0.1:5173" -ForegroundColor White
Write-Host "AI Health: http://127.0.0.1:8001/health" -ForegroundColor White
Write-Host ""
Write-Host "If a port is already in use, close the old terminal or kill the process." -ForegroundColor Yellow
