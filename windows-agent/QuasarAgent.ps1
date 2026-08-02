param([string]$ConfigPath = "$PSScriptRoot\config.json")

$ErrorActionPreference = 'Stop'
$config = Get-Content -Raw -Path $ConfigPath | ConvertFrom-Json
$stateDirectory = Split-Path -Parent $config.stateFile
New-Item -ItemType Directory -Force -Path $stateDirectory | Out-Null

function Read-State {
    if (Test-Path $config.stateFile) { return (Get-Content -Raw $config.stateFile | ConvertFrom-Json).line }
    return 0
}

function Write-State([int]$line) {
    @{ line = $line } | ConvertTo-Json | Set-Content -Encoding UTF8 -Path $config.stateFile
}

Write-Host "Quasar Agent iniciado. Leyendo $($config.sourceFile)"
while ($true) {
    try {
        if (Test-Path $config.sourceFile) {
            $lines = @(Get-Content -Path $config.sourceFile)
            $position = [int](Read-State)
            for ($index = $position; $index -lt $lines.Count; $index++) {
                $body = @{ line = $lines[$index]; equipmentId = $config.equipmentId; equipmentName = $config.equipmentName } | ConvertTo-Json -Compress
                Invoke-RestMethod -Method Post -Uri $config.apiUrl -Headers @{ 'X-API-Key' = $config.apiKey } -ContentType 'application/json' -Body $body | Out-Null
                Write-State ($index + 1)
            }
        }
    } catch {
        Write-Warning "No fue posible enviar la lectura: $($_.Exception.Message)"
    }
    Start-Sleep -Seconds ([int]$config.pollSeconds)
}
