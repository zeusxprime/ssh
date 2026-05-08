<?php


function ulekbot($token, $tgid)
{
    $onoff = shell_exec('screen -list | grep -q botulek && echo 1 || echo 0');
    if ($onoff == 1) {
        shell_exec('screen -X -S botulek quit');
        shell_exec("screen -dmS botulek bash -c 'while true; do ulimit -n 999999 && /opt/DragonCore/ulekbot --token $token --id $tgid; done'");
        echo "BOT ULEK TELEGRAM ONLINE";
    } else {
        deletecone("botulek");
        incone("botulek", "null", $token, $tgid, "null");
        shell_exec("screen -dmS botulek bash -c 'while true; do ulimit -n 999999 && /opt/DragonCore/ulekbot --token $token --id $tgid; done'");
        echo "BOT ULEK TELEGRAM ONLINE";
    }
}


function ulekboton()
{
    $onoff = shell_exec('screen -list | grep -q botulek && echo 1 || echo 0');
    if ($onoff == 1) {
        echo "ON";

    } else {
        echo "OFF";
    }
}


function ulekbotstop()
{
    deletecone("botulek");
    shell_exec("screen -X -S botulek quit");
    echo "BOT ULEK TELEGRAM OFF";

}