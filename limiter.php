<?php



function clearScreen()
{
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        pclose(popen('cls', 'w'));
    } else {
        system('clear');
    }
}


function limiter()
{
    $output = shell_exec("ps aux | grep priv | grep Ss");
    $lines = explode("\n", trim($output));
    $userCounts = [];
    foreach ($lines as $line) {
        preg_match('/sshd:\s+([^[]+)/', $line, $matches);
        $username = end($matches);
        if (!empty($username)) {
            if (!isset($userCounts[$username])) {
                $userCounts[$username] = 1;
            } else {
                $userCounts[$username]++;
            }
        }
    }
    foreach ($userCounts as $username => $count) {
        $output2 = shell_exec("php /opt/DragonCore/menu.php printlim2 $username");
        if (preg_match('/\b(\d+)\b/', $output2, $matches)) {
            $limit = $matches[1];
            if ($count > $limit) {
                shell_exec("kill -9 `ps -fu $username | awk '{print $2}' | grep -v PID`");
                echo "$username Not OK : $count\n";
            } else {
                echo "$username OK : $count \n";
            }
        }
    }
}

while (true) {
    clearScreen();
    limiter();
    sleep(5 * 60);
}
?>