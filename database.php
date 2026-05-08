<?php
require_once '/opt/DragonCore/config.php';

function createTable()
{
    global $db_host, $db_port, $db_name, $db_user, $db_pass;


$conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");

    if (!$conn) {
        echo "Failed to connect to PostgreSQL";
        exit;
    }

    $query = "CREATE TABLE IF NOT EXISTS users (
                ID SERIAL PRIMARY KEY,
                usr TEXT,
                pass TEXT,
                limi TEXT
              )";

    $result = pg_query($conn, $query);

    if (!$result) {
        echo "Error creating table: " . pg_last_error($conn);
    }

    pg_close($conn);
}

function retrieveDataAndCount()
{
    global $db_host, $db_port, $db_name, $db_user, $db_pass;


$conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");
    if (!$conn) {
        die("Connection failed: " . pg_last_error());
    }
    $query = "SELECT COUNT(*) as usr_count FROM users";
    $result = pg_query($conn, $query);
    if (!$result) {
        die("Query execution failed: " . pg_last_error());
    }
    $row = pg_fetch_assoc($result);
    pg_close($conn);
    if ($row) {
        $userCount = $row['usr_count'];
        return $userCount;
    } else {
        return "0";
    }
}


function insertData($user, $pass, $limi)
{
    global $db_host, $db_port, $db_name, $db_user, $db_pass;


$conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");
    if (!$conn) {
        die("Connection failed: " . pg_last_error());
    }
    $check_query = "SELECT COUNT(*) FROM users WHERE usr = $1";
    $check_result = pg_prepare($conn, "", $check_query);
    if (!$check_result) {
        die("Statement preparation failed: " . pg_last_error());
    }
    $check_result = pg_execute($conn, "", array($user));
    if (!$check_result) {
        die("Execution failed: " . pg_last_error());
    }
    $row = pg_fetch_row($check_result);
    $user_exists = intval($row[0]) > 0;

    if ($user_exists) {
        echo "Usuario já existe!.\n";
    } else {
        $insert_query = "INSERT INTO users (usr, pass, limi) VALUES ($1, $2, $3)";
        $insert_result = pg_prepare($conn, "", $insert_query);
        if (!$insert_result) {
            die("Statement preparation failed: " . pg_last_error());
        }
        $insert_result = pg_execute($conn, "", array($user, $pass, $limi));
        if (!$insert_result) {
            die("Execution failed: " . pg_last_error());
        }
        echo "Usuario Adicionado com sucesso! \n";
    }

    pg_close($conn);
}



function deleteData($user)
{
    global $db_host, $db_port, $db_name, $db_user, $db_pass;


$conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");
    if (!$conn) {
        die("Connection failed: " . pg_last_error());
    }
    $query = "DELETE FROM users WHERE usr = $1";
    $result = pg_prepare($conn, "", $query);
    if (!$result) {
        die("Statement preparation failed: " . pg_last_error());
    }
    $result = pg_execute($conn, "", array($user));
    if (!$result) {
        die("Execution failed: " . pg_last_error());
    }
    pg_close($conn);
}


function printusers()
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
        echo "ID: $id |" . $user . "\n";
    }
    pg_close($conn);
}


function printvalid()
{
    global $db_host, $db_port, $db_name, $db_user, $db_pass;


$conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");
    if (!$conn) {
        die("Connection failed: " . pg_last_error());
    }
    $query = "SELECT usr FROM users";

    $result = pg_query($conn, $query);

    if (!$result) {
        die("Query execution failed: " . pg_last_error());
    }

    while ($row = pg_fetch_assoc($result)) {
        $user = $row['usr'];
        $data = shell_exec("chage -l " . escapeshellarg($user) . " | grep -E \"Account expires\" | cut -d ' ' -f3-");
        echo $user . " | " . $data . "\n";
    }
    pg_close($conn);
}


function printvalid2($user)
{
    global $db_host, $db_port, $db_name, $db_user, $db_pass;


$conn = pg_connect("host=localhost dbname=dragoncore user=$db_user password=$db_pass");
    if (!$conn) {
        die("Connection failed: " . pg_last_error());
    }
    $safeUser = pg_escape_string($conn, $user);
    $query = "SELECT usr FROM users WHERE usr='$safeUser'";
    $result = pg_query($conn, $query);
    if (!$result) {
        die("Query execution failed: " . pg_last_error());
    }
    while ($row = pg_fetch_assoc($result)) {
        $user = $row['usr'];
        $data = shell_exec("chage -l " . escapeshellarg($user) . " | grep -E \"Account expires\" | cut -d ' ' -f3-");
        echo "Nova validade: " . $user . " | " . $data . "\n";
    }
    pg_close($conn);
}


