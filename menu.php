<?php
require_once '/opt/DragonCore/config.php';
require "/opt/DragonCore/criarusuario.php";
require "/opt/DragonCore/removeruser.php";
require "/opt/DragonCore/database.php";
require "/opt/DragonCore/sshmonitor.php";
require "/opt/DragonCore/alterardata.php";
require "/opt/DragonCore/websocket.php";
require "/opt/DragonCore/badvpn.php";
require "/opt/DragonCore/alterarlimite.php";
require "/opt/DragonCore/alterarsenha.php";
require "/opt/DragonCore/stunnel.php";
require "/opt/DragonCore/infovps.php";
require "/opt/DragonCore/backup.php";
require "/opt/DragonCore/networkms.php";
require "/opt/DragonCore/openvpn.php";
require "/opt/DragonCore/network.php";
require "/opt/DragonCore/checkatt.php";
require "/opt/DragonCore/autostart.php";
require "/opt/DragonCore/checkusercontrol.php";
require "/opt/DragonCore/napster.php";
require "/opt/DragonCore/relatoriouser.php";
require "/opt/DragonCore/expirado.php";
require "/opt/DragonCore/userteste.php";
require "/opt/DragonCore/gbackup.php";
require "/opt/DragonCore/removertodos.php";
require "/opt/DragonCore/automenu.php";
require "/opt/DragonCore/proxydragon.php";
require "/opt/DragonCore/speedtest.php";
require "/opt/DragonCore/limiterstart.php";
require "/opt/DragonCore/statusvps.php";
require "/opt/DragonCore/bottg.php";
require "/opt/DragonCore/xray.php";
require "/opt/DragonCore/dnstt.php";


function clearScreen()
{
    // Check if the operating system is Windows
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // For Windows, use the "cls" command
        pclose(popen('cls', 'w'));
    } else {
        // For Unix-like systems, use ANSI escape codes to clear the screen
        system('clear');
    }
}



function menu()
{

    $users = retrieveDataAndCount();
    $userons = onlines();
    clearScreen();
    $version = shell_exec("cat /opt/DragonCore/version.txt");

    $menuItems = [
        "Gerenciar Usuarios" => "",
        "Conexoes " => "",
        "Ferramentas" => "",
    ];

    $maxDigits = strlen(count($menuItems));
    $version2 = checkatt();
    echo "DragonCore SSH | Versao: $version\n";
    echo "------------\n";
    echo "-| Usuarios Criados: $users |-\n";
    echo "-| Usuarios Online: $userons |-\n";
    echo "-| Status: $version2 |-\n";
    echo "------------\n";

    $i = 1;
    foreach ($menuItems as $item => $description) {
        $number = str_pad($i, $maxDigits, " ", STR_PAD_LEFT);
        echo "$number. $item";
        if (!empty($description)) {
            echo " $description";
        }
        echo "\n";
        $i++;
    }
    echo "0. Sair\n";
    echo "------------\n";

}

function menuconnect()
{
    $menuItems = [
        "Dragon X Go" => "",
        "Stunnel4" => "",
        "OpenVPN" => "",
        "Xray Core" => "",
        "DNSTT (SlowDNS)"    => "",
        "Portas Ativas" => "",
    ];

    $maxDigits = strlen(count($menuItems));

    echo "Conexoes\n";
    echo "------------\n";

    $i = 1;
    foreach ($menuItems as $item => $description) {
        $number = str_pad($i, $maxDigits, " ", STR_PAD_LEFT);
        echo "$number. $item";
        if (!empty($description)) {
            echo " $description";
        }
        echo "\n";
        $i++;
    }
    echo "0. Voltar para o menu\n";
    echo "------------\n";
}

function menuferramenta()
{

    $menuItems = [
        "Restaurar Backup SSHPlus" => "",
        "BadVPN X" => "",
        "Balanceamento de Rede" => "",
        "CheckUser Mult App" => "",
        "Gerar / Importar Backup DragonCoreSSH" => "",
        "AutoMenu" => "",
        "Speedtest" => "",
        "Limitador" => "Aviso Alto Uso de CPU!",
        "Atualizar" => "",
        "INFO VPS" => "",
        "Bot Telegram" => "",
        "Remover todos os usuarios" => "",
        "Remover Script" => "",
    ];

    $maxDigits = strlen(count($menuItems));

    echo "Ferramentas\n";
    echo "------------\n";

    $i = 1;
    foreach ($menuItems as $item => $description) {
        $number = str_pad($i, $maxDigits, " ", STR_PAD_LEFT);
        echo "$number. $item";
        if (!empty($description)) {
            echo " $description";
        }
        echo "\n";
        $i++;
    }
    echo "0. Voltar para o menu\n";
    echo "------------\n";
}



function menuusuario()
{
    $menuItems = [
        "Criar Usuario" => "",
        "Gerar Teste" => "",
        "Remover Usuario" => "",
        "Monitor Online" => "",
        "Alterar Validade" => "",
        "Alterar Limite" => "",
        "Alterar Senha" => "",
        "Relatorio de Usuarios" => "",
        "Remover Expirados" => "",
    ];

    $maxDigits = strlen(count($menuItems));

    echo "Gerenciamento de usuarios\n";
    echo "------------\n";

    $i = 1;
    foreach ($menuItems as $item => $description) {
        $number = str_pad($i, $maxDigits, " ", STR_PAD_LEFT);
        echo "$number. $item";
        if (!empty($description)) {
            echo " $description";
        }
        echo "\n";
        $i++;
    }

    echo "0. Voltar para o menu\n";
    echo "------------\n";

}

function menuxray()
{
    // Build a static set of management options for Xray.  Users can install, configure or
    // remove Xray regardless of its current state.  This avoids confusing toggles and allows
    // explicit selection of actions.
    $menuItems = [
        'Criar Usuario Xray' => '',
        'Remover Usuario Xray' => '',
        'Listar Usuarios Xray' => '',
        'Informacao Xray' => '',
        'Gerar Certificado TLS' => '',
        'Instalar/Configurar Xray Core' => '',
        'Remover Xray Core' => '',
    ];
    $maxDigits = strlen(count($menuItems));
    echo "Gerenciamento Xray\n";
    echo "------------\n";
    $i = 1;
    foreach ($menuItems as $item => $description) {
        $number = str_pad($i, $maxDigits, " ", STR_PAD_LEFT);
        echo "$number. $item";
        if (!empty($description)) {
            echo " $description";
        }
        echo "\n";
        $i++;
    }
    echo "0. Voltar para o menu\n";
    echo "------------\n";
}

if ($argc < 2) {

    die("Use o MENU!\n");
}

$functionName = $argv[1];

if (function_exists($functionName)) {

    array_shift($argv);
    echo call_user_func_array($functionName, array_slice($argv, 1));

} else {

    echo "Function $functionName does not exist.\n";
}
