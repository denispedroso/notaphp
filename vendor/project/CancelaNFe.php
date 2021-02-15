<?php
namespace Project;

/**
 * Cancela a NFe autorizada
 * usando  http://github.com/nfephp-org/sped-nfe
 *
 * @category  Project
 * @package   Project\CancelaNFe
 * @copyright Project Copyright (c) 2018-2018
 * @license   http://www.gnu.org/licenses/lgpl.txt LGPLv3+
 * @license   https://opensource.org/licenses/MIT MIT
 * @license   http://www.gnu.org/licenses/gpl.txt GPLv3+
 * @author    Denis B. Pedroso <denisbpedroso at gmail dot com>
 * @link      nolink
 */

use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Complements;
use Retorno\Retorno;

class CancelaNFe
{
    public $config;

    public $dir;

    public $json;

    public $certif;

    public $nNF;

    public $cnpj_emissor;
    public $uf_emissor;
    public $data_emissao;
    public $modelo;
    public $serie;
    public $numero_docto;
    public $chave;
    public $protocolo_autorizacao;
    public $motivo;
    public $data_cancelamento;
    public $tipo_ambiente;

    public $senha;

    /**
     * Tipo da NF
     *
     * @var string
     */
    public $tipoNF;

    /**
     * Instancia a classe CancelaNFe
     *
     * @param string $dir    passa o diretorio dos arquivos
     * @param string $config passa as configuracoes da empresa
     * @param string $certif passa o certificado
     */

    public function __construct($dir, $config, $certif, $senha)
    {
        $this->dir = $dir;
        
        $this->config = $config;

        $this->json = json_encode($this->config);

        $this->certif = file_get_contents($certif);

        $this->senha = $senha;
    }
    
    /**
     * Solicita cancelamento de NFe
     *
     * @param string $chave Chave de 44 dígitos da NFe que se quer cancelar (OBRIGATÓRIO)
     * @param string $xJust Justificativa para o cancelamento (OBRIGATÓRIO)
     * @param string $nProt Número do protocolo de autorização de uso (OBRIGATÓRIO)
     *
     * @return value
     */
    public function cancela($fileName)
    {
        $dir = $this->dir;
        $certif = $this->certif;
        $configJson = $this->json;
        $senha = $this->senha;
        $certificate = Certificate::readPfx($certif, $senha);
        $tools = new Tools($configJson, $certificate);
        if ($this->tipoNF == '65') {
            $tools->model('65');
        } else {
            $tools->model('55');
        }
        
        $chave = $this->chave;
        $xJust = $this->motivo;
        $nProt = $this->protocolo_autorizacao;

        $response = $tools->sefazCancela($chave, $xJust, $nProt);
    
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
            $ret->processo = 2;
            $ret->numero_nota_fiscal = $this->nNF;
            $ret->chave_nfe = $std->retEvento->infEvento->chNFe;
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
            $ret->tipo_ret = "Can";
            $ret->numero_nf_final = 0;

            $ret->dir = $dir;

            $ret->montaTxt();

            //delete incoming Txt file
            $stripedDir = substr($dir, 0, -15);
            unlink("$stripedDir\\txt\\$fileName.txt");
            return $ret;
        } else {
            $cStat = $std->retEvento->infEvento->cStat;
            if ($cStat == '101' || $cStat == '135' || $cStat == '155' ) {
                //SUCESSO PROTOCOLAR A SOLICITAÇÂO ANTES DE GUARDAR
                $xml = Complements::toAuthorize($tools->lastRequest, $response);

                $xmlPath = file_put_contents("$dir\\canceladas\\Nfe_chave_$chave.xml", $xml);

                /**
                 * Confere se o arquivo foi salvo corretamente
                 */
                if ($xmlPath) {
                    // Cria Arquivo de retorno
                    $ret = new Retorno();
                    $ret->processo = 2;
                    $ret->numero_nota_fiscal = $this->nNF;
                    $ret->chave_nfe = $std->retEvento->infEvento->chNFe;
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
                    $ret->tipo_ret = "Can";
                    $ret->numero_nf_final = 0;
        
                    $ret->dir = $dir;
        
                    $ret->montaTxt();
        
                    //delete incoming Txt file
                    $stripedDir = substr($dir, 0, -15);
                    unlink("$stripedDir\\txt\\$fileName.txt");
                    return $ret;
                } else {
                    // Cria Arquivo de retorno
                    $ret = new Retorno();
                    $ret->processo = 2;
                    $ret->numero_nota_fiscal = $this->nNF;
                    if (isset($std->retEvento->infEvento->nProt)) {
                        $ret->protocolo = $std->retEvento->infEvento->nProt;
                    } else {
                        $ret->protocolo = "";
                    }
                    $ret->chave_nfe = $std->retEvento->infEvento->chNFe;
                    $ret->status = $std->retEvento->infEvento->cStat;
                    $ret->identifica_origem_erro = 2;
                    $ret->descricao_erro = $std->retEvento->infEvento->xMotivo;
        
                    $ret->data_autorizacao = $std->retEvento->infEvento->dhRegEvento;
                    $ret->uf_autorizou = $std->retEvento->infEvento->cOrgao;
                    $ret->tipo_ret = "Can";
                    $ret->numero_nf_final = 0;
        
                    $ret->dir = $dir;
        
                    $ret->montaTxt();
        
                    //delete incoming Txt file
                    $stripedDir = substr($dir, 0, -15);
                    unlink("$stripedDir\\txt\\$fileName.txt");
                    $ret->error = true;
                    return $ret;
                }
            } else {
                // Cria Arquivo de retorno
                $ret = new Retorno();
                $ret->processo = 2;
                $ret->numero_nota_fiscal = $this->nNF;
                $ret->chave_nfe = $std->retEvento->infEvento->chNFe;
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
                $ret->tipo_ret = "Can";
                $ret->numero_nf_final = 0;
    
                $ret->dir = $dir;
    
                $ret->montaTxt();
    
                //delete incoming Txt file
                $stripedDir = substr($dir, 0, -15);
                unlink("$stripedDir\\txt\\$fileName.txt");
                $ret->error = true;
                return $ret;
            }
        }
    }
}
