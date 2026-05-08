<?php
require_once '/opt/DragonCore/config.php';

function relatoriouser()
{
    global $db_host, $db_port, $db_name, $db_user, $db_pass;


$conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");
    if (!$conn) {
        die("Connection failed: " . pg_last_error());
    }
    $query = "SELECT * FROM users";
    $result = pg_query($conn, $query);
    if (!$result) {
        die("Query execution failed: " . pg_last_error());
    }
    while ($row = pg_fetch_assoc($result)) {
        $user = $row['usr'];
        $pass = $row['pass'];
        $lim = $row['limi'];
        $id = $row['id'];
        $data = shell_exec("chage -l " . escapeshellarg($user) . " | grep -E \"Account expires\" | cut -d ' ' -f3-");
        echo "ID: $id | User: " . $user . " | Senha: " . $pass . " | Limite: " . $lim . "| Validade: " . "$data" . "\n";
    }
    pg_close($conn);

}