<?php

function criaruser($dias, $username, $password, $lim)
{
    if (is_numeric($dias) and is_numeric($lim)) {
        $final = date("Y-m-d", strtotime("+$dias days"));
        $passw = shell_exec('perl -e \'print crypt($ARGV[0], "password")\' ' . escapeshellarg($password));
        shell_exec("useradd -e $final -M -s /bin/false -p $passw $username");
        shell_exec("php /opt/DragonCore/menu.php insertData $username $password $lim");
    } else {
        echo "error invalid day or limit\n";
    }
}
