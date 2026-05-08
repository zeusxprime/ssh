<?php


function update()
{

    echo "apt update";

}


function upgrade()
{

    echo "apt upgrade -y";
}


function installst()
{
    $stunnel = shell_exec("dpkg -l | grep -q stunnel4 && echo 1 || echo 0");
    if ($stunnel == "1") {
        echo "apt purge stunnel4 -y";
    } else {
        echo "apt install stunnel4 -y";
    }

}


function createconf($port)
{

    echo 'echo -e "cert = /etc/stunnel/stunnel.pem\nclient = no\nsocket = a:SO_REUSEADDR=1\nsocket = l:TCP_NODELAY=1\nsocket = r:TCP_NODELAY=1\n\n[stunnel]\nconnect = 127.0.0.1:22\naccept =' . $port . '" >/etc/stunnel/stunnel.conf';
}


function restartst()
{

    echo 'service stunnel4 restart';
}


function ssl_certif()
{
    $crt = 'EC';

    $keyContent = '';
    openssl_pkey_export(openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]), $keyContent);
    file_put_contents('key.pem', $keyContent);

    $certContent = shell_exec("echo '$crt\n$crt\n$crt\n$crt\n$crt\n$crt\n$crt' | openssl req -new -x509 -key key.pem -out cert.pem -days 1050");

    $combinedContent = file_get_contents('cert.pem') . file_get_contents('key.pem');
    file_put_contents('/etc/stunnel/stunnel.pem', $combinedContent);

    unlink('key.pem');
    unlink('cert.pem');

    $stunnelConfigPath = '/etc/default/stunnel4';
    $configContent = file_get_contents($stunnelConfigPath);
    $configContent = str_replace('ENABLED=0', 'ENABLED=1', $configContent);
    file_put_contents($stunnelConfigPath, $configContent);
}


function ston()
{
    $onoff = shell_exec('lsof -i | grep -q stunnel4 && echo "1" || echo "0"');
    if ($onoff == 1) {
        echo "ON";

    } else {
        echo "OFF";
    }
}
