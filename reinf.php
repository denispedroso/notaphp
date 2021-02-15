<?php
require_once "vendor/autoload.php";

use Reinf\R1000;
use Reinf\R2010;
use Reinf\R2060;
use Reinf\R2098;
use Reinf\R2099;
use Reinf\R9000;
use Reinf\Consulta;

/**
 * Recebe arquivos de texto de NFe, converte para XML, assina, e envia para a receita
 * usando  http://github.com/nfephp-org/sped-nfe
 *
 * @version   1.0.2
 * @category  Index
 * @package   Index
 * @author    Denis B. Pedroso <denisbpedroso@gmail.com>
 * @copyright 2018-2018 DTP Processamento de Dados LTDA
 * @license   http://www.gnu.org/licenses/lgpl.txt LGPLv3+
 * @link      no link
 */

// Pega o diretório atual
$dir = __DIR__;
echo "$dir \n\r";

$textReinf = realpath("$dir\\txtreinf");
echo "$textReinf \n\r";

// Passa a configuração da Empresa Atual
$pathName = realpath("$dir\\txtreinf\\Config.txt");
$config = file_get_contents($pathName);
$config = json_decode($config);
$nCNPJ = $config->transmissor->nrInsc;
$config = json_encode($config, JSON_PRETTY_PRINT);

// Passa a configuração da Empresa Atual
$configs = file_get_contents("$dir\\configs.txt");
$configs = unserialize($configs);
$key = '';
foreach ($configs as $nKey => $config2) {
    if (array_search($nCNPJ, $config2)) {
        $key = $nKey;
        break;
    }
}

//$config = $configs[$key];
$certif = $configs[$key]['certificado'];
$senha = $configs[$key]['senhaCertificado'];

$certif = "$dir\\certifs\\$certif";
$dir = "$dir\\$nCNPJ";
if (!is_file($certif)) {
    echo "Não foi possivel achar o arquivo do certificado!";
    sleep(10);
    exit();
}

/**
 * Procura por arquivos na pasta /txt
 */

$files1 = scandir($textReinf);
foreach ($files1 as $file) {
    if ($file == '.' || $file == '..' || $file == 'desktop.ini') {
        continue;
    }

    //Pega o nome do arquivo original
    $fileName = pathinfo("$textReinf\\$file", PATHINFO_FILENAME);
    $fileName = strtolower($fileName);
    $tipo = substr($fileName, 0, 9);

    $pathName = realpath("$textReinf\\$file");
    $edf = file_get_contents($pathName);

    switch ($tipo) {
        case 'r1000':
            $r1000 = new R1000($dir, $config, $certif, $senha, $edf, $fileName);
            $r1000->Schema();
            $r1000->Enviar();
            break;
        case 'r2010':
            $r2010 = new R2010($dir, $config, $certif, $senha, $edf, $fileName);
            $r2010->Schema();
            $r2010->Enviar();
            break;
        case 'r2060':
            $r2060 = new R2060($dir, $config, $certif, $senha, $edf, $fileName);
            $r2060->Schema();
            $r2060->Enviar();
            break;
        case 'r2098':
            $r2098 = new R2098($dir, $config, $certif, $senha, $edf, $fileName);
            $r2098->Schema();
            $r2098->Enviar();
            break;
        case 'r2099':
            $r2099 = new R2099($dir, $config, $certif, $senha, $edf, $fileName);
            $r2099->Schema();
            $r2099->Enviar();
            break;
        case 'r9000':
            $r9000 = new R9000($dir, $config, $certif, $senha, $edf, $fileName);
            $r9000->Schema();
            $r9000->Enviar();
            break;
        case 'consr2099':
            $consulta = new Consulta($dir, $config, $certif, $senha, $edf, $fileName);
            $consulta->Enviar();
            break;
    }
}
