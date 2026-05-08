<?php

function checkatt()
{
    $version = shell_exec("cat /opt/DragonCore/version.txt");
    $version2 = shell_exec("wget -qO- https://raw.githubusercontent.com/zeusxprime/ssh/refs/heads/mainmain/version.txt");
    if ($version == $version2) {
        return "Atualizado";
    } else {
        return "Novo Update Disponivel !";
    }
}