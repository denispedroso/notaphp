<?php
require_once "vendor\\autoload.php";

use Project\Project;
use Project\ConsultaStatus;
use Project\CancelaNFe;
use Project\CartaCorrec;
use Project\Inutilizacao;
use Retorno\Retorno;
use Project\Files;
use Project\DadosNF;
use Project\Emails;

/**
 * Recebe arquivos de texto de NFe, converte para XML, assina, e envia para a receita
 * usando  http://github.com/nfephp-org/sped-nfe
 *
 * @version   1.2.2
 * @category  Index
 * @package   Index
 * @author    Denis B. Pedroso <denisbpedroso@gmail.com>
 * @copyright 2021 DTP Processamento de Dados LTDA
 * @license   http://www.gnu.org/licenses/lgpl.txt LGPLv3+
 * @link      no link
 */

// Pega o diretório atual
$dir = __DIR__;
print_r(date('h:i:s'));

$timer = date('h:i:s');

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

    if (array_key_exists($errno, $errors)) {
        $errorDisp = $errors[$errno];
    }

    $dir = __DIR__;
    date_default_timezone_set('America/Sao_Paulo');
    $today = date('d/m/Y H:i:s', time());

    $handle = fopen("$dir\\error_log.txt", "a+");
    fwrite($handle, "$today -> Erro: [$errorDisp] $errstr, no arquivo $errfile, na linha $errline\r\n");
    fclose($handle);
}

//set error handler
set_error_handler("customError");

/**
 * Cria array com lista de tipos
 */
$options = [1 => 'can', 2 => 'car', 3 => 'inu', 4 => 'rec'];

/**
 * Pega o arquivo na pasta \txt
 * Pega o nome do arquivo
 * Pega o tipo de arquivo
 *
 */
$adress = "$dir\\txt";
$files = new Files($adress);
$files->findFiles();
$content = $files->content;

$fileName = $files->getFileName();

$tipo = $files->getFileType();

/**
 * Pega os emails a serem enviados, se nao houver fica em branco
 */
if ($tipo == 'nfe' || $tipo == 'nfc') {
    $find = new Emails($content);

    $emails = $find->emails;

    $content = $find->content;

    /**
     * Pega o numero da NF dentro do txt
     * Pega tipoNF no conteudo
     * Pega o numero do CNPJ no conteudo
     *
     */
    $grab = new DadosNF($content);
    $grab->getNumbers();
    $nNF = $grab->nNF;
    $tipoNF = $grab->tipoNF;
    $nCNPJ = $grab->nCNPJ;
}
// Acrescenta linha Resp Tec
$linha_ZD = "ZD|$nCNPJ|Responsavel|email|telefone|||";

array_push($content, $linha_ZD);

//Monta o arquivo de texto
$txt = implode($content);

if ($tipo != 'nfe' & $tipo != 'nfc') {
    $linha = explode('|', $content[0]);
    if ($tipo === 'car') {
        $nCNPJ = $linha[1];
    } else {
        $nCNPJ = $linha[0];
    }
}

$configs = file_get_contents("$dir\\configs.txt");
$configs = unserialize($configs);
$key = '';
foreach ($configs as $nKey => $config) {
    if (array_search($nCNPJ, $config)) {
        $key = $nKey;
        break;
    }
}

$config = $configs[$key];
$certif = $configs[$key]['certificado'];
$senha = $configs[$key]['senhaCertificado'];

$certif = "$dir\\certifs\\$certif";

$dir = "$dir\\$nCNPJ";

if (!is_file($certif)) {
    trigger_error("Não foi possivel achar o arquivo do certificado!", E_USER_WARNING);
    exit();
}

//Consulta status do serviço da Receita Estadual
$consulta = new ConsultaStatus($dir, $config, $certif, $senha);
$status = $consulta->consulta();
if (isset($status->cStat)) {
    $cStat = $status->cStat;
} else {
    $cStat = 999;
}

if ($cStat != 107) {
    $ret = new Retorno();
    $ret->processo = 9;
    $ret->tipo_ret = "Ser";
    $ret->numero_nota_fiscal = 0;
    $ret->numero_nf_final = 0;
    $ret->chave_nfe = "";
    $ret->protocolo = "";
    $ret->data_autorizacao = "";
    $ret->uf_autorizou = 0;
    if (isset($status->cStat)) {
        $ret->status = $status->cStat;
    } else {
        $ret->status = 999;
    }
    $ret->identifica_origem_erro = 2;
    if (isset($status->xMotivo)) {
        $ret->descricao_erro = $status->xMotivo;
    } else {
        $ret->descricao_erro = "Erro ao se comunicar com o servidor da receita!";
    }
    $ret->dir = $dir;
    $ret->montaTxt();

    trigger_error(print_r($ret, true), E_USER_WARNING);
    exit();
}

switch ($tipo) {
    case 'nfe':
        $project = new Project($dir, $config, $certif, $nNF, $tipoNF, $senha, $txt, $fileName, $emails);
        $project->convert();
        $timer2 = date('h:i:s');
        $segundo = explode(':', $timer);
        $segundo2 = explode(':', $timer2);
        $totalTimer = $segundo2[2] - $segundo[2];
        trigger_error("Tempo total $totalTimer", E_USER_WARNING);
        break;

    case 'nfc':
        $project = new Project($dir, $config, $certif, $nNF, $tipoNF, $senha, $txt, $fileName, $emails);
        $project->convert();
        $timer2 = date('h:i:s');
        $segundo = explode(':', $timer);
        $segundo2 = explode(':', $timer2);
        $totalTimer = $segundo2[2] - $segundo[2];
        trigger_error("Tempo total $totalTimer", E_USER_WARNING);
        break;

    case 'can':
        $can = new CancelaNFe($dir, $config, $certif, $senha);

        $txt2 = explode("|", $txt);
        $can->cnpj_emissor = $txt2[0];
        $can->uf_emissor = $txt2[1];
        $can->data_emissao = $txt2[2];
        $can->modelo = $txt2[3];
        $can->serie = $txt2[4];
        $can->numero_docto = $txt2[5];
        $can->nNF = $txt2[5];
        $can->chave = $txt2[6];
        $can->protocolo_autorizacao = $txt2[7];
        $can->motivo = $txt2[8];
        $can->data_cancelamento = $txt2[9];
        $can->tipo_ambiente = $txt2[10];
        $can->tipoNF = $txt2[3];

        $motivo = strlen(trim($can->motivo));

        if ($motivo < 16) {
            $ret = new class
            {
            };
            $ret->Erro = 002;
            $ret->Motivo = "O campo justificativa deve ter no mínimo 15 letras!";
            trigger_error($ret, E_USER_WARNING);
            break;
        }
        $can->cancela($fileName);
        $timer2 = date('h:i:s');
        $segundo = explode(':', $timer);
        $segundo2 = explode(':', $timer2);
        $totalTimer = $segundo2[2] - $segundo[2];
        trigger_error("Tempo total $totalTimer", E_USER_WARNING);
        break;

    case 'car':
        $car = new CartaCorrec($dir, $config, $certif, $senha);
        $txt2 = explode("|", $txt);
        $car->cnpj_emissor = $txt2[1];
        $xSeqEvento = $txt2[2];
        $chave = $txt2[4];
        $xCorrecao = $txt2[10];

        $car->correcao($fileName, $chave, $xCorrecao, $xSeqEvento);
        $timer2 = date('h:i:s');
        $segundo = explode(':', $timer);
        $segundo2 = explode(':', $timer2);
        $totalTimer = $segundo2[2] - $segundo[2];
        trigger_error("Tempo total $totalTimer", E_USER_WARNING);
        break;

    case 'inu':
        $inu = new Inutilizacao($dir, $config, $certif, $senha);

        $txt2 = explode("|", $txt);
        $inu->cnpj_emissor = $txt2[0];
        $inu->uf_emissor = $txt2[1];
        $inu->ano_inutilizacao = $txt2[2];
        $inu->modelo = $txt2[3];
        $nSerie = $txt2[4];
        $nSerie = ltrim($nSerie, '0');
        $nIni = $txt2[5];
        $nIni = ltrim($nIni, '0');
        $nFin = $txt2[6];
        $nFin = ltrim($nFin, '0');
        $xJust = $txt2[7];
        $inu->data_inutilizacao = $txt2[8];

        $inu->inutiliza($fileName, $nSerie, $nIni, $nFin, $xJust);
        $timer2 = date('h:i:s');
        $segundo = explode(':', $timer);
        $segundo2 = explode(':', $timer2);
        $totalTimer = $segundo2[2] - $segundo[2];
        trigger_error("Tempo total $totalTimer", E_USER_WARNING);
        break;

    case 'rec':
        $project = new Project($dir, $config, $certif, $nNF, $tipoNF, $senha, $txt, $fileName, $emails);
        $txt2 = explode("|", $txt);
        $recibo = $txt2[0];
        $fileName = str_replace("rec", "nfe", $fileName);
        $project->consultaProtocolo($fileName, $recibo);
        $timer2 = date('h:i:s');
        $segundo = explode(':', $timer);
        $segundo2 = explode(':', $timer2);
        $totalTimer = $segundo2[2] - $segundo[2];
        trigger_error("Tempo total $totalTimer", E_USER_WARNING);
        break;
}
