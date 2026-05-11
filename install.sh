#!/bin/bash
if grep -q 'NAME="Debian GNU/Linux"' /etc/os-release; then
    system="debian"
else
    system="ubuntu"
fi

if [ "$system" = "debian" ]; then
    apt-get install -y sudo
fi

sudo apt update
sudo apt upgrade -y
sudo apt install -y uuid-runtime
sudo apt install -y curl
sudo apt install -y lsb-release ca-certificates apt-transport-https software-properties-common gnupg curl wget
if [ "$system" = "debian" ]; then
    repos=$(find /etc/apt/ -name '*.list' -exec cat {} + | grep  ^[[:space:]]*deb | grep -q "packages.sury.org/php" && echo 1 || echo 0)
    if [ "$repos" = "0" ]; then
        echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/sury-php.list
        curl -fsSL  https://packages.sury.org/php/apt.gpg | sudo gpg --dearmor -o /etc/apt/trusted.gpg.d/sury-keyring.gpg
        sudo apt update
    fi
else
    repos=$(find /etc/apt/ -name '*.list' -exec cat {} + | grep  ^[[:space:]]*deb | grep -q "/ondrej/php" && echo 1 || echo 0)
    if [ "$repos" = "0" ]; then
        sudo apt install lsb-release ca-certificates apt-transport-https software-properties-common -y
        sudo add-apt-repository ppa:ondrej/php -y
        sudo apt update
    fi
fi
sudo apt install -y php-cli php-curl php-sqlite3 php-pgsql git

if [ ! -e "/bin/php" ]; then
    sudo ln -s "$(command -v php)" /bin/php
fi

# === BACKUP EXISTING CONFIG.PHP BEFORE REMOVING FOLDER ===
CONFIG_BACKUP="/opt/DragonCore_config.php.bak"
if [ -f "/opt/DragonCore/config.php" ]; then
    cp /opt/DragonCore/config.php "$CONFIG_BACKUP"
    echo "Backup de config.php criado em $CONFIG_BACKUP"
fi
# =========================================================

cd /opt/
rm -rf DragonCore
cd "$HOME"

git clone https://github.com/zeusxprime/ssh.git /opt/DragonCore
rm -rf /opt/DragonCore/aarch64
rm -rf /opt/DragonCore/x86_64
rm -rf /opt/DragonCore/install.sh

curl -s -L -o /opt/DragonCore/menu https://raw.githubusercontent.com/zeusxprime/ssh/refs/heads/main/menu
curl -s -L -o /opt/DragonCore/dragon_go https://raw.githubusercontent.com/zeusxprime/ssh/refs/heads/main/$(uname -m)/dragon_go
curl -s -L -o /opt/DragonCore/dnstt-server https://raw.githubusercontent.com/zeusxprime/ssh/refs/heads/main/$(uname -m)/dnstt-server
curl -s -L -o /opt/DragonCore/badvpn-udpgw https://raw.githubusercontent.com/zeusxprime/ssh/refs/heads/main/$(uname -m)/badvpn-udpgw
curl -s -L -o /opt/DragonCore/libcrypto.so.3 https://raw.githubusercontent.com/zeusxprime/ssh/refs/heads/main/$(uname -m)/libcrypto.so.3
curl -s -L -o /opt/DragonCore/libssl.so.3 https://raw.githubusercontent.com/zeusxprime/ssh/refs/heads/main/$(uname -m)/libssl.so.3
curl -s -L -o /opt/DragonCore/ProxyDragon https://raw.githubusercontent.com/zeusxprime/ssh/refs/heads/main/$(uname -m)/ProxyDragon
curl -s -L -o /opt/DragonCore/ulekbot https://raw.githubusercontent.com/zeusxprime/ssh/refs/heads/main/$(uname -m)/ulekbot

cd /opt/DragonCore
chmod +x *
cd "$HOME"


if [ -f "$CONFIG_BACKUP" ]; then
    cp "$CONFIG_BACKUP" /opt/DragonCore/config.php
    echo "config.php restaurado de $CONFIG_BACKUP"
fi
# ==============================================

echo -n "/opt/DragonCore/menu" > /bin/menu
chmod +x /bin/menu

existing_cron=$(crontab -l 2>/dev/null | grep -F "*/5 * * * * find /run/user -maxdepth 1 -mindepth 1 -type d -exec mount -o remount,size=1M {} \;")
if [ -z "$existing_cron" ]; then
    (crontab -l 2>/dev/null; echo "*/5 * * * * find /run/user -maxdepth 1 -mindepth 1 -type d -exec mount -o remount,size=1M {} \;") | crontab -
fi

existing_crono=$(crontab -l 2>/dev/null | grep -F "@reboot sleep 30 && /usr/bin/php /opt/DragonCore/menu.php autostart")
if [ -z "$existing_crono" ]; then
    (crontab -l 2>/dev/null; echo "@reboot sleep 30 && /usr/bin/php /opt/DragonCore/menu.php autostart") | crontab -
fi

existing_lima=$(crontab -l 2>/dev/null | grep -F '@reboot sleep 30 && find /etc/DragonTeste -name "*.sh" -exec {} \;')
if [ -z "$existing_lima" ]; then
    (crontab -l 2>/dev/null; echo '@reboot sleep 30 && find /etc/DragonTeste -name "*.sh" -exec {} \;') | crontab -
fi

if dpkg -s libssl1.1 &>/dev/null; then
    echo "libssl1.1 is already installed."
else
    echo "deb http://security.ubuntu.com/ubuntu focal-security main" | tee /etc/apt/sources.list.d/focal-security.list
    apt-get update && apt-get install -y libssl1.1
fi

bash <(php /opt/DragonCore/postinstall.php installpostgre)

# Gerar DBS:
php /opt/DragonCore/menu.php createautostart
php /opt/DragonCore/menu.php createTable
php /opt/DragonCore/menu.php createdbdragon
php /opt/DragonCore/menu.php createv2table
php /opt/DragonCore/dbconvert.php convertdba
php /opt/DragonCore/dbconvert.php finishdba
php /opt/DragonCore/menu.php deletecone ws 
php /opt/DragonCore/menu.php createXrayTable

sed -i '/# HostKeyAlgorithms/ a\HostKeyAlgorithms +ssh-rsa' /etc/ssh/sshd_config
sed -i '/# PubkeyAcceptedKeyTypes/ a\PubkeyAcceptedKeyTypes +ssh-rsa' /etc/ssh/sshd_config

reposi2=$(find /etc/apt/ -name *.list | xargs cat | grep  ^[[:space:]]*deb | grep -q "ookla" && echo 1 || echo 0)
if [ "$reposi2" = "1" ]; then
    echo "OK"
else
    curl -s https://packagecloud.io/install/repositories/ookla/speedtest-cli/script.deb.sh | bash
    apt install -y speedtest
fi

install_netstat() {
    GREEN='\033[0;32m'
    RED='\033[0;31m'
    NC='\033[0m'
    if command -v netstat &> /dev/null; then
        echo "${GREEN}Netstat is already installed.${NC}"
    else
        echo "Netstat is not installed. Trying to install..."
        if [ -x "$(command -v apt)" ]; then
            apt update
            apt install -y net-tools
            echo -e "${GREEN}Netstat installation complete.${NC}"
        else
            echo -e "${RED}Unsupported system. Please install netstat manually.${NC}"
        fi
    fi
}
install_netstat

# continua o script
screen -X -S proxydragon quit
screen -X -S openvpn quit
screen -X -S badvpn quit
screen -X -S checkuser quit
screen -X -S napster quit
screen -X -S limiter quit
screen -X -S botulek quit

php /opt/DragonCore/menu.php autostart

echo ""
echo ""
echo ""
echo "Script instalado use o comando menu"
