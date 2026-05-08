<?php

function checkuserstart()
{
    $onoff = shell_exec('screen -list | grep -q checkuser && echo 1 || echo 0');
    if ($onoff == 1) {
        shell_exec('screen -X -S checkuser quit');
        shell_exec("screen -dmS checkuser bash -c 'while true; do ulimit -n 999999 && php /opt/DragonCore/checkuser.php; done'");
        echo "CheckUser ON Porta: 2095\n";
    } else {
        deletecone("checkuser");
        incone("checkuser", "null", "null", "null", "null");
        shell_exec("screen -dmS checkuser bash -c 'while true; do ulimit -n 999999 && php /opt/DragonCore/checkuser.php; done'");
        echo "CheckUser ON Porta: 2095\n";
    }
}

function checkuseron()
{
    $onoff = shell_exec('screen -list | grep -q checkuser && echo 1 || echo 0');
    if ($onoff == 1) {
        echo "ON";

    } else {
        echo "OFF";
    }
}

function checkuserstop()
{
    deletecone("checkuser");
    shell_exec("screen -X -S checkuser quit");
    echo "CheckUser OFF\n";

}