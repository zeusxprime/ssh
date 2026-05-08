<?php


function ws($port, $banner)
{
    $onoff = shell_exec('screen -list | grep -q proxy && echo 1 || echo 0');
    if ($onoff == 1) {
        shell_exec('screen -X -S proxy quit');
        echo "screen -dmS proxy bash -c 'while true; do ulimit -n 999999 && /opt/DragonCore/proxy --ulimit 999999 --port $port --response $banner; done'" . "\n" . "echo \"Proxy ON Porta: $port | Banner: $banner\"";
    } else {
        deletecone("ws");
        incone("ws", $port, $banner, "null", "null");
        echo "screen -dmS proxy bash -c 'while true; do ulimit -n 999999 && /opt/DragonCore/proxy --ulimit 999999 --port $port --response $banner; done'" . "\n" . "echo \"Proxy ON Porta: $port | Banner: $banner\"";
    }
}


function wson()
{
    $onoff = shell_exec('screen -list | grep -q proxy && echo 1 || echo 0');
    if ($onoff == 1) {
        echo "ON";

    } else {
        echo "OFF";
    }
}


function wson2()
{
    $onoff = shell_exec('screen -list | grep -q proxy && echo 1 || echo 0');
    if ($onoff == 1) {
        return "\\e[32mWebsocket\\e[0m";

    } else {
        return "\\e[31mWebsocket\\e[0m";
    }
}


function wsstop()
{
    deletecone("ws");
    shell_exec("screen -X -S proxy quit");


}