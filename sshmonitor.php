<?php
require_once '/opt/DragonCore/config.php';

function sshmonitor()
{
    global $db_host, $db_port, $db_name, $db_user, $db_pass;


$conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");

    $query = "SELECT * FROM users";

    $result = pg_query($conn, $query);

    while ($row = pg_fetch_assoc($result)) {
        $user = $row['usr'];
        $lim = $row['limi'];
        $numer = trim(shell_exec("ps -u " . $row['usr'] . " | grep -c sshd"));
        if ($numer > 0) {
            echo $user . " - " . $numer . "/" . $lim . "\n";
        }
    }

    pg_close($conn);
}


function sshmonitordragon()
{
    global $db_host, $db_port, $db_name, $db_user, $db_pass;


$conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");

    $query = "SELECT * FROM users";

    $result = pg_query($conn, $query);

    while ($row = pg_fetch_assoc($result)) {
        $user = $row['usr'];
        $lim = $row['limi'];
        $numer = trim(shell_exec("ps -u " . $row['usr'] . " | grep -c sshd"));
        echo $user . " - " . $numer . "/" . $lim . "\n";
    }

    pg_close($conn);
}