<?php
namespace Project;

/**
 * Carta de correção da NFe
 * usando  http://github.com/nfephp-org/sped-nfe
 *
 * @category  Project
 * @package   Project\CrataCorrec
 * @copyright Project Copyright (c) 2018-2018
 * @license   http://www.gnu.org/licenses/lgpl.txt LGPLv3+
 * @license   https://opensource.org/licenses/MIT MIT
 * @license   http://www.gnu.org/licenses/gpl.txt GPLv3+
 * @author    Denis B. Pedroso <denisbpedroso at gmail dot com>
 * @link
 */

use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Complements;
use Retorno\Retorno;

class CartaCorrec
{
    public $config;

    public $dir;

    public $json;

    public $certif;

    public $nNF;

    public $chave;
    public $xCorrecao;
    public $nSeqEvento;

    public $senha;

    public function __construct($dir, $config, $certif, $senha)
    {
        $this->dir = $dir;
        
        $this->config = $config;

        $this->json = json_encode($this->config);

        $this->certif = file_get_contents($certif);

        $this->senha = $senha;
    }

    public function correcao($fileName, $chave, $xCorrecao, $nSeqEvento)
    {
        try {
            $numero_nf = substr($fileName, 8);
            
            $dir = $this->dir;
            $certif = $this->certif;
            $configJson = $this->json;
            $senha = $this->senha;
            $certificate = Certificate::readPfx($certif, $senha);
            $tools = new Tools($configJson, $certificate);
            $tools->model('55');

            $response = $tools->sefazCCe($chave, $xCorrecao, $nSeqEvento);
        
            //você pode padronizar os dados de retorno atraves da classe abaixo
            //de forma a facilitar a extração dos dados do XML
            //NOTA: mas lembre-se que esse XML muitas vezes será necessário,
            //      quando houver a necessidade de protocolos
            $stdCl = new Standardize($response);
            //nesse caso $std irá conter uma representação em stdClass do XML
            $std = $stdCl->toStd();
            
            //verifique se o evento foi processado
            if ($std->cStat != 128) {
                // Cria Arquivo de retorno
                $ret = new Retorno();
                $ret->processo = 4;
                $ret->numero_nota_fiscal = $numero_nf;
                $ret->chave_nfe = $chave;
                if (isset($std->retEvento->infEvento->nProt)) {
                    $ret->protocolo = $std->retEvento->infEvento->nProt;
                } else {
                    $ret->protocolo = "";
                }
                $ret->status = $std->retEvento->infEvento->cStat;
                $ret->identifica_origem_erro = 2;
                $ret->descricao_erro = $std->retEvento->infEvento->xMotivo;
                $ret->data_autorizacao = $std->retEvento->infEvento->dhRegEvento;
                $ret->uf_autorizou = $std->retEvento->infEvento->cOrgao;
                $ret->tipo_ret = "Car";
                $ret->numero_nf_final = 0;
                $ret->dir = $dir;
                $ret->montaTxt();

                //delete incoming Txt file
                unlink($dir . "/txt/" . $fileName . '.txt');
                return $ret;
            } else {
                $cStat = $std->retEvento->infEvento->cStat;
                if ($cStat == '135' || $cStat == '136') {
                    //SUCESSO PROTOCOLAR A SOLICITAÇÂO ANTES DE GUARDAR
                    $xml = Complements::toAuthorize($tools->lastRequest, $response);
                    
                    $xmlPath = file_put_contents($dir . "/corrigidas/Chave_" . $chave . ".xml", $xml);

                    // Cria Arquivo de retorno
                    $ret = new Retorno();
                    $ret->processo = 4;
                    $ret->numero_nota_fiscal = $numero_nf;
                    $ret->chave_nfe = $chave;
                    if (isset($std->retEvento->infEvento->nProt)) {
                        $ret->protocolo = $std->retEvento->infEvento->nProt;
                    } else {
                        $ret->protocolo = "";
                    }
                    $ret->status = $std->retEvento->infEvento->cStat;
                    $ret->identifica_origem_erro = 2;
                    $ret->descricao_erro = $std->retEvento->infEvento->xMotivo;
                    $ret->data_autorizacao = $std->retEvento->infEvento->dhRegEvento;
                    $ret->uf_autorizou = $std->retEvento->infEvento->cOrgao;
                    $ret->tipo_ret = "Car";
                    $ret->numero_nf_final = 0;
                    $ret->dir = $dir;
                    $ret->montaTxt();

                    //delete incoming Txt file
                    unlink($dir . "/txt/" . $fileName . '.txt');
                    return $ret;
                } else {
                    // Cria Arquivo de retorno
                    $ret = new Retorno();
                    $ret->processo = 4;
                    $ret->numero_nota_fiscal = $numero_nf;
                    $ret->chave_nfe = $chave;
                    if (isset($std->retEvento->infEvento->nProt)) {
                        $ret->protocolo = $std->retEvento->infEvento->nProt;
                    } else {
                        $ret->protocolo = "";
                    }
                    $ret->status = $std->retEvento->infEvento->cStat;
                    $ret->identifica_origem_erro = 2;
                    $ret->descricao_erro = $std->retEvento->infEvento->xMotivo;
                    $ret->data_autorizacao = $std->retEvento->infEvento->dhRegEvento;
                    $ret->uf_autorizou = $std->retEvento->infEvento->cOrgao;
                    $ret->tipo_ret = "Car";
                    $ret->numero_nf_final = 0;
                    $ret->dir = $dir;
                    $ret->montaTxt();

                    //delete incoming Txt file
                    $stripedDir = substr($dir, 0, -15);
                    unlink("$stripedDir\\txt\\$fileName.txt");
                    return $ret;
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}
