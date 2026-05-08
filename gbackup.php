<?php
require_once '/opt/DragonCore/config.php';
function createbackup()
{
    global $db_host, $db_port, $db_name, $db_user, $db_pass;
    $db = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");
    $tableData = array();
    $tables = array("users", "conestart");

    foreach ($tables as $table) {
        if ($table == "users") {
            $query = "SELECT * FROM $table";
            $result = pg_query($db, $query);
            $date = '';
            $tableData[$table] = array();

            if (!$result) {
                exit;
            }
            while ($row = pg_fetch_assoc($result)) {
                $username = $row['usr'];
                $expiration_date = shell_exec("chage -l $username | grep -E \"Account expires\" | cut -d ' ' -f3- | xargs -I {} date -d \"{}\" +%s");
                $row['expiration_date'] = trim($expiration_date);
                $tableData[$table][] = $row;
            }

        } else {
            $query = "SELECT * FROM $table";
            $result = pg_query($db, $query);
            if (!$result) {
                continue;
            }
            $tableData[$table] = array();
            while ($row = pg_fetch_assoc($result)) {
                $tableData[$table][] = $row;
            }
        }
    }
    pg_close($db);
    $filename = "/root/dragoncoressh.json";
    $file = fopen($filename, 'w');
    fwrite($file, json_encode($tableData));
    fclose($file);

    echo "Backup Concluido , arquivo $filename\n";
}

function restorebackupuser()
{
    $jsonString = file_get_contents('/root/dragoncoressh.json');
    $tableData = json_decode($jsonString, true);

    foreach ($tableData['users'] as $user) {
        $timestamp = $user['expiration_date'];
        $username = $user['usr'];
        $password = $user['pass'];
        $limite = $user['limi'];
        $dateString = date("Y-m-d", $timestamp);
        $passw = shell_exec('perl -e \'print crypt($ARGV[0], "password")\' ' . escapeshellarg($password));
        shell_exec("useradd -e $dateString -M -s /bin/false -p $passw $username");
        shell_exec("php /opt/DragonCore/menu.php insertData $username $password $limite");
        echo "Usuario: $username Importado com sucesso!\n";
    }
}

function restorebackupconnect()
{
    $jsonString = file_get_contents('/root/dragoncoressh.json');
    $tableData = json_decode($jsonString, true);

    foreach ($tableData['conestart'] as $conn) {
        $cone = $conn['cone'];
        $porta = $conn['porta'];
        $banner = $conn['banner'];
        $token = $conn['token'];
        $tipo = $conn['tipo'];
        shell_exec("php /opt/DragonCore/menu.php deletecone $cone");
        shell_exec("screen -X -S proxydragon quit");
        shell_exec("screen -X -S openvpn quit");
        shell_exec("screen -X -S badvpn quit");
        shell_exec("screen -X -S checkuser quit");
        shell_exec("screen -X -S napster quit");
        shell_exec("screen -X -S limiter quit");
        shell_exec("php /opt/DragonCore/menu.php incone $cone $porta $banner, $token, $tipo");

    }
    shell_exec("php /opt/DragonCore/menu.php autostart");
    echo "Conexoes Importadas com sucesso!\n";
}

function ckbkdragon()
{

    $filename = "/root/dragoncoressh.json";
    if (file_exists($filename)) {
        echo "OK";
    } else {
        echo "NOTOK";
    }
}


?>