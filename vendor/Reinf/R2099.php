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

class R2099
{
    public $dir;
    public $config;
    public $certif;
    public $senha;
    public $edf_R2099;
    public $fileName;

    public function __construct($dir, $config, $certif, $senha, $edf_R2099, $fileName)
    {
        $this->dir = $dir;
        $this->config = $config;
        $this->certif = file_get_contents($certif);
        $this->senha = $senha;
        $this->edf_R2099 = $edf_R2099;
        $this->fileName = $fileName;
    }

    public function Schema()
    {
        $dir = $this->dir;
        $config = $this->config;
        $certif = $this->certif;
        $senha = $this->senha;
        $edf_R2099 = $this->edf_R2099;
        $fileName = $this->fileName;
        
        $evento = 'evtFechaEvPer';
        $version = '1_04_00';
        $jsonSchema = '{
            "title": "evtFechaEvPer",
            "type": "object",
            "properties": {
                "sequencial": {
                    "required": true,
                    "type": "integer",
                    "minimum": 1,
                    "maximum": 99999
                },
                "perapur": {
                    "required": true,
                    "type": "string",
                    "pattern": "^(19[0-9][0-9]|2[0-9][0-9][0-9])[-/](0?[1-9]|1[0-2])$"
                },
                "iderespinf": {
                    "required": false,
                    "type": ["object","null"],
                    "properties": {
                        "nmresp": {
                            "required": true,
                            "type": "string",
                            "maxLength": 70
                        },
                        "cpfresp": {
                            "required": true,
                            "type": "string",
                            "maxLength": 11,
                            "pattern": "^[0-9]"
                        },
                        "telefone": {
                            "required": false,
                            "type": ["string","null"],
                            "maxLength": 13
                        },
                        "email": {
                            "required": false,
                            "type": ["string","null"],
                            "maxLength": 60
                        }
                    }
                },
                "evtservtm": {
                    "required": true,
                    "type": "string",
                    "pattern": "S|N"
                },
                "evtservpr": {
                    "required": true,
                    "type": "string",
                    "pattern": "S|N"
                },
                "evtassdesprec": {
                    "required": true,
                    "type": "string",
                    "pattern": "S|N"
                },
                "evtassdesprep": {
                    "required": true,
                    "type": "string",
                    "pattern": "S|N"
                },
                "evtcomprod": {
                    "required": true,
                    "type": "string",
                    "pattern": "S|N"
                },
                "evtcprb": {
                    "required": true,
                    "type": "string",
                    "pattern": "S|N"
                },
                "evtpgtos": {
                    "required": false,
                    "type": "string",
                    "pattern": "S|N"
                },
                "compsemmovto": {
                    "required": false,
                    "type": ["string","null"],
                    "pattern": "^(19[0-9][0-9]|2[0-9][0-9][0-9])[-/](0?[1-9]|1[0-2])$"
                }
            }
        }';

        $std = json_decode($edf_R2099);

        // Schema must be decoded before it can be used for validation
        $jsonSchemaObject = json_decode($jsonSchema);
        if (empty($jsonSchemaObject)) {
            echo "<h2>Erro de digitação no schema ! Revise</h2>";
            echo "<pre>";
            print_r($jsonSchema);
            echo "</pre>";
            die();
        }
        // The SchemaStorage can resolve references, loading additional schemas from file as needed, etc.
        $schemaStorage = new SchemaStorage();
        // This does two things:
        // 1) Mutates $jsonSchemaObject to normalize the references (to file://mySchema#/definitions/integerData, etc)
        // 2) Tells $schemaStorage that references to file://mySchema... should be resolved by looking in $jsonSchemaObject
        $schemaStorage->addSchema('file://mySchema', $jsonSchemaObject);
        // Provide $schemaStorage to the Validator so that references can be resolved during validation
        $jsonValidator = new Validator(new Factory($schemaStorage));
        // Do validation (use isValid() and getErrors() to check the result)
        $jsonValidator->validate(
            $std,
            $jsonSchemaObject,
            Constraint::CHECK_MODE_COERCE_TYPES  //tenta converter o dado no tipo indicado no schema
        );
        if ($jsonValidator->isValid()) {
            echo "The supplied JSON validates against the schema.<br/>";
        } else {
            echo "JSON does not validate. Violations:<br/>";
            foreach ($jsonValidator->getErrors() as $error) {
                echo sprintf("[%s] %s\r\n", $error['property'], $error['message']);
            }
            die;
        }
        //salva se sucesso
        //file_put_contents($dir . "/jsonSchemes/v$version/$evento.schema", $jsonSchema);
    }


    public function Enviar()
    {
        $dir = $this->dir;
        $config = $this->config;
        $certif = $this->certif;
        $senha = $this->senha;
        $edf_R2099 = $this->edf_R2099;
        $fileName = $this->fileName;
        
        $std = json_decode($edf_R2099);

        try {
            
            //carrega a classe responsavel por lidar com os certificados
            $certificate = Certificate::readPfx($certif, $senha);
            
            //cria o evento e retorna o XML assinado
            $xml = Event::evtFechaEvPer(
                $config,
                $std,
                $certificate,
                '2017-08-03 10:37:00'
            )->toXml();
                     
            file_put_contents("$dir\\reinf\\xmls\\$fileName.xml", $xml);
            
            $evento = Event::evtFechaEvPer($config, $std);
    
            //instancia a classe responsável pela comunicação
            $tools = new Tools($config, $certificate);
            
            //executa o envio
            $response = $tools->enviarLoteEventos($tools::EVT_PERIODICOS, [$evento]);
            //$response = $tools->consultar('7490-2099-1810-7490');

            file_put_contents("$dir\\reinf\\xmls\\$fileName"."_response.xml", $response);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}
