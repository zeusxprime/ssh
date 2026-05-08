<?php

function dnstt($port, $nsDomain, $target)
{
    $port = preg_replace('/\D/', '', (string)$port);
    if ($port === '') {
        $port = '5300';
    }

    $nsDomain = trim($nsDomain);
    $target   = trim($target);

    if ($nsDomain === '' || $target === '') {
        echo "NS domain e destino nao podem estar vazios.\n";
        return;
    }

    $bin      = '/opt/DragonCore/dnstt-server';
    $confDir  = '/opt/DragonCore/dnstt';
    $privFile = $confDir . '/server.key';
    $pubFile  = $confDir . '/server.pub';

    if (!file_exists($bin)) {
        echo "Binario DNSTT nao encontrado em {$bin}\n";
        echo "Coloque o dnstt-server nesse caminho.\n";
        return;
    }

    if (!is_dir($confDir)) {
        mkdir($confDir, 0700, true);
    }

    if (!file_exists($privFile) || !file_exists($pubFile)) {
        $cmdGen = escapeshellcmd($bin) .
            ' -gen-key -privkey-file ' . escapeshellarg($privFile) .
            ' -pubkey-file ' . escapeshellarg($pubFile) .
            ' 2>/dev/null';
        shell_exec($cmdGen);
    }

    if (!file_exists($privFile) || !file_exists($pubFile)) {
        echo "Falha ao gerar as chaves do DNSTT.\n";
        return;
    }

    $cmd = "iptables -C INPUT -p udp --dport {$port} -j ACCEPT 2>/dev/null || iptables -I INPUT -p udp --dport {$port} -j ACCEPT";
    shell_exec($cmd);

    $cmd = "iptables -t nat -C PREROUTING -p udp --dport 53 -j REDIRECT --to-ports {$port} 2>/dev/null || iptables -t nat -I PREROUTING -p udp --dport 53 -j REDIRECT --to-ports {$port}";
    shell_exec($cmd);

    if (function_exists('deletecone') && function_exists('incone')) {
        deletecone('dnstt');
        incone('dnstt', $port, $nsDomain, $target, 'udp');
    }

    $onoff = trim(shell_exec('screen -list | grep -q dnstt && echo 1 || echo 0'));
    if ($onoff === '1') {
        shell_exec('screen -X -S dnstt quit');
    }

    $cmd = "/usr/bin/screen -dmS dnstt bash -c '"
        . "while true; do "
        . "ulimit -n 999999 && "
        . escapeshellcmd($bin)
        . " -udp 0.0.0.0:" . $port
        . " -privkey-file " . escapeshellarg($privFile)
        . " " . escapeshellarg($nsDomain)
        . " " . escapeshellarg($target)
        . "; "
        . "sleep 2; "
        . "done'";

    shell_exec($cmd);

    echo "DNSTT ON | UDP :{$port} | NS: {$nsDomain} | Destino: {$target}\n";
}

function dnstton()
{
    $onoff = trim(shell_exec('screen -list | grep -q dnstt && echo 1 || echo 0'));
    if ($onoff === '1') {
        echo 'ON';
    } else {
        echo 'OFF';
    }
}

function dnsttstop()
{
    if (function_exists('deletecone')) {
        deletecone('dnstt');
    }
    shell_exec('screen -X -S dnstt quit');
    echo "DNSTT OFF\n";
}

function dnsttpub()
{
    $pubFile = '/opt/DragonCore/dnstt/server.pub';

    if (!file_exists($pubFile)) {
        echo "Pubkey nao encontrada em {$pubFile}. Inicie o DNSTT uma vez para gerar.\n";
        return;
    }

    $content = trim(file_get_contents($pubFile));
    if ($content === '') {
        echo "Pubkey vazia em {$pubFile}\n";
    } else {
        echo "DNSTT PubKey:\n{$content}\n";
    }
}
