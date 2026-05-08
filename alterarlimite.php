<?php
require_once '/opt/DragonCore/config.php';

function printlim()
{
    global $db_host, $db_port, $db_name, $db_user, $db_pass;


$conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");

    $query = "SELECT * FROM users";

    $result = pg_query($conn, $query);

    while ($row = pg_fetch_assoc($result)) {
        $user = $row['usr'];
        $lim = $row['limi'];
        echo $user . " | " . $lim . "\n";
    }

    pg_close($conn);
}


function uplimit($username, $newLimit)
{
    $check = shell_exec("php /opt/DragonCore/menu.php printvalid2 $username");
    if ($check != null) {
        if (is_numeric($newLimit)) {
            global $db_host, $db_port, $db_name, $db_user, $db_pass;


$conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");

            $query = "UPDATE users SET limi = $1 WHERE usr = $2";
            $params = array($newLimit, $username);

            $result = pg_query_params($conn, $query, $params);

            pg_close($conn);
        } else {
            echo "error invalid limit\n";
        }
    } else {
        echo "error user dont exist\n";
    }
}


function printlim2($user)
{
    global $db_host, $db_port, $db_name, $db_user, $db_pass;


$conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");

    $query = "SELECT limi FROM users WHERE usr=$1";
    $params = array($user);

    $result = pg_query_params($conn, $query, $params);

    while ($row = pg_fetch_assoc($result)) {
        $lim = $row['limi'];
        echo $user . " | " . $lim . "\n";
    }

    pg_close($conn);
}



function uplimitnew($id, $limit)
{
    global $db_host, $db_port, $db_name, $db_user, $db_pass;


$conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");

    $query = "SELECT usr FROM users where id = $1";

    $result = pg_query_params($conn, $query, array($id));

    while ($row = pg_fetch_assoc($result)) {
        $user = $row['usr'];
        shell_exec("php /opt/DragonCore/menu.php uplimit $user $limit");
    }

    pg_close($conn);
}



function printlim2new($id)
{
    global $db_host, $db_port, $db_name, $db_user, $db_pass;


$conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");

    $query = "SELECT * FROM users WHERE id=$1";
    $params = array($id);

    $result = pg_query_params($conn, $query, $params);

    while ($row = pg_fetch_assoc($result)) {
        $lim = $row['limi'];
        $user = $row['usr'];
        echo $user . " | " . $lim . "\n";
    }

    pg_close($conn);
}


function printlimnew()
{
    global $db_host, $db_port, $db_name, $db_user, $db_pass;


$conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");

    $query = "SELECT * FROM users";

    $result = pg_query($conn, $query);

    while ($row = pg_fetch_assoc($result)) {
        $user = $row['usr'];
        $lim = $row['limi'];
        $id = $row['id'];
        echo "ID: $id | " . $user . " | " . $lim . "\n";
    }

    pg_close($conn);
}