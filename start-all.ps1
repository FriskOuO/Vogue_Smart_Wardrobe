# VogueAI Smart Wardrobe - Start All Services
# This script opens three PowerShell windows:
# 1. Laravel server
# 2. Vite dev server
# 3. Python FastAPI AI Service

$ProjectRoot = "C:\Users\User\Vogue_Smart_Wardrobe"
$AiServicePath = Join-Path $ProjectRoot "ai_service"

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
$VenvActivate = Join-Path $AiServicePath ".venv\Scripts\activate.ps1"
if (!(Test-Path $VenvActivate)) {
    Write-Host "AI Service virtual environment not found: ai_service\.venv" -ForegroundColor Yellow
    Write-Host "Please create it first:" -ForegroundColor Yellow
    Write-Host "cd ai_service"
    Write-Host "python -m venv .venv"
    Write-Host ".venv\Scripts\activate"
    Write-Host "pip install -r requirements.txt"
    exit 1
}

Write-Host "Starting Laravel server..." -ForegroundColor Green
Start-Process powershell -ArgumentList @(
    "-NoExit",
    "-Command",
    "cd '$ProjectRoot'; php artisan serve"
)

Start-Sleep -Seconds 2

Write-Host "Starting Vite dev server..." -ForegroundColor Green
Start-Process powershell -ArgumentList @(
    "-NoExit",
    "-Command",
    "cd '$ProjectRoot'; npm run dev"
)

Start-Sleep -Seconds 2

Write-Host "Starting Python AI Service..." -ForegroundColor Green
Start-Process powershell -ArgumentList @(
    "-NoExit",
    "-Command",
    "cd '$AiServicePath'; .\.venv\Scripts\activate.ps1; uvicorn main:app --host 127.0.0.1 --port 8001 --reload"
)

Write-Host ""
Write-Host "All services are starting..." -ForegroundColor Cyan
Write-Host ""
Write-Host "Laravel:   http://127.0.0.1:8000" -ForegroundColor White
Write-Host "Vite:      http://127.0.0.1:5173" -ForegroundColor White
Write-Host "AI Health: http://127.0.0.1:8001/health" -ForegroundColor White
Write-Host ""
Write-Host "If a port is already in use, close the old terminal or kill the process." -ForegroundColor Yellow