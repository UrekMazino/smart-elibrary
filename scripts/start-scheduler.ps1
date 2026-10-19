# smart-eLibrary - Laravel scheduler launcher (native Windows)
#
# Runs `php artisan schedule:work`, which checks the schedule in app/Console/Kernel.php every minute
# and runs whatever is due: the sitemap at 03:30 and the search-index reconcile at 03:45. Without it,
# nothing scheduled ever runs. Registered as a logon task by scripts\register-scheduler-task.ps1.
# (On Linux, use a cron entry instead: see DEPLOYMENT_CHECKLIST.md.)
#
# -Php: path to php.exe when it is not on PATH, e.g.
#       C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe
param([string]$Php = "php")

$root = Split-Path -Parent $PSScriptRoot   # the project folder (this script lives in scripts\)
Set-Location $root

# schedule:work runs until it is stopped. If PHP ever exits (a crash, MySQL not up yet at logon),
# start it again after a short pause instead of leaving the schedule dead until the next logon.
while ($true) {
    & $Php artisan schedule:work
    Start-Sleep -Seconds 30
}
