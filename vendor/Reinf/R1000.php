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

class R1000
{
    public $dir;
    public $config;
    public $certif;
    public $senha;
    public $edf_R1000;
    public $fileName;

    public function __construct($dir, $config, $certif, $senha, $edf_R1000, $fileName)
    {
        $this->dir = $dir;
        $this->config = $config;
        $this->certif = file_get_contents($certif);
        $this->senha = $senha;
        $this->edf_R1000 = $edf_R1000;
        $this->fileName = $fileName;
    }

    public function Schema()
    {
        $dir = $this->dir;
        $config = $this->config;
        $certif = $this->certif;
        $senha = $this->senha;
        $edf_R1000 = $this->edf_R1000;
        $fileName = $this->fileName;
        
        $evento = 'evtInfoContri';
        $version = '1_04_00';
        $jsonSchema = '{
            "title": "evtInfoContri",
            "type": "object",
            "properties": {
                "sequencial": {
                    "required": true,
                    "type": "integer",
                    "minimum": 1,
                    "maximum": 99999
                },
                "inivalid": {
                    "required": true,
                    "type": "string",
                    "pattern": "^(19[0-9][0-9]|2[0-9][0-9][0-9])[-/](0?[1-9]|1[0-2])$"
                },
                "fimvalid": {
                    "required": false,
                    "type": ["string","null"],
                    "pattern": "^(19[0-9][0-9]|2[0-9][0-9][0-9])[-/](0?[1-9]|1[0-2])$"
                },
                "modo": {
                    "required": true,
                    "type": "string",
                    "pattern": "INC|ALT|EXC"
                },
                "infoCadastro": {
                    "required": false,
                    "type": ["object","null"],
                    "properties": {
                        "classtrib": {
                            "required": true,
                            "type": "string",
                            "minLength": 2,
                            "maxLength": 2,
                            "pattern": "^[0-9]"
                        },
                        "indescrituracao": {
                            "required": true,
                            "type": "integer",
                            "minimum": 0,
                            "maximum": 1
                        },
                        "inddesoneracao": {
                            "required": true,
                            "type": "integer",
                            "minimum": 0,
                            "maximum": 1
                        },
                        "indacordoisenmulta": {
                            "required": true,
                            "type": "integer",
                            "minimum": 0,
                            "maximum": 1
                        },
                        "indsitpj": {
                            "required": false,
                            "type": ["integer","null"],
                            "minimum": 0,
                            "maximum": 4
                        },
                        "contato": {
                            "required": true,
                            "type": "object",
                            "properties": {
                                "nmctt": {
                                    "required": true,
                                    "type": "string",
                                    "maxLength": 70
                                },
                                "cpfctt": {
                                    "required": true,
                                    "type": "string",
                                    "maxLength": 11,
                                    "pattern": "^[0-9]"
                                },
                                "fonefixo": {
                                    "required": false,
                                    "type": ["string","null"],
                                    "minLength": 10,
                                    "maxLength": 13,
                                    "pattern": "^[0-9]"
                                },
                                "fonecel": {
                                    "required": false,
                                    "type": ["string","null"],
                                    "minLength": 10,
                                    "maxLength": 13,
                                    "pattern": "^[0-9]"
                                },
                                "email": {
                                    "required": false,
                                    "type": ["string","null"],
                                    "maxLength": 60
                                }
                            }
                        },
                        "softwarehouse": {
                            "required": false,
                            "type": ["array","null"],
                            "minItems": 0,
                            "maxItems": 99,
                            "items": {
                                "type": "object",
                                "properties": {
                                    "cnpjsofthouse": {
                                        "required": true,
                                        "type": "string",
                                        "maxLength": 14,
                                        "pattern": "^[0-9]"
                                    },
                                    "nmrazao": {
                                        "required": true,
                                        "type": "string",
                                        "maxLength": 115
                                    },
                                    "nmcont": {
                                        "required": true,
                                        "type": "string",
                                        "maxLength": 70
                                    },
                                    "telefone": {
                                        "required": true,
                                        "type": "string",
                                        "minLength": 10,
                                        "maxLength": 13,
                                        "pattern": "^[0-9]"
                                    },
                                    "email": {
                                        "required": false,
                                        "type": ["string","null"],
                                        "maxLength": 60
                                    }
                                }
                            }    
                        },
                        "infoefr": {
                            "required": false,
                            "type": ["object","null"],
                            "properties": {
                                "ideefr": {
                                    "required": true,
                                    "type": "string",
                                    "pattern": "S|N"
                                },
                                "cnpjefr": {
                                    "required": false,
                                    "type": ["string","null"],
                                    "maxLength": 14,
                                    "pattern": "^[0-9]"
                                }
                            }
                        }
                    }
                }    
            }
        }';

        $std = json_decode($edf_R1000);

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
            echo "The supplied JSON validates against the schema.";
        } else {
            echo "JSON does not validate. Violations:<br/>";
            foreach ($jsonValidator->getErrors() as $error) {
                echo sprintf("[%s] %s<br/>", $error['property'], $error['message']);
            }
            die;
        }
        //salva se sucesso
        //file_put_contents("$dir\\jsonSchemes\\v$version\\$evento.schema", $jsonSchema);
    }


    public function Enviar()
    {
        $dir = $this->dir;
        $config = $this->config;
        $certif = $this->certif;
        $senha = $this->senha;
        $edf_R1000 = $this->edf_R1000;
        $fileName = $this->fileName;
        
        $std = json_decode($edf_R1000);

        try {
            
            //carrega a classe responsavel por lidar com os certificados
            $certificate = Certificate::readPfx($certif, $senha);
            
            //cria o evento e retorna o XML assinado
            $xml = Event::evtInfoContri(
                $config,
                $std,
                $certificate,
                '2017-08-03 10:37:00'
            )->toXml();
            
            //$xml = Event::r1000($config, $std, $certificate)->toXML();
            //$json = Event::evtInfoContri($config, $std, $certificate)->toJson();
            
            file_put_contents("$dir\\reinf\\xmls\\$fileName.xml", $xml);
            
            $evento = Event::evtInfoContri($config, $std);
    
            //instancia a classe responsável pela comunicação
            $tools = new Tools($config, $certificate);
            
            //executa o envio
            $response = $tools->enviarLoteEventos($tools::EVT_INICIAIS, [$evento]);

            file_put_contents("$dir\\reinf\\xmls\\$fileName"."_response.xml", $response);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}
