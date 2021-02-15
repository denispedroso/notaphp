<?php
namespace Project;

/**
 * Consulta status da receita
 *
 * @category  Project
 * @package   Project\ConsultaStatus
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

class ConsultaStatus
{
    public $config;

    public $senha;

    public $dir;

    public $json;

    public $certif;

    public $nNF;

    public function __construct($dir, $config, $certif, $senha)
    {
        $this->dir = $dir;
        
        $this->config = $config;

        $this->json = json_encode($this->config);

        $this->certif = file_get_contents($certif);

        $this->senha = $senha;
    }

    public function consulta()
    {
        try {
            $certif = $this->certif;
            $configJson = $this->json;
            $senha = $this->senha;
            $certificate = Certificate::readPfx($certif, $senha);
            $tools = new Tools($configJson, $certificate);
            $tools->model('65');
            $uf = 'PR';
            $tpAmb = 2;
            $response = $tools->sefazStatus($uf, $tpAmb);
            //este método não requer parametros, são opcionais, se nenhum parametro for
            //passado serão usados os contidos no $configJson
            //$response = $tools->sefazStatus();
        
            //você pode padronizar os dados de retorno atraves da classe abaixo
            //de forma a facilitar a extração dos dados do XML
            //NOTA: mas lembre-se que esse XML muitas vezes será necessário,
            //      quando houver a necessidade de protocolos
            $stdCl = new Standardize($response);
            //nesse caso $std irá conter uma representação em stdClass do XML
            $std = $stdCl->toStd();

            $ret = new class {
            };
            $ret->cStat = $std->cStat;
            $ret->xMotivo = $std->xMotivo;
            $ret->dhRecbto = $std->dhRecbto;

            return $ret;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}
