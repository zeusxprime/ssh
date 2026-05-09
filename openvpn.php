<?php


function ovpnin()
{
    echo "apt-get install openvpn iptables easy-rsa openssl ca-certificates zip -y";
}


function setupOpenVPN()
{
    $pl = exec("find /usr -type f -name 'openvpn-plugin-auth-pam.so'");
    $openVPNPath = "/etc/openvpn";
    $GROUPNAME = "nogroup";
    $porta = 1194;
    $PROTOCOL = "tcp";
    mkdir("$openVPNPath/easy-rsa/");
    chdir("$openVPNPath/easy-rsa/");
    exec("chown -R root:root $openVPNPath/easy-rsa/");
    exec("ln -s /usr/share/easy-rsa/* $openVPNPath/easy-rsa/");
    exec("./easyrsa init-pki");
    exec("./easyrsa --batch build-ca nopass");
    exec("./easyrsa gen-dh");
    exec("./easyrsa build-server-full server nopass");
    exec("./easyrsa build-client-full DragonCore nopass");
    exec("./easyrsa gen-crl");
    exec("cp pki/ca.crt pki/private/ca.key pki/dh.pem pki/issued/server.crt pki/private/server.key $openVPNPath/easy-rsa/pki/crl.pem $openVPNPath");
    exec("chown nobody:$GROUPNAME $openVPNPath/crl.pem");
    exec("openvpn --genkey --secret $openVPNPath/ta.key");
    $configContent = "
port $porta
proto $PROTOCOL
dev tun
sndbuf 0
rcvbuf 0
ca ca.crt
cert server.crt
key server.key
dh dh.pem
tls-auth ta.key 0
topology subnet
server 10.8.0.0 255.255.255.0
ifconfig-pool-persist ipp.txt
push \"redirect-gateway def1 bypass-dhcp\"
push \"dhcp-option DNS 8.8.8.8\"
push \"dhcp-option DNS 8.8.8.8\"
keepalive 10 120
float
cipher AES-256-CBC
comp-lzo yes
user nobody
group $GROUPNAME
persist-key
persist-tun
status openvpn-status.log
management 127.0.0.1 7505
verb 3
crl-verify crl.pem
client-to-client
verify-client-cert none
username-as-common-name
plugin $pl login
duplicate-cn";
    file_put_contents("$openVPNPath/server.conf", $configContent);
    exec("systemctl stop openvpn");
    exec("systemctl disable openvpn");
}


function ipv4()
{
    echo "echo 'net.ipv4.ip_forward=1' >>/etc/sysctl.conf";
}


function ipv42()
{
    echo "echo 1 >/proc/sys/net/ipv4/ip_forward";
}


function client()
{
    $openVPNPath = "/etc/openvpn";
    $configContent = "#OVPN_ACCESS_SERVER_PROFILE=[DragonCoreSSH]
    client
    dev tun
    proto tcp
    sndbuf 0
    rcvbuf 0
    remote 127.0.0.1 1194
    resolv-retry 5
    nobind
    persist-key
    persist-tun
    remote-cert-tls server
    cipher AES-256-CBC
    comp-lzo yes
    setenv opt block-outside-dns
    key-direction 1
    verb 3
    auth-user-pass
    keepalive 10 120
    float";
    file_put_contents("$openVPNPath/client-common.txt", $configContent);
}


function client2()
{

    copy('/etc/openvpn/client-common.txt', "/root/DragonCore.ovpn");

    $file = fopen("/root/DragonCore.ovpn", "a");

    fwrite($file, "\n<ca>\n");
    fwrite($file, file_get_contents('/etc/openvpn/easy-rsa/pki/ca.crt'));
    fwrite($file, "</ca>\n");

    fwrite($file, "<cert>\n");
    fwrite($file, file_get_contents("/etc/openvpn/easy-rsa/pki/issued/DragonCore.crt"));
    fwrite($file, "</cert>\n");

    fwrite($file, "<key>\n");
    fwrite($file, file_get_contents("/etc/openvpn/easy-rsa/pki/private/DragonCore.key"));
    fwrite($file, "</key>\n");

    fwrite($file, "<tls-auth>\n");
    fwrite($file, file_get_contents("/etc/openvpn/ta.key"));
    fwrite($file, "</tls-auth>\n");

    fclose($file);

}


function checkinstall()
{
    $install = exec("openvpn --version | grep -q OpenVPN && echo \"1\" || echo \"0\"");
    if ("$install" == "1") {
        echo "OK";
    } else {
        echo "NOK";
    }
}


function checkovpon()
{
    $install = exec("screen -list | grep -q openvpn && echo \"1\" || echo \"0\"");
    if ("$install" == "1") {
        echo "OK";
    } else {
        echo "NOK";
    }
}


function startovpn()
{
    fixovpn();
    $onoff = shell_exec('screen -list | grep -q openvpn && echo 1 || echo 0');
    if ($onoff == 1) {
        shell_exec('screen -X -S openvpn quit');
        echo "screen -dmS openvpn bash -c 'while true; do ulimit -n 999999 && /opt/DragonCore/iptables.sh && cd /etc/openvpn && openvpn --config /etc/openvpn/server.conf; done'" . "\n" . "echo \"OVPN ON Porta: 1194\"";
    } else {
        deletecone("open");
        incone("open", "null", "null", "null", "null");
        echo "screen -dmS openvpn bash -c 'while true; do ulimit -n 999999 && /opt/DragonCore/iptables.sh && cd /etc/openvpn && openvpn --config /etc/openvpn/server.conf; done'" . "\n" . "echo \"OVPN ON Porta: 1194\"";
    }
}


function stopovpn()
{
    deletecone("open");
    shell_exec('screen -X -S openvpn quit');
    echo "OVPN Offline";
}


function fixovpn2()
{
    $openVPNPath = "/etc/openvpn";
    $pl = trim((string)exec("find /usr -type f -name 'openvpn-plugin-auth-pam.so' | head -n 1"));
    if ($pl === '') {
        $pl = '/usr/lib/x86_64-linux-gnu/openvpn/plugins/openvpn-plugin-auth-pam.so';
    }
    $configContent = "
port 1194
proto tcp
dev tun
sndbuf 0
rcvbuf 0
ca ca.crt
cert server.crt
key server.key
dh dh.pem
tls-auth ta.key 0
topology subnet
server 10.8.0.0 255.255.255.0
push \"redirect-gateway def1 bypass-dhcp\"
push \"dhcp-option DNS 8.8.8.8\"
push \"dhcp-option DNS 8.8.4.4\"
keepalive 10 120
float
cipher AES-256-CBC
comp-lzo yes
user nobody
group nogroup
persist-key
persist-tun
status openvpn-status.log
management localhost 7505
verb 3
crl-verify crl.pem
client-to-client
verify-client-cert none
username-as-common-name
plugin $pl login
duplicate-cn
";
    file_put_contents("$openVPNPath/server.conf", $configContent);
}


function fixovpn()
{
    $cake3 = exec("cat /etc/openvpn/client-common.txt | grep -q \"http-proxy\" && echo \"1\" || echo \"0\"");
    if ("$cake3" == "1") {
        exec("systemctl stop openvpn");
        exec("systemctl disable openvpn");
        exec("rm -rf /etc/openvpn/client-common.txt");
        exec("rm -rf /root/DragonCore.ovpn");
        exec("rm -rf /etc/openvpn/server.conf");
        client();
        client2();
        fixovpn2();
    }
}



