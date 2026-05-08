<?php

function installpostgre()
{
    $dir        = '/opt/DragonCore';
    $configPath = $dir . '/config.php';

    $password  = null;
    $db_user   = 'dragoncore34';   // default user
    $db_name   = 'dragoncore';    // default db name

    // If config already exists, load existing password (and user/db if needed)
    if (file_exists($configPath)) {
        include $configPath; // defines $db_pass, $db_user, $db_name, etc.

        if (isset($db_pass)) {
            $password = $db_pass;
        }
        // if you ever want to override db_user/db_name from config, you can:
        // if (isset($db_user)) $db_user = $db_user;
        // if (isset($db_name)) $db_name = $db_name;
    }

    // If no password loaded from config, generate a new one
    if ($password === null) {
        if (function_exists('random_bytes')) {
            $bytes = random_bytes(16);
        } else {
            $bytes = openssl_random_pseudo_bytes(16);
        }
        $password = substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, 20);
    }

    echo 'sudo apt update' . "\n";
    echo 'sudo apt install postgresql postgresql-contrib -y' . "\n";
    echo 'sudo systemctl restart postgresql' . "\n";

    // DB + user + basic grants
    echo 'sudo -u postgres psql -c "CREATE DATABASE ' . $db_name . ';"' . "\n";
    echo 'sudo -u postgres psql -c "CREATE USER ' . $db_user . ' WITH PASSWORD \'' . $password . '\';"' . "\n";
    echo 'sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE ' . $db_name . ' TO ' . $db_user . ';"' . "\n";
    echo 'sudo -u postgres psql -d ' . $db_name . ' -c "GRANT USAGE, CREATE ON SCHEMA public TO ' . $db_user . ';"' . "\n";

    // OPTIONAL: make dragoncore2 the owner of everything that was owned by dragoncore in this DB
    echo 'sudo -u postgres psql -d ' . $db_name . ' -c "REASSIGN OWNED BY dragoncore TO ' . $db_user . ';"' . "\n";

    // Also give full privileges on all existing tables/sequences (nice to have)
    echo 'sudo -u postgres psql -d ' . $db_name . ' -c "GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO ' . $db_user . ';"' . "\n";
    echo 'sudo -u postgres psql -d ' . $db_name . ' -c "GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO ' . $db_user . ';"' . "\n";

    echo 'sudo systemctl restart postgresql' . "\n";

    // If config already existed, don't overwrite it
    if (file_exists($configPath)) {
        echo "Config file already exists at {$configPath}, not overwriting.\n";
        return;
    }

    // Ensure /opt/DragonCore exists
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
            fwrite(STDERR, "Failed to create directory: $dir\n");
            return;
        }
    }

    // Create config.php with variables (no return)
    $config = <<<PHP
<?php
\$db_host = '127.0.0.1';
\$db_port = '5432';
\$db_name = '{$db_name}';
\$db_user = '{$db_user}';
\$db_pass = '{$password}';
PHP;

    if (file_put_contents($configPath, $config) === false) {
        fwrite(STDERR, "Failed to write config file at {$configPath}\n");
    } else {
        echo "Config file created at {$configPath}\n";
    }
}

if ($argc < 2) {
    die("Use o MENU!\n");
}

$functionName = $argv[1];

if (function_exists($functionName)) {
    array_shift($argv);
    echo call_user_func_array($functionName, array_slice($argv, 1));
} else {
    echo "Function $functionName does not exist.\n";
}

?>
