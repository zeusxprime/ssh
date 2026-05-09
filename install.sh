#!/bin/bash

# DragonCore SSH installer - Ubuntu 20.04/22.04/24.04 + Debian, x64/ARM64
# Compatibilidade reforçada para Ubuntu 24.04 amd64/x86_64 e arm64/aarch64.

set -o pipefail

if [ "$(id -u)" -ne 0 ]; then
    if command -v sudo >/dev/null 2>&1; then
        exec sudo bash "$0" "$@"
    else
        echo "Execute como root ou instale sudo."
        exit 1
    fi
fi

if [ -r /etc/os-release ]; then
    . /etc/os-release
else
    echo "Sistema não suportado: /etc/os-release não encontrado."
    exit 1
fi

system="${ID:-ubuntu}"
version="${VERSION_ID:-}"
case "$system" in
    ubuntu)
        case "$version" in
            20.04|22.04|24.04) ;;
            *) echo "Ubuntu $version não homologado. Use Ubuntu 20.04, 22.04 ou 24.04."; exit 1 ;;
        esac
        ;;
    debian)
        apt-get update -y
        apt-get install -y sudo
        ;;
    *)
        echo "Sistema não suportado: $system. Use Ubuntu 20.04/22.04/24.04 ou Debian."
        exit 1
        ;;
esac

raw_arch="$(dpkg --print-architecture 2>/dev/null || uname -m)"
case "$raw_arch" in
    amd64|x86_64) ARCH_DIR="x86_64" ;;
    arm64|aarch64) ARCH_DIR="aarch64" ;;
    *) echo "Arquitetura não suportada: $raw_arch. Use x64/amd64 ou ARM64/aarch64."; exit 1 ;;
esac

export DEBIAN_FRONTEND=noninteractive

apt-get update -y
apt-get upgrade -y
apt-get install -y \
    sudo uuid-runtime curl wget git ca-certificates lsb-release apt-transport-https \
    software-properties-common gnupg php-cli php-curl php-sqlite3 php-pgsql \
    screen cron net-tools iproute2 iptables openssh-client openssh-server \
    unzip zip tar gzip procps lsof openssl libssl3

systemctl enable cron >/dev/null 2>&1 || true
systemctl start cron >/dev/null 2>&1 || true
systemctl enable ssh >/dev/null 2>&1 || true
systemctl restart ssh >/dev/null 2>&1 || systemctl restart sshd >/dev/null 2>&1 || true

if [ "$system" = "debian" ]; then
    repos=$(find /etc/apt/ -name '*.list' -exec cat {} + 2>/dev/null | grep ^[[:space:]]*deb | grep -q "packages.sury.org/php" && echo 1 || echo 0)
    if [ "$repos" = "0" ]; then
        echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | tee /etc/apt/sources.list.d/sury-php.list >/dev/null
        curl -fsSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /etc/apt/trusted.gpg.d/sury-keyring.gpg
        apt-get update -y
    fi
else
    repos=$(find /etc/apt/ -name '*.list' -exec cat {} + 2>/dev/null | grep ^[[:space:]]*deb | grep -q "/ondrej/php" && echo 1 || echo 0)
    if [ "$repos" = "0" ]; then
        add-apt-repository ppa:ondrej/php -y
        apt-get update -y
    fi
fi

apt-get install -y php-cli php-curl php-sqlite3 php-pgsql git

if [ ! -e "/bin/php" ]; then
    ln -s "$(command -v php)" /bin/php
fi

# === BACKUP EXISTING CONFIG.PHP BEFORE REMOVING FOLDER ===
CONFIG_BACKUP="/opt/DragonCore_config.php.bak"
if [ -f "/opt/DragonCore/config.php" ]; then
    cp /opt/DragonCore/config.php "$CONFIG_BACKUP"
    echo "Backup de config.php criado em $CONFIG_BACKUP"
fi
# =========================================================

cd /opt/ || exit 1
rm -rf DragonCore
cd "$HOME" || exit 1

git clone https://github.com/zeusxprime/ssh.git /opt/DragonCore
rm -rf /opt/DragonCore/aarch64
rm -rf /opt/DragonCore/x86_64
rm -rf /opt/DragonCore/install.sh

download_bin() {
    local name="$1"
    local url="https://raw.githubusercontent.com/zeusxprime/ssh/refs/heads/main/${ARCH_DIR}/${name}"
    curl -fSL --retry 3 --connect-timeout 15 -o "/opt/DragonCore/${name}" "$url"
}

curl -fSL --retry 3 --connect-timeout 15 -o /opt/DragonCore/menu https://raw.githubusercontent.com/zeusxprime/ssh/refs/heads/main/menu
download_bin dragon_go
download_bin dnstt-server
download_bin badvpn-udpgw
download_bin libcrypto.so.3
download_bin libssl.so.3
download_bin ProxyDragon
download_bin ulekbot

# Garante que binários do DragonCore encontrem as libs locais também no Ubuntu 20/22/24.
echo "/opt/DragonCore" > /etc/ld.so.conf.d/dragoncore.conf
ldconfig >/dev/null 2>&1 || true

cd /opt/DragonCore || exit 1
chmod +x *
cd "$HOME" || exit 1

if [ -f "$CONFIG_BACKUP" ]; then
    cp "$CONFIG_BACKUP" /opt/DragonCore/config.php
    echo "config.php restaurado de $CONFIG_BACKUP"
fi
# ==============================================

printf '%s\n' "/opt/DragonCore/menu" > /bin/menu
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

# Ubuntu 24.04 usa OpenSSL 3/libssl3. Não adiciona repositório focal-security nem força libssl1.1.
# Os binários do projeto que usam OpenSSL 3 recebem libssl.so.3/libcrypto.so.3 em /opt/DragonCore.
apt-get install -y libssl3 || true
ldconfig >/dev/null 2>&1 || true

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

if [ -f /etc/ssh/sshd_config ]; then
    grep -q '^HostKeyAlgorithms .*ssh-rsa' /etc/ssh/sshd_config || echo 'HostKeyAlgorithms +ssh-rsa' >> /etc/ssh/sshd_config
    grep -q '^PubkeyAcceptedKeyTypes .*ssh-rsa' /etc/ssh/sshd_config || echo 'PubkeyAcceptedKeyTypes +ssh-rsa' >> /etc/ssh/sshd_config
    systemctl restart ssh >/dev/null 2>&1 || systemctl restart sshd >/dev/null 2>&1 || true
fi

reposi2=$(find /etc/apt/ -name '*.list' -print0 2>/dev/null | xargs -0 cat 2>/dev/null | grep ^[[:space:]]*deb | grep -q "ookla" && echo 1 || echo 0)
if [ "$reposi2" = "1" ]; then
    echo "OK"
else
    curl -fsSL https://packagecloud.io/install/repositories/ookla/speedtest-cli/script.deb.sh | bash || true
    apt-get install -y speedtest || true
fi

install_netstat() {
    GREEN='\033[0;32m'
    RED='\033[0;31m'
    NC='\033[0m'
    if command -v netstat >/dev/null 2>&1; then
        echo -e "${GREEN}Netstat is already installed.${NC}"
    else
        echo "Netstat is not installed. Trying to install..."
        if command -v apt-get >/dev/null 2>&1; then
            apt-get update -y
            apt-get install -y net-tools
            echo -e "${GREEN}Netstat installation complete.${NC}"
        else
            echo -e "${RED}Unsupported system. Please install netstat manually.${NC}"
        fi
    fi
}
install_netstat

screen -X -S proxydragon quit >/dev/null 2>&1 || true
screen -X -S openvpn quit >/dev/null 2>&1 || true
screen -X -S badvpn quit >/dev/null 2>&1 || true
screen -X -S checkuser quit >/dev/null 2>&1 || true
screen -X -S napster quit >/dev/null 2>&1 || true
screen -X -S limiter quit >/dev/null 2>&1 || true
screen -X -S botulek quit >/dev/null 2>&1 || true

php /opt/DragonCore/menu.php autostart

echo ""
echo ""
echo ""
echo "Script instalado use o comando menu"
