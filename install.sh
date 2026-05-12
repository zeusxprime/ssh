#!/usr/bin/env bash
set -Eeuo pipefail

REPO_URL="https://github.com/zeusxprime/ssh.git"
RAW_BASE="https://raw.githubusercontent.com/zeusxprime/ssh/refs/heads/main"
INSTALL_DIR="/opt/DragonCore"
CONFIG_BACKUP="/opt/DragonCore_config.php.bak"
LOG_FILE="/var/log/dragonssh-install.log"

export DEBIAN_FRONTEND=noninteractive
export NEEDRESTART_MODE=a
export APT_LISTCHANGES_FRONTEND=none

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

msg(){ echo -e "${GREEN}$*${NC}"; }
warn(){ echo -e "${YELLOW}$*${NC}"; }
fail(){ echo -e "${RED}Erro:${NC} $*"; exit 1; }

require_root(){
  [[ "${EUID:-$(id -u)}" -eq 0 ]] || fail "execute como root: sudo bash install.sh"
}

detect_system(){
  if grep -qi 'debian' /etc/os-release 2>/dev/null; then echo debian; else echo ubuntu; fi
}

detect_arch(){
  case "$(uname -m)" in
    x86_64|amd64) echo x86_64 ;;
    aarch64|arm64) echo aarch64 ;;
    *) fail "arquitetura não suportada: $(uname -m). Suporte esperado: x86_64/amd64 e aarch64/arm64" ;;
  esac
}

apt_install(){
  apt-get install -y -qq \
    -o DPkg::Lock::Timeout=180 \
    -o Dpkg::Options::="--force-confdef" \
    -o Dpkg::Options::="--force-confold" "$@"
}

download_required(){
  local url="$1" out="$2"
  curl -fsSL --retry 2 --connect-timeout 10 --max-time 120 "$url" -o "$out" \
    || fail "falha ao baixar: $url"
  [[ -s "$out" ]] || fail "arquivo vazio: $out"
}

install_php_repo(){
  local system="$1"
  apt_install lsb-release ca-certificates apt-transport-https software-properties-common gnupg curl wget git sudo
  if [[ "$system" == "debian" ]]; then
    if ! grep -Rqs "packages.sury.org/php" /etc/apt/sources.list /etc/apt/sources.list.d 2>/dev/null; then
      curl -fsSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /etc/apt/trusted.gpg.d/sury-keyring.gpg
      echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/sury-php.list
    fi
  else
    if ! grep -Rqs "/ondrej/php" /etc/apt/sources.list /etc/apt/sources.list.d 2>/dev/null; then
      add-apt-repository ppa:ondrej/php -y >/dev/null
    fi
  fi
  apt-get update -y -qq -o DPkg::Lock::Timeout=180
}

install_optional_libssl11(){
  if dpkg -s libssl1.1 >/dev/null 2>&1; then
    echo "libssl1.1 is already installed."
  else
    echo "deb http://security.ubuntu.com/ubuntu focal-security main" > /etc/apt/sources.list.d/focal-security.list
    apt-get update -y -qq -o DPkg::Lock::Timeout=180
    apt-get install -y -qq libssl1.1
  fi
}

create_menu_wrapper(){
  cat > /bin/menu <<'EOS'
#!/usr/bin/env bash
bash /opt/DragonCore/menu "$@"
if command -v gestorvps >/dev/null 2>&1; then
  exec gestorvps
elif [[ -x /usr/local/bin/gestorvps ]]; then
  exec /usr/local/bin/gestorvps
elif [[ -x /opt/.gestorvps/gestorvps.sh ]]; then
  exec bash /opt/.gestorvps/gestorvps.sh
fi
exit 0
EOS
  chmod +x /bin/menu
}


ask_install_or_menu(){
  echo
  read -r -p "Deseja instalar/atualizar o DragonSSH? [s/n]: " resp
  case "${resp,,}" in
    s|sim|y|yes|"")
      return 0
      ;;
    n|nao|não|no)
      echo "Abrindo menu..."
      if command -v menu >/dev/null 2>&1; then
        exec menu
      elif [[ -x /bin/menu ]]; then
        exec /bin/menu
      elif [[ -x "$INSTALL_DIR/menu" ]]; then
        exec "$INSTALL_DIR/menu"
      else
        warn "Menu não encontrado. Instale o DragonSSH primeiro."
        if command -v gestorvps >/dev/null 2>&1; then exec gestorvps; fi
        exit 0
      fi
      ;;
    *)
      warn "Resposta inválida. Use s ou n."
      ask_install_or_menu
      ;;
  esac
}

add_cron_once(){
  local line="$1"
  local current
  current="$(crontab -l 2>/dev/null || true)"
  if ! printf '%s\n' "$current" | grep -Fqx "$line"; then
    (printf '%s\n' "$current"; printf '%s\n' "$line") | sed '/^$/d' | crontab -
  fi
}

main(){
  require_root
  ask_install_or_menu
  : > "$LOG_FILE"
  exec > >(tee -a "$LOG_FILE") 2>&1

  local system arch
  system="$(detect_system)"
  arch="$(detect_arch)"

  msg "Preparando dependências para $system / $arch..."
  apt-get update -y -qq -o DPkg::Lock::Timeout=180
  apt_install sudo uuid-runtime curl wget git ca-certificates gnupg lsb-release apt-transport-https software-properties-common net-tools screen cron
  install_php_repo "$system"
  apt_install php-cli php-curl php-sqlite3 php-pgsql
  [[ -e /bin/php ]] || ln -sf "$(command -v php)" /bin/php

  if [[ -f "$INSTALL_DIR/config.php" ]]; then
    cp "$INSTALL_DIR/config.php" "$CONFIG_BACKUP"
    msg "Backup de config.php criado em $CONFIG_BACKUP"
  fi

  msg "Baixando DragonSSH..."
  rm -rf "$INSTALL_DIR"
  git clone --depth 1 "$REPO_URL" "$INSTALL_DIR" || fail "falha ao clonar $REPO_URL"
  rm -rf "$INSTALL_DIR/aarch64" "$INSTALL_DIR/x86_64" "$INSTALL_DIR/install.sh"

  download_required "$RAW_BASE/menu" "$INSTALL_DIR/menu"
  for bin in dragon_go dnstt-server badvpn-udpgw libcrypto.so.3 libssl.so.3 ProxyDragon ulekbot; do
    download_required "$RAW_BASE/$arch/$bin" "$INSTALL_DIR/$bin"
  done

  chmod +x "$INSTALL_DIR"/* || true

  if [[ -f "$CONFIG_BACKUP" ]]; then
    cp "$CONFIG_BACKUP" "$INSTALL_DIR/config.php"
    msg "config.php restaurado de $CONFIG_BACKUP"
  fi

  create_menu_wrapper

  add_cron_once '*/5 * * * * find /run/user -maxdepth 1 -mindepth 1 -type d -exec mount -o remount,size=1M {} \;'
  add_cron_once '@reboot sleep 30 && /usr/bin/php /opt/DragonCore/menu.php autostart'
  add_cron_once '@reboot sleep 30 && find /etc/DragonTeste -name "*.sh" -exec {} \;'

  install_optional_libssl11

  msg "Finalizando banco e serviços..."
  if [[ -f "$INSTALL_DIR/postinstall.php" ]]; then
    bash <(php "$INSTALL_DIR/postinstall.php" installpostgre) || true
  fi

  php "$INSTALL_DIR/menu.php" createautostart || true
  php "$INSTALL_DIR/menu.php" createTable || true
  php "$INSTALL_DIR/menu.php" createdbdragon || true
  php "$INSTALL_DIR/menu.php" createv2table || true
  php "$INSTALL_DIR/dbconvert.php" convertdba || true
  php "$INSTALL_DIR/dbconvert.php" finishdba || true
  php "$INSTALL_DIR/menu.php" deletecone ws || true
  php "$INSTALL_DIR/menu.php" createXrayTable || true

  grep -q '^HostKeyAlgorithms +ssh-rsa' /etc/ssh/sshd_config 2>/dev/null || echo 'HostKeyAlgorithms +ssh-rsa' >> /etc/ssh/sshd_config
  grep -q '^PubkeyAcceptedKeyTypes +ssh-rsa' /etc/ssh/sshd_config 2>/dev/null || echo 'PubkeyAcceptedKeyTypes +ssh-rsa' >> /etc/ssh/sshd_config
  systemctl restart ssh 2>/dev/null || systemctl restart sshd 2>/dev/null || true

  screen -X -S proxydragon quit 2>/dev/null || true
  screen -X -S openvpn quit 2>/dev/null || true
  screen -X -S badvpn quit 2>/dev/null || true
  screen -X -S checkuser quit 2>/dev/null || true
  screen -X -S napster quit 2>/dev/null || true
  screen -X -S limiter quit 2>/dev/null || true
  screen -X -S botulek quit 2>/dev/null || true

  php "$INSTALL_DIR/menu.php" autostart || true

  echo
  msg "Script instalado. Use o comando: menu"
  echo "Log: $LOG_FILE"
}

main "$@"
