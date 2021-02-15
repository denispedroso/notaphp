<?php
namespace Reinf;

error_reporting(E_ALL);
ini_set('display_errors', 'On');

use NFePHP\EFDReinf\Event;
use NFePHP\Common\Certificate;
use NFePHP\EFDReinf\Tools;
use JsonSchema\Validator;

use JsonSchema\Constraints\Constraint;
use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;

use NFePHP\EFDReinf\Common\FakePretty;
use NFePHP\EFDReinf\Common\Soap\SoapFake;

use stdClass;

class Consulta {

    public $dir;
    public $config;
    public $certif;
    public $senha;
    public $recibo;
    public $fileName;

    public function __construct($dir, $config, $certif, $senha, $recibo, $fileName)
    {
        $this->dir = $dir;
        $this->config = $config;
        $this->certif = file_get_contents($certif);
        $this->senha = $senha;
        $this->recibo = $recibo;
        $this->fileName = $fileName;

    }

    public function Enviar()
    {
        $dir = $this->dir;
        $config = $this->config;
        $certif = $this->certif;
        $senha = $this->senha;
        $recibo = $this->recibo;
        $fileName = $this->fileName;
        
        $config = json_decode($config);
        $recibo = json_decode($recibo);

        //CONSULTAS
        //Consolidada 
        $std = new stdClass();
        $std->numeroprotocolofechamento = $recibo->recibo;
        $std->tipoinscricaocontribuinte = $config->contribuinte->tpInsc;
        $std->numeroinscricaocontribuinte = $config->contribuinte->nrInsc;

        $config = json_encode($config);
        try {
            
            //carrega a classe responsavel por lidar com os certificados
            $certificate = Certificate::readPfx($certif, $senha);
    
            //instancia a classe responsável pela comunicação
            $tools = new Tools($config, $certificate);
            
            //executa o envio
            $response = $tools->consultar($tools::CONSULTA_CONSOLIDADA, $std);

            file_put_contents("$dir\\reinf\\xmls\\$fileName"."_response.xml", $response);
            
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return;
    }

}