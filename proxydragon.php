<?php
require_once '/opt/DragonCore/config.php';




function dbdragon()
{
    $cpuInfo = shell_exec('cat /proc/cpuinfo | grep -m 1 "Serial"');
    $macInfo = shell_exec('cat /sys/class/net/$(ls /sys/class/net | head -n 1)/address');
    $macAddress = trim($macInfo);
    $machineInfo = $cpuInfo . $macAddress;
    return hash('sha256', $machineInfo);

}


function dbdragon2()
{
    $cpuInfo = shell_exec('cat /proc/cpuinfo | grep -m 1 "Serial"');
    $macInfo = shell_exec('cat /sys/class/net/$(ls /sys/class/net | head -n 1)/address');
    $macAddress = trim($macInfo);
    $machineInfo = $cpuInfo . $macAddress;
    echo hash('sha256', $machineInfo);

}


function createdbdragon()
{
    $hash = dbdragon();
    global $db_host, $db_port, $db_name, $db_user, $db_pass;


$conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");

    if (!$conn) {
        echo "Failed to connect to PostgreSQL";
        exit;
    }

    $query = "CREATE TABLE IF NOT EXISTS proxydr4 (
                ID SERIAL PRIMARY KEY,
                cake TEXT
              )";

    $result = pg_query($conn, $query);

    if (!$result) {
        echo "Error creating table: " . pg_last_error($conn);
    }
    $checkQuery = "SELECT 1 FROM proxydr4 WHERE ID = 1";
    $checkResult = pg_query($conn, $checkQuery);

    if (!$checkResult) {
        pg_close($conn);
        return;
    }

    $row = pg_fetch_assoc($checkResult);
    if (!$row) {
        $url = "https://raw.githubusercontent.com/Penguinehis/proxydragon/main/key";
        $content = file_get_contents($url);
        if ($content === false) {
            echo "OFF";
        } else {
            if (trim($content) === "mZyx3VuEclU4XWd8EFUnGpW9jQOiSqds5YtZfLyAMXNFucR5rF6FfTHoaYJ1hbYA6H7JObE1TfoWriTgfeTowljbF6lPJ9TS0Pe77FiIO4A3mJsa9VKHeoI5F8NGXv0Yoy7srN6WexkGkpDfciEBux5M9W50ucVgQsJKnYaZREuBYxHnq5wckoV0I4HCgQIPUULL95fwCuamu6DnsSr9EldgveWLf7VhkgxUjBdHYTCbAYcBLib9ISwPiD50BAYik82MA99ZbtLeyzTJN5CDFxDVPnNaBAOFAKeUXfIbft4w") {
                $insertQuery = "INSERT INTO proxydr4 (ID, cake) VALUES (1, '$hash')";
                $insertResult = pg_query($conn, $insertQuery);
                if (!$insertResult) {
                    echo "Error inserting default row: " . pg_last_error($conn);
                }
            } else {
                echo "OFF";
            }
        }
    }

    pg_close($conn);
}


function dragon()
{
    $lima = dbdragon();
    $currentDateTime = date('Y-m-d H:i');
    $generatedHash = hash('sha256', $currentDateTime);

    /*if (dragonprhash($lima)) {*/
    echo $generatedHash;
    /*} else {
        echo "ERROR";
    }*/

}


function dragonprhash($hash)
{
    $dragona = dbdragon();
    global $db_host, $db_port, $db_name, $db_user, $db_pass;


$conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");
    if (!$conn) {
        die("Connection failed: " . pg_last_error());
    }
    $query = "SELECT cake FROM proxydr4 WHERE ID = 1";
    $result = pg_query($conn, $query);
    if (!$result) {
        die("Query execution failed: " . pg_last_error());
    }
    while ($row = pg_fetch_assoc($result)) {
        $hash = $row['cake'];
        if ($hash == $dragona) {
            return true;
        } else {
            del232409875892309ete();
            return false;
        }
    }
    pg_close($conn);
}


function del232409875892309ete()
{
    $dragona = dbdragon();
    global $db_host, $db_port, $db_name, $db_user, $db_pass;


$conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");
    if (!$conn) {
        die("Connection failed: " . pg_last_error());
    }
    $query = "DELETE FROM proxydr4 WHERE ID = 1";
    $result = pg_query($conn, $query);
    if (!$result) {
        die("Query execution failed: " . pg_last_error());
    } else {
        createdbdragon();
    }
    pg_close($conn);
}


function dragonprhash2()
{
    $dragona = dbdragon();
    global $db_host, $db_port, $db_name, $db_user, $db_pass;


$conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");
    if (!$conn) {
        die("Connection failed: " . pg_last_error());
    }
    $query = "SELECT cake FROM proxydr4 WHERE ID = 1";
    $result = pg_query($conn, $query);
    if (!$result) {
        die("Query execution failed: " . pg_last_error());
    }
    while ($row = pg_fetch_assoc($result)) {
        $hash = $row['cake'];
        if ($hash == $dragona) {
            echo $hash;
        } else {
            return false;
        }
    }
    pg_close($conn);
}


function pdragon($port)
{
    $onoff = shell_exec('screen -list | grep -q proxydragon && echo 1 || echo 0');
    if ($onoff == 1) {
        shell_exec('screen -X -S proxydragon quit');
        shell_exec("screen -dmS proxydragon bash -c 'while true; do ulimit -n 999999 && /opt/DragonCore/ProxyDragon -port $port; done'");
        echo "Proxy Dragon Online na Porta: $port\n";
    } else {
        deletecone("proxydragon");
        incone("proxydragon", $port, "null", "null", "null");
        shell_exec("screen -dmS proxydragon bash -c 'while true; do ulimit -n 999999 && /opt/DragonCore/ProxyDragon -port $port; done'");
        echo "Proxy Dragon Online na Porta: $port\n";
    }
}


function pdragonon()
{
    $onoff = shell_exec('screen -list | grep -q proxydragon && echo 1 || echo 0');
    if ($onoff == 1) {
        echo "ON";

    } else {
        echo "OFF";
    }
}


function pdragonstop()
{
    deletecone("proxydragon");
    shell_exec("screen -X -S proxydragon quit");
    echo "Proxy Dragon OFF\n";

}