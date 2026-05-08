<?php

require_once '/opt/DragonCore/config.php';

function createXrayTable()
{
    global $db_user, $db_pass;
    $conn = pg_connect("host=localhost dbname=dragoncore user={$db_user} password={$db_pass}");
    if (!$conn) {
        echo "Failed to connect to PostgreSQL\n";
        return;
    }
    $query = "CREATE TABLE IF NOT EXISTS xray (
        id       SERIAL PRIMARY KEY,
        uuid     TEXT,
        nick     TEXT,
        expiry   DATE,
        protocol TEXT
    );";
    $result = pg_query($conn, $query);
    if (!$result) {
        echo "Error creating Xray table: " . pg_last_error($conn) . "\n";
    }
    pg_close($conn);
}

function xrayConfigPath(): string
{
    return '/usr/local/etc/xray/config.json';
}

function xrayEnsureConfigDir(): void
{
    $dir = dirname(xrayConfigPath());
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

function isXrayInstalled(): bool
{
    $paths = [
        '/usr/local/bin/xray',
        '/usr/bin/xray'
    ];
    foreach ($paths as $p) {
        if (file_exists($p)) {
            return true;
        }
    }
    return false;
}

function xrayInstall()
{
    if (isXrayInstalled()) {
        echo "echo 'Xray ja esta instalado';\n";
        return;
    }

    echo 'bash -c "$(curl -L https://github.com/XTLS/Xray-install/raw/main/install-release.sh)" @ install' . "\n";
}

function xrayRemove()
{
    if (!isXrayInstalled()) {
        echo "echo 'Xray nao esta instalado';\n";
        return;
    }

    echo 'bash -c "$(curl -L https://github.com/XTLS/Xray-install/raw/main/install-release.sh)" @ remove' . "\n";
}

function xrayGetProtocols()
{
    global $db_user, $db_pass;
    $protocols = [];
    $conn = pg_connect("host=localhost dbname=dragoncore user={$db_user} password={$db_pass}");
    if ($conn) {
        $res = pg_query($conn, 'SELECT DISTINCT protocol FROM xray ORDER BY protocol');
        while ($row = pg_fetch_assoc($res)) {
            $protocols[] = $row['protocol'];
        }
        pg_close($conn);
    }
    echo implode(', ', $protocols);
}

function xrayListSimple()
{
    global $db_user, $db_pass;
    $conn = pg_connect("host=localhost dbname=dragoncore user={$db_user} password={$db_pass}");
    if ($conn) {
        $res = pg_query($conn, 'SELECT uuid, expiry FROM xray ORDER BY id');
        while ($row = pg_fetch_assoc($res)) {
            echo $row['uuid'] . '  ' . $row['expiry'] . "\n";
        }
        pg_close($conn);
    }
}

function xrayChoice(): string
{
    return isXrayInstalled() ? 'Remover' : 'Instalar';
}

function xrayUuid(): string
{
    $uuid = trim((string)@shell_exec('uuidgen'));
    if ($uuid !== '') {
        return $uuid;
    }

    if (function_exists('random_bytes')) {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    return md5(uniqid((string)mt_rand(), true));
}

function xrayGenerateConfig($port, string $network = 'xhttp')
{
    $configPath = xrayConfigPath();
    xrayEnsureConfigDir();

    $port = (int)$port;
    if ($port <= 0 || $port > 65535) {
        $port = 443;
    }

    $network = strtolower($network);
    if (!in_array($network, ['xhttp', 'ws', 'grpc', 'tcp'], true)) {
        $network = 'xhttp';
    }


    $apiInbound = [
        'tag'      => 'api',
        'port'     => 1080,
        'protocol' => 'dokodemo-door',
        'settings' => [
            'address' => '127.0.0.1',
        ],
        'listen'   => '127.0.0.1',
    ];


    $streamSettings = [
        'network' => $network,
    ];

    if ($network === 'xhttp') {
        $streamSettings['security'] = 'tls';
        $streamSettings['tlsSettings'] = [
            'certificates' => [[
                'certificateFile' => '/opt/DragonCoreSSL/fullchain.pem',
                'keyFile'        => '/opt/DragonCoreSSL/privkey.pem',
            ]],
            'alpn' => ['http/1.1'],
        ];
        $streamSettings['xhttpSettings'] = [
            'headers'              => null,
            'host'                 => '',
            'mode'                 => '',
            'noSSEHeader'          => false,
            'path'                 => '/',
            'scMaxBufferedPosts'   => 30,
            'scMaxEachPostBytes'   => '1000000',
            'scStreamUpServerSecs' => '20-80',
            'xPaddingBytes'        => '100-1000',
        ];
    } elseif ($network === 'ws') {
        $streamSettings['security'] = 'none';
        $streamSettings['wsSettings'] = [
            'acceptProxyProtocol' => false,
            'headers'             => (object)[],
            'heartbeatPeriod'     => 0,
            'host'                => '',
            'path'                => '/',
        ];
    } elseif ($network === 'grpc') {
        $streamSettings['security'] = 'none';
        $streamSettings['grpcSettings'] = [
            'serviceName'           => 'vlessgrpc',
            'multiMode'             => false,
            'idle_timeout'          => 60,
            'permit_without_stream' => false,
        ];
    } else {
        $streamSettings['security'] = 'none';
    }


    $dragonInbound = [
        'tag'      => 'inbound-dragoncore',
        'port'     => $port,
        'protocol' => 'vless',
        'settings' => [
            'clients'    => [],
            'decryption' => 'none',
            'fallbacks'  => [],
        ],
        'streamSettings' => $streamSettings,
    ];

    $config = [
        'api' => [
            'services' => [
                'HandlerService',
                'LoggerService',
                'StatsService',
            ],
            'tag' => 'api',
        ],
        'burstObservatory' => null,
        'dns'              => null,
        'fakedns'          => null,
        'inbounds'         => [
            $apiInbound,
            $dragonInbound,
        ],
        'observatory' => null,
        'outbounds' => [
            [
                'protocol' => 'freedom',
                'settings' => (object)[],
                'tag'      => 'direct',
            ],
            [
                'protocol' => 'blackhole',
                'settings' => (object)[],
                'tag'      => 'blocked',
            ],
        ],
        'reverse' => null,
        'routing' => [
            'domainStrategy' => 'AsIs',
            'rules' => [
                [
                    'inboundTag'  => ['api'],
                    'outboundTag' => 'api',
                    'type'        => 'field',
                ],
                [
                    'ip'          => ['geoip:private'],
                    'outboundTag' => 'blocked',
                    'type'        => 'field',
                ],
                [
                    'outboundTag' => 'blocked',
                    'protocol'    => ['bittorrent'],
                    'type'        => 'field',
                ],
            ],
        ],
        'stats'     => (object)[],
        'transport' => null,
    ];

    file_put_contents(
        $configPath,
        json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    @shell_exec('systemctl restart xray');
    echo "Config Xray gerado em {$configPath} (porta {$port}, protocolo {$network})\n";
}

function xrayFixEmptyObjects(array &$config): void
{

    if (isset($config['stats']) && is_array($config['stats']) && empty($config['stats'])) {
        $config['stats'] = (object)[];
    }


    if (isset($config['outbounds']) && is_array($config['outbounds'])) {
        foreach ($config['outbounds'] as &$out) {
            if (isset($out['settings']) && is_array($out['settings']) && empty($out['settings'])) {
                $out['settings'] = (object)[];
            }
        }
        unset($out);
    }
}

function xrayAddUser(string $nick, string $protocol = 'xhttp')
{
    global $db_user, $db_pass;

    $uuid   = xrayUuid();
    $expiry = date('Y-m-d', strtotime('+30 days'));

    $configPath = xrayConfigPath();
    if (!file_exists($configPath)) {
        echo "Arquivo de configuracao {$configPath} nao encontrado. Gere o config Xray primeiro.\n";
        return;
    }

    $config = json_decode(file_get_contents($configPath), true);
    if (!is_array($config)) {
        echo "Falha ao ler/decodificar {$configPath}.\n";
        return;
    }


    $inboundIndex = null;
    if (isset($config['inbounds']) && is_array($config['inbounds'])) {
        foreach ($config['inbounds'] as $idx => $in) {
            if (isset($in['tag']) && $in['tag'] === 'inbound-dragoncore') {
                $inboundIndex = $idx;
                break;
            }
        }
    }

    if ($inboundIndex === null) {
        echo "Inbound 'inbound-dragoncore' nao encontrado no config Xray.\n";
        return;
    }

    if (
        !isset($config['inbounds'][$inboundIndex]['settings']['clients']) ||
        !is_array($config['inbounds'][$inboundIndex]['settings']['clients'])
    ) {
        $config['inbounds'][$inboundIndex]['settings']['clients'] = [];
    }

    $client = [
        'id'    => $uuid,
        'email' => $nick,
        'level' => 0,
    ];
    $config['inbounds'][$inboundIndex]['settings']['clients'][] = $client;

    xrayFixEmptyObjects($config);
    file_put_contents(
        $configPath,
        json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );


    $conn = pg_connect("host=localhost dbname=dragoncore user={$db_user} password={$db_pass}");
    if ($conn) {
        $query = "INSERT INTO xray (uuid, nick, expiry, protocol) VALUES ($1,$2,$3,$4)";
        $stmt  = pg_prepare($conn, '', $query);
        pg_execute($conn, '', [$uuid, $nick, $expiry, $protocol]);
        pg_close($conn);
    }

    @shell_exec('systemctl restart xray');

    $domain = trim((string)shell_exec('hostname -I | awk "{print $1}"'));
    if ($domain === '') {
        $domain = '127.0.0.1';
    }
    $port = $config['inbounds'][$inboundIndex]['port'];
    $uri  = "vless://{$uuid}@{$domain}:{$port}#{$nick}";

    echo "UUID Criado: {$uuid}\n{$uri}\n";
}

function xrayRemoveUser($identifier)
{
    global $db_user, $db_pass;
    $uuid = null;

    if (is_numeric($identifier)) {
        $conn = pg_connect("host=localhost dbname=dragoncore user={$db_user} password={$db_pass}");
        if ($conn) {
            $res = pg_query_params($conn, 'SELECT uuid FROM xray WHERE id = $1', [$identifier]);
            if ($res && ($row = pg_fetch_assoc($res))) {
                $uuid = $row['uuid'];
            }
            pg_close($conn);
        }
    } else {
        $uuid = $identifier;
    }

    if (!$uuid) {
        echo "UUID nao encontrado\n";
        return;
    }

    $configPath = xrayConfigPath();
    if (file_exists($configPath)) {
        $config = json_decode(file_get_contents($configPath), true);
        if (isset($config['inbounds']) && is_array($config['inbounds'])) {
            $inboundIndex = null;
            foreach ($config['inbounds'] as $idx => $in) {
                if (isset($in['tag']) && $in['tag'] === 'inbound-dragoncore') {
                    $inboundIndex = $idx;
                    break;
                }
            }

            if (
                $inboundIndex !== null &&
                isset($config['inbounds'][$inboundIndex]['settings']['clients']) &&
                is_array($config['inbounds'][$inboundIndex]['settings']['clients'])
            ) {

                $clients = &$config['inbounds'][$inboundIndex]['settings']['clients'];
                foreach ($clients as $index => $cli) {
                    if (isset($cli['id']) && $cli['id'] === $uuid) {
                        array_splice($clients, $index, 1);
                        break;
                    }
                }

                xrayFixEmptyObjects($config);
                file_put_contents(
                    $configPath,
                    json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                );
            }
        }
    }

    $conn = pg_connect("host=localhost dbname=dragoncore user={$db_user} password={$db_pass}");
    if ($conn) {
        pg_query_params($conn, 'DELETE FROM xray WHERE uuid = $1', [$uuid]);
        pg_close($conn);
    }

    @shell_exec('systemctl restart xray');
    echo "Usuario removido: {$uuid}\n";
}

function xrayListUsers()
{
    global $db_user, $db_pass;
    $conn = pg_connect("host=localhost dbname=dragoncore user={$db_user} password={$db_pass}");
    if (!$conn) {
        echo "Falha ao conectar ao banco de dados\n";
        return;
    }
    $res = pg_query($conn, 'SELECT id, uuid, nick, expiry, protocol FROM xray ORDER BY id');
    while ($row = pg_fetch_assoc($res)) {
        echo "ID: {$row['id']} | NICK: {$row['nick']} | UUID: {$row['uuid']} | EXPIRA: {$row['expiry']} | PROTO: {$row['protocol']}\n";
    }
    pg_close($conn);
}

function xrayCert(string $domain)
{
    $sslDir  = '/opt/DragonCoreSSL';
    $keyFile = $sslDir . '/privkey.pem';
    $crtFile = $sslDir . '/fullchain.pem';

    if (!is_dir($sslDir)) {
        mkdir($sslDir, 0700, true);
    }

    $subject = '/C=BR/ST=SP/L=SaoPaulo/O=DragonCore/OU=VPN/CN=' . $domain;

    $cmd  = 'openssl req -x509 -nodes -newkey rsa:2048 '
        . '-days 9999 '
        . '-subj ' . escapeshellarg($subject) . ' '
        . '-keyout ' . escapeshellarg($keyFile) . ' '
        . '-out ' . escapeshellarg($crtFile) . ' 2>/dev/null';

    @shell_exec($cmd);

    if (file_exists($keyFile) && file_exists($crtFile)) {
        echo "Certificado TLS autoassinado (9999 dias) gerado para {$domain}\n";
    } else {
        echo "Falha ao gerar certificado autoassinado para {$domain}\n";
    }
}

function xrayPurgeExpired()
{
    global $db_user, $db_pass;
    $today = date('Y-m-d');
    $conn = pg_connect("host=localhost dbname=dragoncore user={$db_user} password={$db_pass}");
    if ($conn) {
        $res = pg_query_params($conn, 'SELECT uuid FROM xray WHERE expiry < $1', [$today]);
        while ($row = pg_fetch_assoc($res)) {
            xrayRemoveUser($row['uuid']);
        }
        pg_close($conn);
    }
}

function xrayInfo()
{
    $binary = isXrayInstalled() ? '/usr/local/bin/xray' : 'xray';
    $version = trim((string)shell_exec($binary . ' -version 2>&1'));

    global $db_user, $db_pass;
    $conn  = pg_connect("host=localhost dbname=dragoncore user={$db_user} password={$db_pass}");
    $count = 0;
    $protocols = [];
    if ($conn) {
        $res = pg_query($conn, 'SELECT COUNT(*) AS c FROM xray');
        if ($row = pg_fetch_assoc($res)) {
            $count = $row['c'];
        }
        $res2 = pg_query($conn, 'SELECT DISTINCT protocol FROM xray');
        while ($r = pg_fetch_assoc($res2)) {
            $protocols[] = $r['protocol'];
        }
        pg_close($conn);
    }

    echo "Xray Versao: {$version}\n";
    echo "Usuarios cadastrados: {$count}\n";
    echo "Protocolos em uso: " . implode(', ', $protocols) . "\n";
}
