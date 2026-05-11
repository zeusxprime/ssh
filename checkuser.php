<?php


function svcheckuser()
{
    $host = '0.0.0.0';
    $port = 2095;
    $socket = stream_socket_server("tcp://{$host}:{$port}", $errno, $errstr);

    if (!$socket) {
        die("Error: {$errstr} ({$errno})\n");
    }

    echo "CheckUser rodando em http://{$host}:{$port}/\n";

    while ($conn = stream_socket_accept($socket, -1)) {
        $request = fread($conn, 4096);
        $method = '';
        $requestData = '';
        if (preg_match('/^([A-Z]+) (.*) HTTP/', $request, $matches)) {
            $method = $matches[1];
            $requestData = $matches[2];
        }
        $method = trim($method);
        $requestData = trim($requestData);
        #echo $requestData . "\n";

        if ($method === 'GET') {
            $urlPath = parse_url($requestData, PHP_URL_PATH);
            #echo $urlPath . "\n";
            $app = '';
            $user = '';

            if (strpos($urlPath, '/check/') !== false) {
                $userPath = explode('/check/', $urlPath)[1];
                if (strpos($requestData, '?deviceId=') !== false) {
                    $app = "dt";
                    $user = explode("?deviceId=", $userPath)[0];
                } else {
                    $app = "gl";
                    $user = explode("?access_token", $userPath)[0];
                }

            } else if (str_contains($requestData, "?user=") !== false) {
                $app = "atx";
                $user = explode("?user=", $requestData)[1];
            } else {
                $app = "none";
                $user = "none";
            }

            $response = makeResponse($app, $user, '');

        } elseif ($method === 'POST') {
            $body = substr($request, strpos($request, "\r\n\r\n") + 4);
            #echo $method . "\n";
            #echo $body . "\n";
            $requestBody = '';
            $app = '';
            $user = '';
            $deviceId = '';

            $jsonData = json_decode($body, true);

            if ($jsonData !== null) {
                $requestBody = $jsonData;
                $app = 'conecta4g';
            } else {
                parse_str($body, $formData);
                $requestBody = $formData;
                $app = 'any';
            }

            $user = isset($requestBody['user']) ? $requestBody['user'] : (isset($requestBody['username']) ? $requestBody['username'] : '');
            $deviceId = isset($requestBody['deviceid']) ? $requestBody['deviceid'] : '';

            $response = makeResponse($app, $user, $deviceId);
            #echo makeResponse($app, $user, $deviceId);
        }
        $httpResponse = "HTTP/1.1 200 OK\r\n";
        $httpResponse .= "Content-Type: text/plain\r\n";
        $httpResponse .= "Content-Length: " . strlen($response) . "\r\n\r\n";
        # echo $response;
        fwrite($conn, $httpResponse . $response);
        fclose($conn);
    }

    fclose($socket);
}

function days_difference($date_string)
{
    $dateObject = DateTime::createFromFormat('M d, Y', $date_string);
    if (!$dateObject) {
        return false;
    }
    $today = new DateTime();
    $difference = $today->diff($dateObject);
    $days_difference = $difference->days;
    return $days_difference;
}

function format_date_for_anymod($date_string)
{
    $date = DateTime::createFromFormat('d/m/Y', $date_string);
    $formatted_date = $date->format('Y-m-d-');
    return $formatted_date;
}


function exact_expiration_from_dragon_teste($user)
{
    $safeUser = basename($user);
    $paths = [
        "/etc/DragonTeste/expirations/" . $safeUser . ".txt",
        "/etc/DragonTeste/expirations/" . $safeUser,
        "/etc/DragonTeste/" . $safeUser . ".exp"
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            $value = trim(file_get_contents($path));
            if ($value !== '') {
                $ts = strtotime($value);
                if ($ts !== false) {
                    return $ts;
                }
            }
        }
    }

    $db = "/etc/DragonTeste/expirations.db";
    if (file_exists($db)) {
        $lines = file($db, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = explode("|", $line, 2);
            if (count($parts) === 2 && trim($parts[0]) === $user) {
                $ts = strtotime(trim($parts[1]));
                if ($ts !== false) {
                    return $ts;
                }
            }
        }
    }

    return false;
}

function remaining_info_from_timestamp($expirationTs)
{
    $remainingSeconds = $expirationTs - time();
    if ($remainingSeconds <= 0) {
        return ["expirado", 0, 0, "expired"];
    }

    // Arredonda para baixo: no último minuto mostra 00h:00,
    // mas só expira quando $remainingSeconds <= 0.
    $totalMinutes = (int)floor($remainingSeconds / 60);
    if ($totalMinutes < 1440) {
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;
        return [sprintf("%02dh:%02d", $hours, $minutes), 0, $totalMinutes, "minutes"];
    }

    $days = intdiv($totalMinutes, 1440);
    if ($days <= 1) {
        return ["1 dia", 1, 1, "days"];
    }
    return [$days . " dias", $days, $days, "days"];
}

function makeResponse($app, $user, $deviceId)
{
    #echo $app . "\n";
    if ($user == null) {
        $responseData = [
            'ERROR' => "NULL"
        ];
        return json_encode($responseData, JSON_UNESCAPED_SLASHES);
    } elseif ($user == "none") {
        $responseData = [
            'ERROR' => "NULL"
        ];
        return json_encode($responseData, JSON_UNESCAPED_SLASHES);
    } elseif (stripos(shell_exec("grep -q \"^$user:\" /etc/passwd && echo \"1\" || echo \"2\""), "2") !== false) {
        $responseData = [
            'ERROR' => "NULL"
        ];
        return json_encode($responseData, JSON_UNESCAPED_SLASHES);
    } else {

        $responseData = null;

        $exactExpirationTs = exact_expiration_from_dragon_teste($user);
        if ($exactExpirationTs !== false) {
            $formattedExpirationDate = date("d/m/Y", $exactExpirationTs);
            list($expiresIn, $remainingDays, $remainingValue, $remainingUnit) = remaining_info_from_timestamp($exactExpirationTs);
        } else {
            $expirationDate = shell_exec("chage -l $user | grep -i co | awk -F: '{print $2}'");
            $expirationDate = trim($expirationDate);

            $formattedExpirationDateObj = date_create_from_format("M d, Y", $expirationDate);
            if ($formattedExpirationDateObj === false) {
                $responseData = [
                    'ERROR' => "NULL"
                ];
                return json_encode($responseData, JSON_UNESCAPED_SLASHES);
            }
            $formattedExpirationDate = date_format($formattedExpirationDateObj, "d/m/Y");
            $remainingDays = days_difference($expirationDate);
            $expiresIn = ($remainingDays == 1) ? "1 dia" : $remainingDays . " dias";
            $remainingValue = (int)$remainingDays;
            $remainingUnit = "days";
        }

        $limit = trim(shell_exec("php /opt/DragonCore/menu.php printlim | awk '/" . $user . "/ {print $3}'"));
        $connections = trim(shell_exec("ps -u $user  | grep sshd | wc -l"));

        if ($formattedExpirationDate !== false) {

            switch ($app) {
                case "conecta4g":
                    $responseData = [
                        'username' => $user,
                        'count_connection' => $connections,
                        'expiration_date' => $formattedExpirationDate,
                        'expiration_days' => strval($remainingDays),
                        'expires_in' => $expiresIn,
                        'expiration_remaining' => $expiresIn,
                        'expiration_display' => $expiresIn,
                        'expiration_value' => $remainingValue,
                        'expiration_unit' => $remainingUnit,
                        'limiter_user' => $limit
                    ];
                    $jsonData = json_encode($responseData, JSON_UNESCAPED_SLASHES);
                    break;
                case "gl":
                    $responseData = [
                        'username' => $user,
                        'count_connection' => $connections,
                        'expiration_date' => $formattedExpirationDate,
                        'expiration_days' => strval($remainingDays),
                        'expires_in' => $expiresIn,
                        'expiration_remaining' => $expiresIn,
                        'expiration_display' => $expiresIn,
                        'expiration_value' => $remainingValue,
                        'expiration_unit' => $remainingUnit,
                        'limit_connection' => $limit
                    ];
                    $jsonData = json_encode($responseData, JSON_UNESCAPED_SLASHES);
                    break;
                case "dt":
                    $responseData = [
                        'username' => $user,
                        'count_connections' => $connections,
                        'expiration_date' => $formattedExpirationDate,
                        'expiration_days' => $remainingDays,
                        'expires_in' => $expiresIn,
                        'expiration_remaining' => $expiresIn,
                        'expiration_display' => $expiresIn,
                        'expiration_value' => $remainingValue,
                        'expiration_unit' => $remainingUnit,
                        'limit_connections' => $limit,
                        'id' => 0
                    ];

                    $jsonData = json_encode($responseData, JSON_UNESCAPED_SLASHES);
                    $jsonData = str_replace(['"' . $responseData['count_connections'] . '"', '"' . $responseData['expiration_days'] . '"', '"' . $responseData['limit_connections'] . '"'], [$responseData['count_connections'], $responseData['expiration_days'], $responseData['limit_connections']], $jsonData);
                    break;
                case "any":
                    $responseData = [
                        'USER_ID' => $user,
                        'DEVICE' => $deviceId,
                        'is_active' => "true",
                        'expiration_date' => format_date_for_anymod($formattedExpirationDate),
                        'expiry' => $expiresIn,
                        'uuid' => "null"
                    ];
                    $jsonData = json_encode($responseData, JSON_UNESCAPED_SLASHES);
                    break;
                case "atx":
                    $responseData = [
                        'username' => $user,
                        'cont_conexao' => $connections,
                        'data_expiracao' => $formattedExpirationDate,
                        'dias_expiracao' => strval($remainingDays),
                        'expires_in' => $expiresIn,
                        'expiration_remaining' => $expiresIn,
                        'expiration_display' => $expiresIn,
                        'expiration_value' => $remainingValue,
                        'expiration_unit' => $remainingUnit,
                        'limite_user' => $limit
                    ];
                    $jsonData = json_encode($responseData, JSON_UNESCAPED_SLASHES);
                    break;
                default:
                    $responseData = "App not recognized";
            }

            return $jsonData;
        } else {
            $responseData = [
                'ERROR' => "NULL"
            ];
            return json_encode($responseData, JSON_UNESCAPED_SLASHES);
        }
    }
}
svcheckuser()
    ?>