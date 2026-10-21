# smart-eLibrary - register the Laravel scheduler as a logon Scheduled Task (native Windows).
#
# Without it nothing in app/Console/Kernel.php ever runs: the sitemap is never regenerated and the
# nightly search-index reconcile never happens. The task starts scripts\start-scheduler.ps1
# (php artisan schedule:work) hidden, 30 s after logon so Laragon's MySQL is up first.
#
# RUN ONCE (elevate only if a normal run reports Access Denied):
#   powershell -ExecutionPolicy Bypass -File C:\laragon\www\elibrary\scripts\register-scheduler-task.ps1
# If php.exe is not on PATH, give its path:
#   ... register-scheduler-task.ps1 -Php "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe"
param([string]$Php = "php")

$name   = "eLibrary-scheduler"
$script = Join-Path $PSScriptRoot "start-scheduler.ps1"

$action = New-ScheduledTaskAction -Execute "powershell.exe" `
    -Argument "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File `"$script`" -Php `"$Php`""

$trigger = New-ScheduledTaskTrigger -AtLogOn
$trigger.Delay = "PT30S"

$principal = New-ScheduledTaskPrincipal -UserId "$env:USERDOMAIN\$env:USERNAME" `
    -LogonType Interactive -RunLevel Limited

# A long-running process: no execution time limit. The launcher restarts PHP itself if it stops.
$settings = New-ScheduledTaskSettingsSet -ExecutionTimeLimit ([TimeSpan]::Zero) -StartWhenAvailable `
    -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries

try {
    Register-ScheduledTask -TaskName $name -Action $action -Trigger $trigger -Principal $principal `
        -Settings $settings -Force -ErrorAction Stop `
        -Description "Laravel scheduler for the smart-eLibrary: sitemap, nightly search-index reconcile." | Out-Null
    Write-Host "registered $name  (logon +30s)"
}
catch {
    Write-Warning ("FAILED " + $name + " - " + $_.Exception.Message)
    Write-Host "Re-run this in an ELEVATED PowerShell (Run as Administrator)." -ForegroundColor Yellow
}

# Show what actually exists now, so the output can't claim success it didn't achieve.
$task = Get-ScheduledTask -TaskName $name -ErrorAction SilentlyContinue
if ($task) { Write-Host ("Currently registered: " + $task.TaskName + "  [" + $task.State + "]") }
else       { Write-Host "Currently registered: (none)" }

Write-Host ""
Write-Host "It starts by itself at the next logon. Useful commands:"
Write-Host "  Start now:   schtasks /run /tn $name"
Write-Host "  Check:       php artisan schedule:list   (in the project folder)"
Write-Host "  Stop:        schtasks /end /tn $name"
Write-Host "  Remove:      Unregister-ScheduledTask -TaskName $name -Confirm:`$false"
