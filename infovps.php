<?php

function getVpsInformation()
{
    echo "\033[44;1;37m               INFORMACOES DO VPS                 \033[0m\n\n";

    if (file_exists('/etc/lsb-release')) {
        echo "\033[1;31m• \033[1;32mSISTEMA OPERACIONAL\033[1;31m •\033[0m\n\n";
        $name = exec("cat /etc/lsb-release | grep DESCRIPTION | awk -F = {'print $2'}");
        $codename = exec("cat /etc/lsb-release | grep CODENAME | awk -F = {'print $2'}");

        echo "\033[1;33mNome: \033[1;37m$name\n";
        echo "\033[1;33mCodeName: \033[1;37m$codename\n";
        echo "\033[1;33mKernel: \033[1;37m" . php_uname('s') . "\n";
        echo "\033[1;33mKernel Release: \033[1;37m" . php_uname('r') . "\n";

        if (file_exists('/etc/os-release')) {
            $devlike = exec("cat /etc/os-release | grep LIKE | awk -F = {'print $2'}");
            echo "\033[1;33mDerivado do OS: \033[1;37m$devlike\n\n";
        }
    } else {
        $system = exec("cat /etc/issue.net");
        echo "\033[1;31m• \033[1;32mSISTEMA OPERACIONAL\033[1;31m •\033[0m\n\n";
        echo "\033[1;33mNome: \033[1;37m$system\n\n";
    }

    if (file_exists('/proc/cpuinfo')) {
        $uso = exec("top -bn1 | awk '/Cpu/ { cpu = \"\" 100 - $8 \"%\" }; END { print cpu }'");
        echo "\033[1;31m• \033[1;32mPROCESSADOR\033[1;31m •\033[0m\n\n";
        $modelo = exec("cat /proc/cpuinfo | grep 'model name' | uniq | awk -F : {'print $2'}");
        $cpucores = exec("grep -c cpu[0-9] /proc/stat");
        $cache = exec("cat /proc/cpuinfo | grep 'cache size' | uniq | awk -F : {'print $2'}");
        $architecture = shell_exec("uname -p");
        if (empty($architecture)) {
            $architecture = shell_exec("uname -m");
        }
        echo "\033[1;33mModelo:\033[1;37m$modelo\n";
        echo "\033[1;33mNucleos:\033[1;37m $cpucores\n";
        echo "\033[1;33mMemoria Cache:\033[1;37m$cache\n";
        echo "\033[1;33mArquitetura: \033[1;37m" . trim($architecture) . "\n";
        echo "\033[1;33multilizacao: \033[37m$uso\n\n";
    } else {
        echo "\033[1;32mPROCESSADOR\033[0m\n\n";
        echo "Não foi possivel obter informações\n";
    }

    if (function_exists('shell_exec') && is_callable('shell_exec')) {
        $freeOutput = shell_exec("free -h");

        // Extracting values from the 'free -h' output
        $ramValues = explode("\n", $freeOutput)[1];
        $ramValues = preg_split('/\s+/', $ramValues, -1, PREG_SPLIT_NO_EMPTY);

        $usoram = exec("free -m | awk 'NR==2{printf \"%.2f%%\t\t\", $3*100/$2 }'");

        echo "\033[1;31m• \033[1;32mMEMORIA RAM\033[1;31m •\033[0m\n\n";
        echo "\033[1;33mTotal: \033[1;37m" . $ramValues[1] . "\n";
        echo "\033[1;33mEm Uso: \033[1;37m" . $ramValues[2] . "\n";
        echo "\033[1;33mLivre: \033[1;37m" . $ramValues[6] . "\n";
        echo "\033[1;33multilizacao: \033[37m$usoram\n\n";
    } else {
        echo "\033[1;32mMEMORIA RAM\033[0m\n\n";
        echo "Não foi possivel obter informações\n";
    }
    /*echo "\033[1;31m• \033[1;32mSERVICOS EM EXECUCAO\033[1;31m •\033[0m\n\n";
    $pt = shell_exec("lsof -V -i tcp -P -n | grep -v 'ESTABLISHED' | grep -v 'COMMAND' | grep 'LISTEN'");

    $portas = explode("\n", $pt);
    $seenPorts = array(); // Array to keep track of seen ports
    foreach ($portas as $linha) {
        $explodedLine = explode(":", $linha);
        if (count($explodedLine) > 1) {
            $porta = explode(" ", $explodedLine[1])[0];
            if (!in_array($porta, $seenPorts)) { // Check if port is not already seen
                $svcs = explode(" ", $explodedLine[0])[0];
                echo "\033[1;33mServico \033[1;37m$svcs \033[1;33mPorta \033[1;37m$porta\n";
                $seenPorts[] = $porta; // Add port to seen ports array
            }
        }
    }*/


}

function infoport()
{
    $pt = shell_exec("lsof -V -i tcp -P -n | grep -v 'ESTABLISHED' | grep -v 'COMMAND' | grep 'LISTEN'");

    $portas = explode("\n", $pt);
    $seenPorts = array(); // Array to keep track of seen ports
    foreach ($portas as $linha) {
        $explodedLine = explode(":", $linha);
        if (count($explodedLine) > 1) {
            $porta = explode(" ", $explodedLine[1])[0];
            if (!in_array($porta, $seenPorts)) { // Check if port is not already seen
                $svcs = explode(" ", $explodedLine[0])[0];
                echo "\033[1;33mServico \033[1;37m$svcs \033[1;33mPorta \033[1;37m$porta\n";
                $seenPorts[] = $porta; // Add port to seen ports array
            }
        }
    }
}



function cpu()
{
    $minUsage = PHP_INT_MAX;
    for ($i = 0; $i < 10; $i++) {
        $usage = exec("top -bn1 | awk '/Cpu/ { cpu = \"\" 100 - $8 \"%\" }; END { print cpu }'");
        $parsedUsage = (float) rtrim($usage, '%');
        $minUsage = min($minUsage, $parsedUsage);
    }
    return $minUsage . '%';
}



function ram()
{
    $usoram = exec("free -m | awk 'NR==2{printf \"%.2f%%\t\t\", $3*100/$2 }'");
    return $usoram;
}


function onlines()
{

    $onlines = shell_exec("ps -x | grep sshd | grep -v root | grep priv | wc -l");
    $onelines = str_replace(array("\r", "\n"), '', $onlines);
    return $onelines;
}

