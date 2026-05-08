<?php

function badvpn()
{
    $onoff = shell_exec('screen -list | grep -q badvpn && echo 1 || echo 0');
    if ($onoff == 1) {
        shell_exec('screen -X -S badvpn quit');
        echo "screen -dmS badvpn bash -c 'while true; do ulimit -n 999999 && /opt/DragonCore/badvpn-udpgw --listen-addr 127.0.0.1:7300 --max-clients 1024 --max-connections-for-client 2 --client-socket-sndbuf 10000; done'" . "\n" . "echo \"BadVPN ON | Porta: 7300\"";
    } else {
        deletecone("badx");
        incone("badx", "null", "null", "null", "null");
        echo "screen -dmS badvpn bash -c 'while true; do ulimit -n 999999 && /opt/DragonCore/badvpn-udpgw --listen-addr 127.0.0.1:7300 --max-clients 1024 --max-connections-for-client 2 --client-socket-sndbuf 10000; done'" . "\n" . "echo \"BadVPN ON | Porta: 7300\"";
    }
}


function badvpnon()
{
    $onoff = shell_exec('screen -list | grep -q badvpn && echo 1 || echo 0');
    if ($onoff == 1) {
        echo "ON";

    } else {
        echo "OFF";
    }
}

function badvpnstop()
{
    deletecone("badx");
    echo "screen -X -S badvpn quit" . "\n" . "echo BadVPN OFF";
}

