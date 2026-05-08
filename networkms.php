<?php


function formatBytes($bytes, $precision = 2)
{
    $units = array("bs", "kbs", "Mbs", "Gbs", "Tbs", "Pbs", "Ebs", "Zbs", "Ybs");
    $exp = floor(log($bytes, 1024)) | 0;
    return round($bytes / (1024 ** $exp), $precision) . $units[$exp];
}


function network()
{
    $dataFile = '/opt/DragonCore/network_data.json';
    if (file_exists($dataFile)) {
        $lastData = json_decode(file_get_contents($dataFile), true);
        $currentTime = time();
        if ($currentTime - $lastData['timestamp'] < 300) {
            return $lastData['result'];
        }
    }
    $before = shell_exec("grep eth0 /proc/net/dev");
    sleep(1);
    $after = shell_exec("grep eth0 /proc/net/dev");
    $beforeValues = preg_split('/\s+/', trim($before));
    $afterValues = preg_split('/\s+/', trim($after));
    $rxThroughput = ($afterValues[1] - $beforeValues[1]) * 8 / 1024;
    $txThroughput = ($afterValues[9] - $beforeValues[9]) * 8 / 1024;
    $result = "DW: " . formatBytes($rxThroughput * 1024) . " | " . "UP: " . formatBytes($txThroughput * 1024);
    $newData = [
        'timestamp' => time(),
        'result' => $result,
    ];
    file_put_contents($dataFile, json_encode($newData));

    return $result;
}
