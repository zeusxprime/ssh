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

progress_line(){
  local percent="$1" text="$2" width=30 filled empty bar
  (( percent < 0 )) && percent=0
  (( percent > 100 )) && percent=100
  filled=$(( percent * width / 100 ))
  empty=$(( width - filled ))
  bar="$(printf '%*s' "$filled" '' | tr ' ' '#')$(printf '%*s' "$empty" '' | tr ' ' '-')"
  printf "\r[%s] %3s%% | %-55s" "$bar" "$percent" "$text"
}

progress_done(){
  progress_line 100 "Instalação finalizada"
  printf "\n"
}

run_step(){
  local percent="$1" text="$2"
  shift 2
  progress_line "$percent" "$text"
  if ! "$@" >>"$LOG_FILE" 2>&1; then
    printf "\n"
    echo -e "${RED}O instalador retornou erro em:${NC} $text"
    echo "Confira o log: $LOG_FILE"
    tail -n 20 "$LOG_FILE" 2>/dev/null || true
    exit 1
  fi
}

run_step_shell(){
  local percent="$1" text="$2" code="$3"
  progress_line "$percent" "$text"
  if ! bash -c "$code" >>"$LOG_FILE" 2>&1; then
    printf "\n"
    echo -e "${RED}O instalador retornou erro em:${NC} $text"
    echo "Confira o log: $LOG_FILE"
    tail -n 20 "$LOG_FILE" 2>/dev/null || true
    exit 1
  fi
}

require_root(){
  [[ "${EUID:-$(id -u)}" -eq 0 ]] || fail "execute como root: sudo bash install.sh"
}

detect_system(){
  # Ubuntu somente. Não usa Debian/Sury.
  if [[ -r /etc/os-release ]]; then
    . /etc/os-release
    if [[ "${ID:-}" != "ubuntu" ]]; then
      fail "sistema não suportado: ${PRETTY_NAME:-desconhecido}. Este instalador trabalha somente com Ubuntu."
    fi
    echo "ubuntu"
    return 0
  fi
  fail "não foi possível detectar o sistema. Este instalador trabalha somente com Ubuntu."
}

cleanup_broken_php_repos(){
  # Remove qualquer repositório PHP externo deixado por tentativas antigas.
  # Isso precisa rodar antes do primeiro apt update, senão o apt pode quebrar.
  rm -f /etc/apt/sources.list.d/sury-php.list
  rm -f /etc/apt/trusted.gpg.d/sury-keyring.gpg
  rm -f /etc/apt/sources.list.d/ondrej-ubuntu-php-*.list 2>/dev/null || true

  # Caso a linha tenha sido colocada em outro arquivo .list, comenta só ela.
  grep -RIl "packages.sury.org/php" /etc/apt/sources.list /etc/apt/sources.list.d/*.list 2>/dev/null | while read -r file; do
    sed -i.bak '/packages\.sury\.org\/php/s/^/# removido pelo instalador DragonSSH: /' "$file" || true
  done
}

detect_arch(){
  # Detecta a arquitetura do Ubuntu antes de instalar.
  # amd64 usa a pasta x86_64 do DragonSSH; arm64 usa a pasta aarch64.
  local machine deb_arch
  machine="$(uname -m)"
  deb_arch="$(dpkg --print-architecture 2>/dev/null || true)"
  case "$deb_arch:$machine" in
    amd64:*|*:x86_64) echo x86_64 ;;
    arm64:*|*:aarch64) echo aarch64 ;;
    *) fail "arquitetura não suportada: ${deb_arch:-$machine}. Suporte esperado no Ubuntu: amd64/x86_64 e arm64/aarch64" ;;
  esac
}

arch_label(){
  case "$1" in
    x86_64) echo "amd64/x86_64" ;;
    aarch64) echo "arm64/aarch64" ;;
    *) echo "$1" ;;
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
  apt_install lsb-release ca-certificates apt-transport-https software-properties-common gnupg curl wget git sudo
  cleanup_broken_php_repos
  # Ubuntu somente: usa PHP nativo do Ubuntu.
  apt-get update -y -qq -o DPkg::Lock::Timeout=180
}

install_optional_libssl11(){
  if dpkg -s libssl1.1 >/dev/null 2>&1; then
    echo "libssl1.1 is already installed."
  else
    local deb_arch repo_url
    deb_arch="$(dpkg --print-architecture 2>/dev/null || true)"
    case "$deb_arch" in
      arm64) repo_url="http://ports.ubuntu.com/ubuntu-ports" ;;
      amd64) repo_url="http://security.ubuntu.com/ubuntu" ;;
      *) fail "arquitetura não suportada para libssl1.1: ${deb_arch:-desconhecida}" ;;
    esac
    echo "deb [arch=${deb_arch}] ${repo_url} focal-security main" > /etc/apt/sources.list.d/focal-security-libssl11.list
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

  local system arch label
  system="$(detect_system)"
  arch="$(detect_arch)"
  label="$(arch_label "$arch")"

  echo
  progress_line 1 "Iniciando instalação para Ubuntu / $label"
  sleep 0.2

  run_step 5 "Limpando repositórios antigos" cleanup_broken_php_repos
  run_step 10 "Atualizando lista de pacotes" apt-get update -y -qq -o DPkg::Lock::Timeout=180
  run_step 18 "Instalando dependências base" apt_install sudo uuid-runtime curl wget git ca-certificates gnupg lsb-release apt-transport-https software-properties-common net-tools screen cron
  run_step 26 "Preparando PHP nativo do Ubuntu" install_php_repo
  run_step 34 "Instalando módulos PHP" apt_install php-cli php-curl php-sqlite3 php-pgsql
  run_step_shell 38 "Configurando atalho do PHP" '[[ -e /bin/php ]] || ln -sf "$(command -v php)" /bin/php'

  if [[ -f "$INSTALL_DIR/config.php" ]]; then
    run_step 42 "Criando backup do config.php" cp "$INSTALL_DIR/config.php" "$CONFIG_BACKUP"
  fi

  run_step_shell 48 "Baixando DragonSSH" "rm -rf '$INSTALL_DIR' && git clone --depth 1 '$REPO_URL' '$INSTALL_DIR' && rm -rf '$INSTALL_DIR/aarch64' '$INSTALL_DIR/x86_64' '$INSTALL_DIR/install.sh'"
  run_step 53 "Baixando menu principal" download_required "$RAW_BASE/menu" "$INSTALL_DIR/menu"

  local bin pct
  pct=56
  for bin in dragon_go dnstt-server badvpn-udpgw libcrypto.so.3 libssl.so.3 ProxyDragon ulekbot; do
    run_step "$pct" "Baixando $bin" download_required "$RAW_BASE/$arch/$bin" "$INSTALL_DIR/$bin"
    pct=$((pct + 4))
  done

  run_step_shell 84 "Aplicando permissões" "chmod +x '$INSTALL_DIR'/* || true"

  if [[ -f "$CONFIG_BACKUP" ]]; then
    run_step 86 "Restaurando config.php" cp "$CONFIG_BACKUP" "$INSTALL_DIR/config.php"
  fi

  run_step 88 "Criando comando menu" create_menu_wrapper
  run_step 90 "Configurando tarefas automáticas" bash -c "$(declare -f add_cron_once); add_cron_once '*/5 * * * * find /run/user -maxdepth 1 -mindepth 1 -type d -exec mount -o remount,size=1M {} \\;'; add_cron_once '@reboot sleep 30 && /usr/bin/php /opt/DragonCore/menu.php autostart'; add_cron_once '@reboot sleep 30 && find /etc/DragonTeste -name \"*.sh\" -exec {} \\;'"
  run_step 92 "Instalando libssl1.1" install_optional_libssl11

  run_step_shell 94 "Finalizando banco e serviços" "
    cd '$INSTALL_DIR'
    if [[ -f '$INSTALL_DIR/postinstall.php' ]]; then bash <(php '$INSTALL_DIR/postinstall.php' installpostgre) || true; fi
    php '$INSTALL_DIR/menu.php' createautostart || true
    php '$INSTALL_DIR/menu.php' createTable || true
    php '$INSTALL_DIR/menu.php' createdbdragon || true
    php '$INSTALL_DIR/menu.php' createv2table || true
    php '$INSTALL_DIR/dbconvert.php' convertdba || true
    php '$INSTALL_DIR/dbconvert.php' finishdba || true
    php '$INSTALL_DIR/menu.php' deletecone ws || true
    php '$INSTALL_DIR/menu.php' createXrayTable || true
  "

  run_step_shell 97 "Ajustando SSH" "
    grep -q '^HostKeyAlgorithms +ssh-rsa' /etc/ssh/sshd_config 2>/dev/null || echo 'HostKeyAlgorithms +ssh-rsa' >> /etc/ssh/sshd_config
    grep -q '^PubkeyAcceptedKeyTypes +ssh-rsa' /etc/ssh/sshd_config 2>/dev/null || echo 'PubkeyAcceptedKeyTypes +ssh-rsa' >> /etc/ssh/sshd_config
    systemctl restart ssh 2>/dev/null || systemctl restart sshd 2>/dev/null || true
  "

  run_step_shell 99 "Reiniciando serviços DragonSSH" "
    screen -X -S proxydragon quit 2>/dev/null || true
    screen -X -S openvpn quit 2>/dev/null || true
    screen -X -S badvpn quit 2>/dev/null || true
    screen -X -S checkuser quit 2>/dev/null || true
    screen -X -S napster quit 2>/dev/null || true
    screen -X -S limiter quit 2>/dev/null || true
    screen -X -S botulek quit 2>/dev/null || true
    php '$INSTALL_DIR/menu.php' autostart || true
  "

  progress_done
  msg "Script instalado. Use o comando: menu"
  echo "Log: $LOG_FILE"
}

main "$@"
