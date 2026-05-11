<?php

require_once '/opt/DragonCore/config.php';

function removeexpired()
{

    global $db_host, $db_port, $db_name, $db_user, $db_pass;


$conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");
    if (!$conn) {
        die("Connection failed: " . pg_last_error());
    }
    $query = "SELECT usr FROM users";
    $result = pg_query($conn, $query);
    if (!$result) {
        die("Query execution failed: " . pg_last_error());
    }
    while ($row = pg_fetch_assoc($result)) {
        $user = $row['usr'];
        $validRaw = trim(shell_exec("chage -l " . escapeshellarg($user) . " | grep -E \"Account expires\" | cut -d ' ' -f3-"));
        $validTs = strtotime($validRaw);
        if ($validTs === false) {
            continue;
        }
        // O chage retorna apenas a data. Para não remover antes da hora final,
        // a conta só expira em 00:00 do dia seguinte à data de validade.
        $valid = strtotime('+1 day', $validTs);
        $currentTimestamp = time();
        if ($currentTimestamp >= $valid) {
            shell_exec("usermod -p $(openssl passwd -1 2837495738) $user");
            shell_exec("kill -9 `ps -fu $user | awk '{print $2}' | grep -v PID` >/dev/null 2>&1");
            shell_exec("userdel $user");
            shell_exec("php /opt/DragonCore/menu.php deleteData $user");
            echo "Usuario: $user - Removido\n";
        }
    }
    pg_close($conn);
}