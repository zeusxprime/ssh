<?php

function bitmask()
{
    $cores = intval(shell_exec("/usr/bin/grep -c ^processor /proc/cpuinfo"));
    $half_cores = ceil($cores / 2);
    $bitmask = 0;

    for ($i = 0; $i < $half_cores; $i++) {
        $bitmask |= (1 << $i);
    }

    return $bitmask;
}

function netinter()
{
    $interface = trim(shell_exec("/usr/bin/ip route get 1 | grep -Po '(?<=dev )(\S+)'"));
    return $interface;
}

function insertnet()
{
    $netface = netinter();
    if ($netface === '') {
        echo "Não foi possível detectar interface de rede.\n";
        return;
    }

    $path = "/sys/class/net/$netface/queues/rx-0/rps_cpus";

    $checknet = trim(@shell_exec("/usr/bin/cat $path"));
    $bit      = bitmask();

    if ((int)$checknet === (int)$bit) {
        deletecone("netsta");
        shell_exec("/usr/bin/echo \"0\" > $path");
        echo "Balanceamento Desativado! \n";
    } else {
        deletecone("netsta");
        incone("netsta", "null", "null", "null", "null");
        shell_exec("/usr/bin/echo \"$bit\" > $path");
        echo "Balanceamento ativado! \n";
    }
}

function checknet()
{
    $netface = netinter();
    if ($netface === '') {
        echo "OFF";
        return;
    }

    $path = "/sys/class/net/$netface/queues/rx-0/rps_cpus";

    $checknet = trim(@shell_exec("/usr/bin/cat $path"));
    $bit      = bitmask();

    if ((int)$checknet === (int)$bit) {
        echo "ON";
    } else {
        echo "OFF";
    }
}
