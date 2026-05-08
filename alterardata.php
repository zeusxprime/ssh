<?php
require_once '/opt/DragonCore/config.php';


function alterardata($user, $data)
{
    $check = shell_exec("php /opt/DragonCore/menu.php printvalid2 $user");
    if ($check != null) {
        $final = date("Y-m-d", strtotime("+$data days"));
        shell_exec("chage -E $final $user");
    } else {
        echo "error user dont exist\n";
    }
}


function alterardatanew($id, $data)
{
    global $db_host, $db_port, $db_name, $db_user, $db_pass;
    $conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");

    $query = "SELECT usr FROM users where id = $1";

    $result = pg_query_params($conn, $query, array($id));

    while ($row = pg_fetch_assoc($result)) {
        $user = $row['usr'];
        shell_exec("php /opt/DragonCore/menu.php alterardata $user $data");
    }

    pg_close($conn);
}



function printvalidnew($id)
{
    global $db_host, $db_port, $db_name, $db_user, $db_pass;


    $conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");

    $query = "SELECT usr FROM users WHERE id=$1";
    $params = array($id);

    $result = pg_query_params($conn, $query, $params);

    while ($row = pg_fetch_assoc($result)) {
        $user = $row['usr'];
        $data = shell_exec("chage -l " . escapeshellarg($user) . " | grep -E \"Account expires\" | cut -d ' ' -f3-");
        echo "Nova validade: " . $user . " | " . $data . "\n";
    }

    pg_close($conn);
}


function printvalinew()
{
    global $db_host, $db_port, $db_name, $db_user, $db_pass;


    $conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");
    if (!$conn) {
        die("Connection failed: " . pg_last_error());
    }
    $query = "SELECT id,usr FROM users";

    $result = pg_query($conn, $query);

    if (!$result) {
        die("Query execution failed: " . pg_last_error());
    }

    while ($row = pg_fetch_assoc($result)) {
        $id = $row['id'];
        $user = $row['usr'];
        $data = shell_exec("chage -l " . escapeshellarg($user) . " | grep -E \"Account expires\" | cut -d ' ' -f3-");
        echo "ID: $id | " . $user . " | " . $data . "\n";
    }
    pg_close($conn);
}
