param(
    [string]$MasterContainer = "laravel-db",
    [string]$SlaveContainer = "laravel-db-slave",
    [string]$Database = "trello_test_aldo",
    [string]$Username = "root",
    [string]$Password = "root",
    [string]$DumpFile = ".\tmp-master-dump.sql"
)

$ErrorActionPreference = "Stop"

Write-Host "Dumping database '$Database' from $MasterContainer ..."
docker exec $MasterContainer sh -lc "mysqldump -u$Username -p$Password --single-transaction --routines --triggers $Database" > $DumpFile

if (-not (Test-Path $DumpFile)) {
    throw "Dump file was not created: $DumpFile"
}

Write-Host "Importing dump into $SlaveContainer ..."
Get-Content $DumpFile | docker exec -i $SlaveContainer sh -lc "mysql -u$Username -p$Password $Database"

Write-Host "Done. Slave data has been refreshed from master."
