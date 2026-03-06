<?php

use Illuminate\Support\Facades\DB;

DB::purge('mysql');
DB::reconnect('mysql');

$conn = DB::connection('mysql');

$writeHost = $conn->selectOne('select @@hostname as host')->host;
$readHost = $conn->selectOne('select @@hostname as host', [], true)->host;

return [
    'write_host' => $writeHost,
    'read_host' => $readHost,
    'config_host_note' => 'DB::connection("mysql")->getConfig("host") is config fallback only, not active PDO target.',
];
