param(
    [string]$ServerUrl = "__QUASAR_SERVER_URL__",
    [switch]$Unattended,
    [string]$SourceFile,
    [string]$ApiKey,
    [string]$EquipmentName = $env:COMPUTERNAME,
    [string]$EquipmentId = $env:COMPUTERNAME
)

$ErrorActionPreference = 'Stop'
$installDirectory = Join-Path $env:ProgramData 'QuasarAgent'
$taskName = 'Quasar Agent'

function Read-Value([string]$Message, [string]$DefaultValue) {
    $suffix = if ($DefaultValue) { " [$DefaultValue]" } else { '' }
    $value = Read-Host "$Message$suffix"
    if ([string]::IsNullOrWhiteSpace($value)) { return $DefaultValue }
    return $value.Trim()
}

function Stop-WithMessage([string]$Message) {
    Write-Host "`nERROR: $Message" -ForegroundColor Red
    if (-not $Unattended) { Read-Host 'Presione ENTER para cerrar' | Out-Null }
    exit 1
}

Write-Host '====================================================' -ForegroundColor Cyan
Write-Host '  Instalador guiado del Agente Windows de Quasar' -ForegroundColor Cyan
Write-Host '====================================================' -ForegroundColor Cyan
Write-Host 'El agente leera las nuevas mediciones y las enviara de forma segura.'

try {
    $isAdministrator = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
    if (-not $isAdministrator) { Stop-WithMessage 'Ejecute este archivo desde PowerShell como administrador.' }

    if (-not $Unattended) {
        $SourceFile = Read-Value '1/4 Ruta completa del archivo Analisis.txt' 'C:\SistemaTXT\Entrada\Analisis.txt'
        $EquipmentName = Read-Value '2/4 Nombre con el que aparecera este equipo' $EquipmentName
        $EquipmentId = Read-Value '3/4 Identificador unico del equipo' $EquipmentId
        $ApiKey = Read-Host '4/4 Clave del agente entregada por el administrador'
    }

    if ([string]::IsNullOrWhiteSpace($SourceFile)) { Stop-WithMessage 'Debe indicar la ruta del archivo.' }
    if (-not (Test-Path -LiteralPath $SourceFile -PathType Leaf)) { Stop-WithMessage "No se encontro el archivo: $SourceFile" }
    if ([string]::IsNullOrWhiteSpace($ApiKey)) { Stop-WithMessage 'La clave del agente es obligatoria.' }
    if ([string]::IsNullOrWhiteSpace($ServerUrl) -or $ServerUrl -like '__*') { Stop-WithMessage 'El instalador no contiene una URL valida del servidor.' }

    $ServerUrl = $ServerUrl.TrimEnd('/')
    Write-Host "`nComprobando conexion con $ServerUrl ..." -ForegroundColor Yellow
    Invoke-RestMethod -Method Get -Uri "$ServerUrl/api/agent/status" -Headers @{ 'X-API-Key' = $ApiKey } -TimeoutSec 15 | Out-Null

    New-Item -ItemType Directory -Force -Path $installDirectory | Out-Null
    $agentBytes = [Convert]::FromBase64String('__QUASAR_AGENT_BASE64__')
    [IO.File]::WriteAllBytes((Join-Path $installDirectory 'QuasarAgent.ps1'), $agentBytes)

    $configuration = [ordered]@{
        sourceFile = $SourceFile
        apiUrl = "$ServerUrl/api/measurements"
        apiKey = $ApiKey
        equipmentId = $EquipmentId
        equipmentName = $EquipmentName
        pollSeconds = 5
        stateFile = (Join-Path $installDirectory 'state.json')
    }
    $configuration | ConvertTo-Json | Set-Content -Encoding UTF8 -Path (Join-Path $installDirectory 'config.json')

    $action = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File `"$installDirectory\QuasarAgent.ps1`""
    $trigger = New-ScheduledTaskTrigger -AtStartup
    $principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest
    $settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -RestartCount 20 -RestartInterval (New-TimeSpan -Minutes 1) -ExecutionTimeLimit ([TimeSpan]::Zero)
    Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Principal $principal -Settings $settings -Description 'Lee Analisis.txt y envia mediciones a Quasar.' -Force | Out-Null
    Start-ScheduledTask -TaskName $taskName

    Write-Host "`nINSTALACION COMPLETADA" -ForegroundColor Green
    Write-Host "Equipo: $EquipmentName ($EquipmentId)"
    Write-Host "Archivo: $SourceFile"
    Write-Host 'El agente se inicio y volvera a iniciarse automaticamente con Windows.'
    Write-Host "Configuracion: $installDirectory\config.json"
} catch {
    Stop-WithMessage $_.Exception.Message
}

if (-not $Unattended) { Read-Host '`nPresione ENTER para cerrar' | Out-Null }
