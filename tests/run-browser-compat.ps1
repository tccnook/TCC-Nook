# Execute em Windows com Node e navegadores instalados.
# Nenhuma senha ou sessão é escrita no relatório.
[CmdletBinding()]
param(
    [Parameter(Mandatory=$true)][uri]$BaseUrl,
    [Parameter(Mandatory=$true)][int]$Book,
    [string]$Session = $env:NOOK_TEST_SESSION,
    [string]$PlaywrightPath = $env:NOOK_PLAYWRIGHT_PATH,
    [string]$Browsers = 'chrome,msedge,firefox',
    [string]$OutputDirectory = (Join-Path $env:TEMP ('nook-compat-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))),
    [switch]$AllowWrites
)
$ErrorActionPreference = 'Stop'
if ($env:OS -ne 'Windows_NT') { throw 'Este runner destina-se a uma execução real no Windows.' }
if (-not (Get-Command node -ErrorAction SilentlyContinue)) { throw 'Instale Node.js antes de executar os testes.' }
if ([string]::IsNullOrWhiteSpace($Session)) { throw 'Defina NOOK_TEST_SESSION com a sessão de uma conta de testes.' }
if (-not $BaseUrl.AbsoluteUri.EndsWith('/')) { throw 'BaseUrl deve terminar em /paginas/.' }
$env:NOOK_TEST_URL = $BaseUrl.AbsoluteUri
$env:NOOK_TEST_SESSION = $Session
$env:NOOK_TEST_BOOK = [string]$Book
$env:NOOK_TEST_BROWSERS = $Browsers
$env:NOOK_TEST_OUTPUT = $OutputDirectory
$env:NOOK_TEST_MUTATIONS = if ($AllowWrites) { '1' } else { '0' }
if (-not [string]::IsNullOrWhiteSpace($PlaywrightPath)) { $env:NOOK_PLAYWRIGHT_PATH = $PlaywrightPath }
node (Join-Path $PSScriptRoot 'browser-compat.cjs')
if ($LASTEXITCODE -ne 0) { throw "A matriz teve falhas ou navegador indisponível. Consulte $OutputDirectory\report.json" }
Write-Host "Testes concluídos no Windows. Relatório: $OutputDirectory\report.json"
