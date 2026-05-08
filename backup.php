<?php


function checkbackup()
{
    $filePath = '/root/backup.vps';
    if (file_exists($filePath)) {
        echo "OK";
    } else {
        echo "ERROR";
    }
}


function unpck()
{
    mkdir('/root/temp');

    rename('/root/backup.vps', '/root/temp/backup.vps');

    chdir('/root/temp');

    exec('tar -xvf backup.vps', $output, $returnCode);

    unlink('backup.vps');
    if ($returnCode === 0) {
        echo "Extracao Concluida.\n";
    } else {
        echo "Falha ao extrair o backup.\n";
        // You can also print more details about the error using $output
        print_r($output);
    }
}


function paswd()
{

    inshadow();
    inpw();
    ingsshadow();
    ingroup();

}


function db()
{
    $filePath = '/root/temp/root/usuarios.db';
    $file = fopen($filePath, 'r');
    // Loop through each line in the file
    while (!feof($file)) {
        // Read a line from the file
        $line = fgets($file);

        // Split the line into user and limit
        @list($user, $limit) = explode(' ', trim($line));
        if (empty($user)) {

        } else {
            $filePath2 = "/root/temp/etc/SSHPlus/senha/$user";
            if (file_exists($filePath2)) {
                $file2 = fopen($filePath2, 'r');
                $pas = fgets($file2);
                $onelines = str_replace(array("\r", "\n"), '', $pas);
                insertData($user, $onelines, $limit);
            }
        }
    }

    // Close the file
    fclose($file);
}


function inpw()
{
    $backupFile = '/root/temp/etc/passwd';
    $systemPasswdFile = '/etc/passwd';
    $backupContent = file($backupFile, FILE_IGNORE_NEW_LINES);
    $systemPasswdContent = file($systemPasswdFile, FILE_IGNORE_NEW_LINES);
    foreach ($backupContent as $line) {
        if (!in_array($line, $systemPasswdContent)) {
            $systemPasswdContent[] = $line;
        }
    }
    file_put_contents($systemPasswdFile, implode("\n", $systemPasswdContent));
}

function inshadow()
{
    $backupFile = '/root/temp/etc/shadow';
    $systemPasswdFile = '/etc/shadow';
    $backupContent = file($backupFile, FILE_IGNORE_NEW_LINES);
    $systemPasswdContent = file($systemPasswdFile, FILE_IGNORE_NEW_LINES);
    foreach ($backupContent as $line) {
        if (!in_array($line, $systemPasswdContent)) {
            $systemPasswdContent[] = $line;
        }
    }
    file_put_contents($systemPasswdFile, implode("\n", $systemPasswdContent));
}

function ingroup()
{
    $backupFile = '/root/temp/etc/group';
    $systemPasswdFile = '/etc/group';
    $backupContent = file($backupFile, FILE_IGNORE_NEW_LINES);
    $systemPasswdContent = file($systemPasswdFile, FILE_IGNORE_NEW_LINES);
    foreach ($backupContent as $line) {
        if (!in_array($line, $systemPasswdContent)) {
            $systemPasswdContent[] = $line;
        }
    }
    file_put_contents($systemPasswdFile, implode("\n", $systemPasswdContent));
}

function ingsshadow()
{
    $backupFile = '/root/temp/etc/gshadow';
    $systemPasswdFile = '/etc/gshadow';
    $backupContent = file($backupFile, FILE_IGNORE_NEW_LINES);
    $systemPasswdContent = file($systemPasswdFile, FILE_IGNORE_NEW_LINES);
    foreach ($backupContent as $line) {
        if (!in_array($line, $systemPasswdContent)) {
            $systemPasswdContent[] = $line;
        }
    }
    file_put_contents($systemPasswdFile, implode("\n", $systemPasswdContent));
}
