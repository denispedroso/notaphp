<?php
require_once "vendor/autoload.php";

use Config\Config;

function customError($errno, $errstr, $errfile, $errline)
{
    $errors = [
        2 => "Warning",
        8 => "Notice",
        256 => "User Error",
        512 => "User Warning",
        1024 => "User Notice",
        4096 => "Recoverable Error",
        8191 => "All",
    ];

    $errorDisp = $errors[$errno];
    $dir = __DIR__;
    date_default_timezone_set('America/Sao_Paulo');
    $today = date('d/m/Y H:i:s', time());

    $handle = fopen("$dir\\error_log.txt", "a+");
    fwrite($handle, "$today -> Erro: [$errorDisp] $errstr, no arquivo $errfile, na linha $errline\r\n");
    fclose($handle);
}

//set error handler
set_error_handler("customError");

$dir = __DIR__;

echo "DTP Processamento de Dados Ltda - ©1983-2021\n\r";
echo "--------------------------------------------\n\r";
/**
 * echo "Senha:";
 * echo "\033[30;40m";
 * $senha = trim(fgets(STDIN));
 * echo "\033[0m";
 * $hash = file_get_contents(realpath($dir."\master.txt"));
 *
 * if (!password_verify($senha, $hash)) {
 *     echo "Senha inválida!";
 *     exit();
 * }
 */


$exit = false;
$config = new Config($dir);
while ($exit == false) {
    $option = $config->options();

    switch ($option) {
        case 1:
            $config->alteraSenha($dir);
            break;
        case 2:
            $config->alteraNome($dir);
            break;
        case 3:
            $config->alteraCnpj($dir);
            break;
        case 31:
            $config->siglaUF($dir);
            break;
        case 4:
            $config->ListarEmpresas($dir);
            break;
        case 8:
            $config->gerarPastas($dir);
            break;
        case 9:
            exit();
        default:
            break;
    }
}
