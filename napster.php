<?php


function napster($port)
{
    $onoff = shell_exec('screen -list | grep -q napster && echo 1 || echo 0');
    if ($onoff == 1) {
        shell_exec('screen -X -S napster quit');
        shell_exec("screen -dmS napster bash -c 'while true; do ulimit -n 999999 && /opt/DragonCore/dragon_go -port :$port; done'");
        echo "Dragon SSH Napster ON Porta: $port";
    } else {
        deletecone("napster");
        incone("napster", $port, "null", "null", "null");
        shell_exec("screen -dmS napster bash -c 'while true; do ulimit -n 999999 && /opt/DragonCore/dragon_go -port :$port; done'");
        echo "Dragon SSH Napster ON Porta: $port";
    }
}


function napsteron()
{
    $onoff = shell_exec('screen -list | grep -q napster && echo 1 || echo 0');
    if ($onoff == 1) {
        echo "ON";

    } else {
        echo "OFF";
    }
}


function napsteron2()
{
    $onoff = shell_exec('screen -list | grep -q napster && echo 1 || echo 0');
    if ($onoff == 1) {
        return "\\e[32mWebsocket\\e[0m";

    } else {
        return "\\e[31mWebsocket\\e[0m";
    }
}


function napsterstop()
{
    deletecone("napster");
    shell_exec("screen -X -S napster quit");
    echo "Napster OFF";

}