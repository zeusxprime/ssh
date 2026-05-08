<?php

function automenu()
{
    $bash = file_get_contents('/root/.bashrc');
    $bash .= "\nmenu\n";
    file_put_contents('/root/.bashrc', $bash);
    echo "Auto Menu Ativado\n";
}

function noautomenu()
{
    $bash = file_get_contents('/root/.bashrc');
    $bash = str_replace("menu\n", '', $bash);
    file_put_contents('/root/.bashrc', $bash);
    echo "Auto Menu Desativado\n";
}

function ckautomenu()
{

    $bash = shell_exec("cat /root/.bashrc | grep -q menu && echo OK || echo notok");
    if (trim($bash) == "OK") {
        echo "OK";
    } else {
        echo "notok";
    }

}