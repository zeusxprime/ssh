<?php

function printpass()
{
    global $db_host, $db_port, $db_name, $db_user, $db_pass;


$conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");

    $query = "SELECT id,usr, pass FROM users";

    $result = pg_query($conn, $query);

    while ($row = pg_fetch_assoc($result)) {
        $id = $row['id'];
        $user = $row['usr'];
        $pass = $row['pass'];
        echo "ID: $id | " . $user . " | " . $pass . "\n";
    }

    pg_close($conn);
}


function uppass($username, $pass)
{
    $check = shell_exec("php /opt/DragonCore/menu.php printvalid2 $username");
    if ($check != null) {
        shell_exec("usermod -p $(openssl passwd -1 $pass) $username");
        shell_exec("php /opt/DragonCore/menu.php uppass2 $username $pass");
    } else {
        echo "error user dont exist\n";
    }
}

function printpass2($user)
{
    global $db_host, $db_port, $db_name, $db_user, $db_pass;


$conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");

    $query = "SELECT pass FROM users WHERE usr=$1";
    $params = array($user);

    $result = pg_query_params($conn, $query, $params);

    while ($row = pg_fetch_assoc($result)) {
        $pass = $row['pass'];
        echo $user . " | " . $pass . "\n";
    }

    pg_close($conn);
}


function uppass2($username, $pass)
{
    global $db_host, $db_port, $db_name, $db_user, $db_pass;


$conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");

    $query = "UPDATE users SET pass = $1 WHERE usr = $2";
    $params = array($pass, $username);

    $result = pg_query_params($conn, $query, $params);

    pg_close($conn);
}


function uppassnew($id, $pass)
{
    global $db_host, $db_port, $db_name, $db_user, $db_pass;


$conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");

    $query = "SELECT usr FROM users where id = $1";

    $result = pg_query_params($conn, $query, array($id));

    while ($row = pg_fetch_assoc($result)) {
        $user = $row['usr'];
        shell_exec("php /opt/DragonCore/menu.php uppass $user $pass");
        shell_exec("php /opt/DragonCore/menu.php uppass2 $user $pass");
    }

    pg_close($conn);
}



function printpassnew($id)
{
    global $db_host, $db_port, $db_name, $db_user, $db_pass;


$conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");

    $query = "SELECT usr,pass FROM users WHERE id=$1";
    $params = array($id);

    $result = pg_query_params($conn, $query, $params);

    while ($row = pg_fetch_assoc($result)) {
        $pass = $row['pass'];
        $user = $row['usr'];
        echo $user . " | " . $pass . "\n";
    }

    pg_close($conn);
}