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

class R2010 {

    public $dir;
    public $config;
    public $certif;
    public $senha;
    public $edf_R2010;
    public $fileName;

    public function __construct($dir, $config, $certif, $senha, $edf_R2010, $fileName)
    {
        $this->dir = $dir;
        $this->config = $config;
        $this->certif = file_get_contents($certif);
        $this->senha = $senha;
        $this->edf_R2010 = $edf_R2010;
        $this->fileName = $fileName;

    }

    public function Schema()
    {
        $dir = $this->dir;
        $config = $this->config;
        $certif = $this->certif;
        $senha = $this->senha;
        $edf_R2010 = $this->edf_R2010;
        $fileName = $this->fileName;
        
        $evento = 'evtServTom';
        $version = '1_04_00';
        $jsonSchema = '{
            "title": "evtServTom",
            "type": "object",
            "properties": {
                "sequencial": {
                    "required": true,
                    "type": "integer",
                    "minimum": 1,
                    "maximum": 99999
                },
                "indretif": {
                    "required": true,
                    "type": "integer",
                    "minimum": 1,
                    "maximum": 2
                },
                "nrrecibo": {
                    "required": false,
                    "type": ["string","null"],
                    "maxLength": 52,
                    "pattern": "^([0-9]{1,18}[-][0-9]{2}[-][0-9]{4}[-][0-9]{4}[-][0-9]{1,18})$"
                },
                "perapur": {
                    "required": true,
                    "type": "string",
                    "pattern": "^(19[0-9][0-9]|2[0-9][0-9][0-9])[-/](0?[1-9]|1[0-2])$"
                },
                "tpinscestab": {
                    "required": true,
                    "type": "integer",
                    "minimum": 1,
                    "maximum": 4
                },
                "nrinscestab": {
                    "required": true,
                    "type": "string",
                    "maxLength": 14,
                    "pattern": "^[0-9]"
                },
                "indobra": {
                    "required": true,
                    "type": "integer",
                    "minimum": 0,
                    "maximum": 2
                },
                "cnpjprestador": {
                    "required": true,
                    "type": "string",
                    "maxLength": 14,
                    "pattern": "^[0-9]"
                },
                "vlrtotalbruto": {
                    "required": true,
                    "type": "number"
                },
                "vlrtotalbaseret": {
                   "required": true,
                   "type": "number"
                },
                "vlrtotalretprinc": {
                    "required": true,
                    "type": "number"
                },
                "vlrtotalretadic": {
                    "required": false,
                    "type": ["number","null"]
                },
                "vlrtotalnretprinc": {
                     "required": false,
                     "type": ["number","null"]
                },
                "vlrtotalnretadic": {
                     "required": false,
                     "type": ["number","null"]
                },
                "indcprb": {
                    "required": true,
                    "type": "integer",
                    "minimum": 0,
                    "maximum": 2
                },    
                "nfs": {
                   "required": true,
                   "type": "array",
                   "minItems": 1,
                   "maxItems": 500,
                   "items": {
                        "type": "object",
                        "properties": {
                            "serie": {
                                "required": true,
                                "type": "string",
                                "maxLength": 5
                            },
                            "numdocto": {
                                "required": true,
                                "type": "string",
                                "maxLength": 15
                            },
                            "dtemissaonf": {
                                "required": true,
                                "type": "string",
                                "pattern": "^(19[0-9][0-9]|2[0-9][0-9][0-9])[-/](0?[1-9]|1[0-2])[-/](0?[1-9]|[12][0-9]|3[01])$"
                            },
                            "vlrbruto": {
                                "required": true,
                                "type": "number"
                            },
                            "obs": {
                                "required": false,
                                "type": ["string","null"],
                                "maxLength": 250
                            },
                            "infotpserv": {
                                "required": true,
                                "type": "array",
                                "minItems": 1,
                                "maxItems": 9,
                                "items": {
                                    "type": "object",
                                    "properties": {
                                        "tpservico": {
                                            "required": true,
                                            "type": "string",
                                            "maxLength": 9,
                                            "pattern": "^[0-9]"
                                        },
                                        "vlrbaseret": {
                                            "required": true,
                                            "type": "number"
                                        },
                                        "vlrretencao": {
                                            "required": true,
                                            "type": "number"
                                        },
                                        "vlrretsub": {
                                            "required": false,
                                            "type": ["number","null"]
                                        },
                                        "vlrnretprinc": {
                                            "required": false,
                                            "type": ["number","null"]
                                        },
                                        "vlrservicos15": {
                                            "required": false,
                                            "type": ["number","null"]
                                        },
                                        "vlrservicos20": {
                                            "required": false,
                                            "type": ["number","null"]
                                        },
                                        "vlrservicos25": {
                                            "required": false,
                                            "type": ["number","null"]
                                        },
                                        "vlradicional": {
                                            "required": false,
                                            "type": ["number","null"]
                                        },
                                        "vlrnretadic": {
                                            "required": false,
                                            "type": ["number","null"]
                                        }                            
                                    }
                                }
                            }    
                        }
                    }
                },
                "infoprocretpr": {
                    "required": false,
                    "type": ["array","null"],
                    "minItems": 0,
                    "maxItems": 50,
                    "items": {
                        "type": "object",
                        "properties": {
                            "tpprocretprinc": {
                                "required": true,
                                "type": "integer",
                                "minimum": 1,
                                "maximum": 2
                            },
                            "nrprocretprinc": {
                                "required": true,
                                "type": "string",
                                "maxLength": 21
                            },
                            "codsuspprinc": {
                                "required": false,
                                "type": ["string","null"],
                                "maxLength": 14,
                                "pattern": "^[0-9]"
                            },
                            "valorprinc": {
                                "required": true,
                                "type": "number"
                            }                
                        }
                    }
                },
                "infoprocretad": {
                    "required": false,
                    "type": ["array","null"],
                    "minItems": 0,
                    "maxItems": 50,
                    "items": {
                        "type": "object",
                        "properties": {
                            "tpprocretadic": {
                                "required": true,
                                "type": "integer",
                                "minimum": 1,
                                "maximum": 2
                            },
                            "nrprocretadic": {
                                "required": true,
                                "type": "string",
                                "maxLength": 21
                            },
                            "codsuspadic": {
                                "required": false,
                                "type": ["string","null"],
                                "maxLength": 14,
                                "pattern": "^[0-9]"
                            },
                            "valoradic": {
                                "required": true,
                                "type": "number"
                            }                
                        }
                    }    
                }    
            }
        }';

        $edf_R2010 = utf8_encode($edf_R2010); 
        $std = json_decode($edf_R2010);

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
                echo sprintf("[%s] %s<br/>", $error['property'], $error['message']);
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
        $edf_R2010 = $this->edf_R2010;
        $fileName = $this->fileName;
        
        $std = json_decode($edf_R2010);

        try {
            
            //carrega a classe responsavel por lidar com os certificados
            $certificate = Certificate::readPfx($certif, $senha);
            
            //cria o evento e retorna o XML assinado
            $xml = Event::evtServTom(
                $config,
                $std,
                $certificate,
                '2017-08-03 10:37:00'
            )->toXml();
            
            //$xml = Event::r1000($config, $std, $certificate)->toXML();
            //$json = Event::evtInfoContri($config, $std, $certificate)->toJson();
            
            file_put_contents("$dir\\reinf\\xmls\\$fileName.xml", $xml);
            
            $evento = Event::evtServTom($config, $std);
    
            //instancia a classe responsável pela comunicação
            $tools = new Tools($config, $certificate);
            
            //executa o envio
            $response = $tools->enviarLoteEventos($tools::EVT_PERIODICOS, [$evento]);

            file_put_contents("$dir\\reinf\\xmls\\$fileName"."_response.xml", $response);
            
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

}