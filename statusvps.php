<?php

function statusvps()
{
    clearScreen();
    $ram = ram();
    $cpu = cpu();
    $net = network();
    echo "Uso de CPU: $cpu\n";
    echo "Uso de RAM: $ram\n";
    echo "Uso de rede: $net\n";
}