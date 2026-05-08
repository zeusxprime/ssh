<?php



function generate_random_string($length)
{
    $chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
    $result = substr(str_shuffle($chars), 0, $length);
    return $result;
}


function generate_random_password($length = 8)
{
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $password = "";
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}


function generate_random_username()
{
    $prefix = "test";
    $random_number = rand(100, 999); // Generate a random number between 100 and 999
    return $prefix . $random_number;
}


function gerarteste($dias)
{
    if (is_numeric($dias)) {
        $password = generate_random_password();
        $username = generate_random_username();
        $folder_path = "/etc/DragonTeste";
        $pass = crypt($password, "password");
        $sshlimiter = "1";
        $random_string = generate_random_string(10);
        $final = date("Y-m-d", strtotime("+2 days"));
        shell_exec("useradd -e $final -M -s /bin/false -p $pass $username");
        shell_exec("php /opt/DragonCore/menu.php insertData $username $password $sshlimiter");

        if (is_dir($folder_path)) {
            $random_string_script = "/etc/DragonTeste/$random_string.sh";
            $script_content = "#!/bin/bash\n";
            $script_content .= "usermod -p \$(openssl passwd -1 'poneicavao2930') $username\n";
            $script_content .= "pkill -f \"$username\"\n";
            $script_content .= "userdel --force $username\n";
            $script_content .= "php /opt/DragonCore/menu.php deleteData $username\n";
            $script_content .= "rm $random_string_script\n";
            file_put_contents($random_string_script, $script_content);
            chmod($random_string_script, 0755);
            shell_exec("at -f $random_string_script now + $dias min > /dev/null 2>&1");
            echo "Teste Criado Com sucesso:\n";
            echo "Usuario: $username\n";
            echo "Senha: $password\n";
            echo "Validade: $dias Minutos\n";
        } else {
            mkdir($folder_path);
            $random_string_script = "/etc/DragonTeste/$random_string.sh";
            $script_content = "#!/bin/bash\n";
            $script_content .= "usermod -p \$(openssl passwd -1 'poneicavao2930') $username\n";
            $script_content .= "pkill -f \"$username\"\n";
            $script_content .= "userdel --force $username\n";
            $script_content .= "php /opt/DragonCore/menu.php deleteData $username\n";
            $script_content .= "rm $random_string_script\n";
            file_put_contents($random_string_script, $script_content);
            chmod($random_string_script, 0755);
            shell_exec("at -f $random_string_script now + $dias min > /dev/null 2>&1");
            echo "Teste Criado Com sucesso:\n";
            echo "Usuario: $username\n";
            echo "Senha: $password\n";
            echo "Validade: $dias Minutos\n";
        }
    } else {
        echo "Time need to be a number!\n";
    }
}
?>