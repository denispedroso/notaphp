<?php
namespace Project;

/**
 * Inutilizacao de NFe
 * usando  http://github.com/nfephp-org/sped-nfe
 *
 * @category  Project
 * @package   Project\Inutilizacao
 * @copyright Project Copyright (c) 2018-2018
 * @license   http://www.gnu.org/licenses/lgpl.txt LGPLv3+
 * @author    Denis B. Pedroso <denisbpedroso at gmail dot com>
 * @link
 */

use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Complements;
use Retorno\Retorno;

class Inutilizacao
{
    public $config;

    public $dir;

    public $json;

    public $certif;

    public $nNF;

    public $nSerie;
    public $nIni;
    public $nFin;
    public $xJust;

    public $cnpj_emissor;
    public $uf_emissor;
    public $ano_emissao;
    public $modelo;
    public $data_inutilizacao;

    public $senha;

    public function __construct($dir, $config, $certif, $senha)
    {
        $this->dir = $dir;
        
        $this->config = $config;

        $this->json = json_encode($this->config);

        $this->certif = file_get_contents($certif);

        $this->senha = $senha;
    }

    /**
     * Solicita autorizao para inutilizar numeros de NFe
     *
     * @param string $nSerie Série da NF-e
     * @param string $nIni   Número da NF-e inicial a ser
     *                       inutilizada
     * @param string $nFin   Número da NF-e final a ser
     *                       inutilizada
     * @param string $xJust  Informar a justificativa do pedido de
     *                       inutilização
     */
    public function inutiliza($fileName, $nSerie, $nIni, $nFin, $xJust)
    {
        try {
            $dir = $this->dir;
            $certif = $this->certif;
            $configJson = $this->json;
            $senha = $this->senha;
            $certificate = Certificate::readPfx($certif, $senha);
            $tools = new Tools($configJson, $certificate);

            $response = $tools->sefazInutiliza($nSerie, $nIni, $nFin, $xJust);
        
            //você pode padronizar os dados de retorno atraves da classe abaixo
            //de forma a facilitar a extração dos dados do XML
            //NOTA: mas lembre-se que esse XML muitas vezes será necessário,
            //      quando houver a necessidade de protocolos
            $stdCl = new Standardize($response);
            //nesse caso $std irá conter uma representação em stdClass do XML
            $std = $stdCl->toStd();
            
            file_put_contents("$dir\\inutilizadas\\$fileName.xml", $response);

            // Cria Arquivo de retorno
            $ret = new Retorno();
            $ret->processo = 3;
            $ret->numero_nota_fiscal = $nIni;
            $ret->chave_nfe = 0;
            if (isset($std->infInut->nProt)) {
                $ret->protocolo = $std->infInut->nProt;
            } else {
                $ret->protocolo = "";
            }
            $ret->status = $std->infInut->cStat;
            $ret->identifica_origem_erro = 2;
            $ret->descricao_erro = $std->infInut->xMotivo;
            $ret->data_autorizacao = $std->infInut->dhRecbto;
            $ret->uf_autorizou = $std->infInut->cUF;
            $ret->tipo_ret = "Inu";
            $ret->numero_nf_final = $nFin;
            $ret->dir = $dir;
            $ret->montaTxt();

            //delete incoming Txt file
            $stripedDir = substr($dir, 0, -15);
            unlink("$stripedDir\\txt\\$fileName.txt");
            return $ret;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}
