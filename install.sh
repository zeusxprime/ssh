#!/bin/bash
set -e

# Token do repositório privado do DragonSSH.
# Troque TOKEN_DO_DRAGONSSH pelo token real ou envie por variável de ambiente.
DRAGONSSH_GITHUB_TOKEN="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"
export GIT_TERMINAL_PROMPT=0
export GCM_INTERACTIVE=Never

is_placeholder_token() {
    case "${1:-}" in
        ""|TOKEN_DO_*|SEU_TOKEN*|tokenaqui|TOKEN_AQUI) return 0 ;;
        *) return 1 ;;
    esac
}

urlencode_token() {
    printf '%s' "$1" | sed 's/%/%25/g; s/@/%40/g; s/:/%3A/g; s/#/%23/g; s/\//%2F/g; s/?/%3F/g; s/&/%26/g'
}

github_repo_url() {
    if is_placeholder_token "${DRAGONSSH_GITHUB_TOKEN:-}"; then
        printf '%s' "https://github.com/zeusxprime/ssh.git"
    else
        local encoded
        encoded="$(urlencode_token "$DRAGONSSH_GITHUB_TOKEN")"
        printf '%s' "https://x-access-token:${encoded}@github.com/zeusxprime/ssh.git"
    fi
}

github_raw_download() {
    local output="$1"
    local url="$2"

    if is_placeholder_token "${DRAGONSSH_GITHUB_TOKEN:-}"; then
        curl -fsSL --retry 2 --connect-timeout 10 --max-time 60 -o "$output" "$url" </dev/null
    else
        curl -fsSL --retry 2 --connect-timeout 10 --max-time 60 \
            -H "Authorization: Bearer ${DRAGONSSH_GITHUB_TOKEN}" \
            -H "Accept: application/vnd.github.raw" \
            -H "X-GitHub-Api-Version: 2022-11-28" \
            -o "$output" "$url" </dev/null
    fi
}

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

git clone "$(github_repo_url)" /opt/DragonCore || {
    echo "Erro ao clonar o repositório DragonSSH. Verifique DRAGONSSH_GITHUB_TOKEN com Contents: Read-only."
    exit 1
}
rm -rf /opt/DragonCore/aarch64
rm -rf /opt/DragonCore/x86_64
rm -rf /opt/DragonCore/install.sh

github_raw_download /opt/DragonCore/menu https://raw.githubusercontent.com/zeusxprime/ssh/refs/heads/main/menu
github_raw_download /opt/DragonCore/dragon_go https://raw.githubusercontent.com/zeusxprime/ssh/refs/heads/main/$(uname -m)/dragon_go
github_raw_download /opt/DragonCore/dnstt-server https://raw.githubusercontent.com/zeusxprime/ssh/refs/heads/main/$(uname -m)/dnstt-server
github_raw_download /opt/DragonCore/badvpn-udpgw https://raw.githubusercontent.com/zeusxprime/ssh/refs/heads/main/$(uname -m)/badvpn-udpgw
github_raw_download /opt/DragonCore/libcrypto.so.3 https://raw.githubusercontent.com/zeusxprime/ssh/refs/heads/main/$(uname -m)/libcrypto.so.3
github_raw_download /opt/DragonCore/libssl.so.3 https://raw.githubusercontent.com/zeusxprime/ssh/refs/heads/main/$(uname -m)/libssl.so.3
github_raw_download /opt/DragonCore/ProxyDragon https://raw.githubusercontent.com/zeusxprime/ssh/refs/heads/main/$(uname -m)/ProxyDragon
github_raw_download /opt/DragonCore/ulekbot https://raw.githubusercontent.com/zeusxprime/ssh/refs/heads/main/$(uname -m)/ulekbot

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
