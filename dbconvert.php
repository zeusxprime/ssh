<?php
require "database.php";


function convertdba()
{
    $filePath = '/root/dragoncore.db';
    if (file_exists($filePath)) {
        $db = new SQLite3('dragoncore.db');
        $query = "SELECT * FROM users";

        $result = $db->query($query);

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $user = $row['user'];
            $pass = $row['pass'];
            $lim = $row['limi'];
            insertData($user, $pass, $lim);
            echo "$user CONVERTIDO\n";
        }
    } else {
        echo "Nova instalação nenhum DB convertido\n";
    }

}

function finishdba()
{
    $filePath = '/root/dragoncore.db';
    if (file_exists($filePath)) {
        exec("mv /root/dragoncore.db /root/dragoncore.bkp");
    } else {

    }
}

if ($argc < 2) {

    die("Use o MENU!\n");
}

$functionName = $argv[1];

if (function_exists($functionName)) {

    array_shift($argv);
    echo call_user_func_array($functionName, array_slice($argv, 1));

} else {

    echo "Function $functionName does not exist.\n";
}