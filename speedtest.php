<?php


function speedtest()
{
    echo "Executando speedtest por favor aguarde!\n";
    $cake = shell_exec("speedtest --accept-license --accept-gdpr");
    echo "Aqui está o resultado\n";
    echo $cake . "\n";
}