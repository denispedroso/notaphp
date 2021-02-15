<?php

namespace Project;

use NFePHP\NFe\Convert;
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use NFePHP\DA\NFe\Danfe;
use NFePHP\DA\NFe\Danfce;
use NFePHP\DA\Legacy\FilesFolders;
use Retorno\Retorno;
use NFePHP\NFe\Complements;

/**
 *  Recebe arquivos de texto de NFe, converte para XML, assina, e envia para a receita
 *  usando  http://github.com/nfephp-org/sped-nfe
 *
 * @category Project
 *
 * @package   Project\Project
 * @author    Denis B. Pedroso <denisbpedroso@gmail.com>
 * @copyright 2018-2018 DTP Processamento de Dados LTDA
 * @license   http://www.gnu.org/licenses/lgpl.txt LGPLv3+
 * @version   Release:1.0.1
 * @link      link
 */
class Project
{
    /**
     * @var string $config Configuracoes
     * */
    public $config;
    /**
     * @var string $dir Diretorio Base
     */
    public $dir;
    /**
     * @var string $json Arquivo Json
     */
    public $json;
    /**
     * @var string $certif Certificado Digital
     */
    public $certif;
    /**
     * @var string $nNF Numero da nota
     */
    public $nNF;
    /**
     * @var string $tipoNF Tipo de nota
     */
    public $tipoNF;
    /**
     * @var string $senha Senha
     */
    public $senha;

    public $txt;

    public $fileName;

    public $emails;

    /**
     *  Constroi a classe Project
     *
     * @param string $dir    Traz o nome do diretório de
     *                       origem
     * @param string $config Passa as confiracoes da empresa
     * @param string $certif Passa o nome do arquivo do certificado
     * @param string $nNF    Numero NF
     * @param string $tipoNF NFe ou NFCe
     * @param string $senha  Senha
     *
     * @return void
     */
    public function __construct($dir, $config, $certif, $nNF, $tipoNF, $senha, $txt, $fileName, $emails)
    {
        $this->dir = $dir;

        $this->config = $config;

        $this->json = json_encode($this->config);

        $this->certif = file_get_contents($certif);

        $this->nNF = $nNF;

        $this->tipoNF = $tipoNF;

        $this->senha = $senha;

        $this->txt = $txt;

        $this->fileName = $fileName;

        $this->emails = $emails;
    }

    /**
     * Converte o TXT em XML
     *
     * @param string $dir      traz o nome do diretório de
     *                         origem
     * @param string $fileName passo o nome do arquivo .txt
     * @param string $txt      passa o conteúdo do
     *                         .txt
     * @param string $json     passa dados da empresa
     * @param string $certif   passa dados certificado
     * @param string $emails   email
     *
     * @return value
     */
    public function convert()
    {
        try {
            $dir = $this->dir;
            $fileName = $this->fileName;

            $conv = new Convert($this->txt, "LOCAL_V12");
            $axml = $conv->toXML();
            foreach ($axml as $xml) {
                // salva o arquivo XML
                $xmlPath = file_put_contents(
                    "$dir\\xmls\\$fileName.xml",
                    $xml
                );
                if ($xmlPath) {
                    //chama a funcao assinar()
                    $xmlPath = "$dir\\xmls\\$fileName.xml";
                    $retorno = $this->assinar($xmlPath);
                    return;
                } else {
                    // Cria Arquivo de retorno
                    $ret = new Retorno();
                    if ($this->tipoNF == 55) {
                        $ret->processo = 1;
                        $ret->tipo_ret = "Nfe";
                    } else {
                        $ret->processo = 5;
                        $ret->tipo_ret = "Nfce";
                    }
                    $ret->numero_nota_fiscal = $this->nNF;
                    $ret->numero_nf_final = 0;
                    $ret->chave_nfe = "";
                    $ret->protocolo = "";
                    $ret->data_autorizacao = "";
                    $ret->uf_autorizou = "";
                    $ret->status = 0000;
                    $ret->identifica_origem_erro = 1;
                    $ret->descricao_erro = "Erro ao converter arquivo em XML !";
                    $ret->dir = $dir;

                    $ret->montaTxt();
                    $stripedDir = substr($dir, 0, -15);
                    unlink("$stripedDir\\txt\\$fileName.txt");

                    trigger_error(print_r($ret, true), E_USER_WARNING);
                    return;
                }
            }
        } catch (\Exception $e) {
            //tratar exceptions
            $retorno = str_replace("\n", "<br/>", $e->getMessage());
            trigger_error(print_r($retorno, true), E_USER_WARNING);
        }
    }

    /**
     *  Assina o arquivo XML
     *
     * @param string $dir      traz o nome do diretório de
     *                         origem
     * @param string $xmlPath  camiho xml
     * @param string $fileName passo o nome do arquivo .txt
     * @param string $json     dados empresa
     * @param string $certif   dados certif
     * @param string $emails   email
     *
     * @return value
     */
    public function assinar($xmlPath)
    {
        $dir = $this->dir;
        $json = $this->json;
        $certif = $this->certif;
        $fileName = $this->fileName;
        $emails = $this->emails;

        //Pega os dados do XML
        $xml = file_get_contents($xmlPath);

        $tools = new Tools($json, Certificate::readPfx($certif, $this->senha));
        if ($this->tipoNF == '65') {
            $tools->model('65');
        }

        //Assina o XML
        $xmlAssinado = $tools->signNFe($xml);

        //Salva o xml assinado na pasta assinadas
        $xmlPath = file_put_contents(
            "$dir\\assinadas\\$fileName.xml",
            $xmlAssinado
        );
        if ($xmlPath) {
            // chama enviar()
            $retorno = $this->enviar($xmlAssinado);

            if ($retorno->error) {
                trigger_error(print_r($retorno, true), E_USER_WARNING);
            }

            return;
        } else {
            // Cria Arquivo de retorno
            $ret = new Retorno();
            if ($this->tipoNF == 55) {
                $ret->processo = 1;
                $ret->tipo_ret = "Nfe";
            } else {
                $ret->processo = 5;
                $ret->tipo_ret = "Nfce";
            }
            $ret->numero_nota_fiscal = $this->nfNF;
            $ret->chave_nfe = "";
            $ret->protocolo = "";
            $ret->status = 0000;
            $ret->identifica_origem_erro = 1;
            $ret->descricao_erro = "Erro ao assinar o arquivo!";

            $ret->data_autorizacao = "";
            $ret->uf_autorizou = "";

            $ret->numero_nf_final = 0;

            $ret->dir = $dir;
            $ret->montaTxt();

            $stripedDir = substr($dir, 0, -15);
            unlink("$stripedDir\\txt\\$fileName.txt");

            trigger_error(print_r($ret, true), E_USER_WARNING);
            return;
        }
    }

    /**
     *  Função para enviar o Xml Assinado em pasta /assinadas
     *
     * @param string $xmlAssinado passa o NFe.xml assinado
     * @param string $dir         traz o nome do diretório de
     *                            origem
     * @param string $fileName    passo o nome do arquivo .txt
     * @param string $json        passa dados da empresa
     * @param string $certif      passa dados do certificado
     * @param string $emails      email
     *
     * @return value
     */
    public function enviar($xmlAssinado)
    {
        $dir = $this->dir;
        $json = $this->json;
        $certif = $this->certif;
        $fileName = $this->fileName;

        $tools = new Tools($json, Certificate::readPfx($certif, $this->senha));
        $idLote = str_pad(100, 15, '0', STR_PAD_LEFT); // Identificador do lote
        if ($this->tipoNF == '65') {
            $tools->model('65');
        }

        $resp = $tools->sefazEnviaLote([$xmlAssinado], $idLote);

        $st = new Standardize();
        $std = $st->toStd($resp);

        if ($std->cStat != 103) {
            // Cria Arquivo de retorno
            $ret = new Retorno();
            if ($this->tipoNF == 55) {
                $ret->processo = 1;
                $ret->tipo_ret = "Nfe";
            } else {
                $ret->processo = 5;
                $ret->tipo_ret = "Nfce";
            }
            $ret->numero_nota_fiscal = $this->nNF;
            $ret->chave_nfe = "";
            $ret->protocolo = $std->infRec->nRec;
            $ret->status = $std->cStat;
            $ret->identifica_origem_erro = 2;
            $ret->descricao_erro = $std->cStat . $std->xMotivo;

            $ret->data_autorizacao = "";
            $ret->uf_autorizou = "";

            $ret->numero_nf_final = 0;

            $ret->dir = $dir;

            $ret->montaTxt();
            $stripedDir = substr($dir, 0, -15);
            unlink("$stripedDir\\txt\\$fileName.txt");
            $ret->error = true;
            return $ret;
        }
        // Vamos usar a variável $recibo para consultar o status da nota
        $recibo = $std->infRec->nRec;

        //Salva o recibo na pasta recibos
        $xmlPath = file_put_contents("$dir\\recibos\\$fileName.xml", $resp);

        pegarProtocolo:
        // Espera por 1 segundo para pegar protocolo
        sleep(1);
        $protocolo = $tools->sefazConsultaRecibo($recibo);

        $stdCl = new Standardize($protocolo);
        //$std irá conter uma representação em stdClass do XML
        $std = $stdCl->toStd();

        $cStat2 = $std->protNFe->infProt->cStat;

        if ($cStat2 == 105) {
            goto pegarProtocolo;
        }

        // verifica se o Cstat é 100
        if ($cStat2 != 100) {
            // Cria Arquivo de retorno
            $ret = new Retorno();
            if ($this->tipoNF == 55) {
                $ret->processo = 1;
                $ret->tipo_ret = "Nfe";
            } else {
                $ret->processo = 5;
                $ret->tipo_ret = "Nfce";
            }
            $ret->numero_nota_fiscal = $this->nNF;
            $ret->chave_nfe = $std->protNFe->infProt->chNFe;
            if (isset($std->protNFe->infProt->nProt)) {
                $ret->protocolo = $std->protNFe->infProt->nProt;
            } else {
                $ret->protocolo = $std->nRec;
            }
            $ret->status = $std->protNFe->infProt->cStat;
            $ret->identifica_origem_erro = 2;
            $ret->descricao_erro = $std->protNFe->infProt->xMotivo;

            $ret->data_autorizacao = "";
            $ret->uf_autorizou = "";

            $ret->numero_nf_final = 0;

            $ret->dir = $dir;

            $ret->montaTxt();
            $stripedDir = substr($dir, 0, -15);
            unlink("$stripedDir\\txt\\$fileName.txt");
            $ret->error = true;
            return $ret;
        }

        //Salva o protocolo na pasta recibos
        $xmlPath = file_put_contents(
            "$dir\\protocolos\\$fileName.xml",
            $protocolo
        );

        try {
            $xmlProtocolado = Complements::toAuthorize($xmlAssinado, $protocolo);
            $xmlPath = file_put_contents(
                "$dir\\enviadas\\$fileName.xml",
                $xmlProtocolado
            );
        } catch (\Exception $e) {
            trigger_error(print_r($e, true), E_USER_WARNING);
        }

        if ($xmlPath) {
            // Cria Arquivo de retorno
            $ret = new Retorno();
            if ($this->tipoNF == 55) {
                $ret->processo = 1;
                $ret->tipo_ret = "Nfe";
            } else {
                $ret->processo = 5;
                $ret->tipo_ret = "Nfce";
            }
            $ret->numero_nota_fiscal = $this->nNF;
            $ret->chave_nfe = $std->protNFe->infProt->chNFe;
            if (isset($std->protNFe->infProt->nProt)) {
                $ret->protocolo = $std->protNFe->infProt->nProt;
            } else {
                $ret->protocolo = $std->nRec;
            }
            $ret->status = $std->protNFe->infProt->cStat;
            $ret->identifica_origem_erro = 2;
            $ret->descricao_erro = $std->protNFe->infProt->xMotivo;

            $ret->data_autorizacao = $std->protNFe->infProt->dhRecbto;
            $ret->uf_autorizou = $std->cUF;

            $ret->numero_nf_final = 0;

            $ret->dir = $dir;

            $ret->montaTxt();

            //delete incoming Txt file
            $stripedDir = substr($dir, 0, -15);
            unlink("$stripedDir\\txt\\$fileName.txt");

            //gera a DANFE ou DANFC-e
            $xml = "$dir\\enviadas\\$fileName.xml";

            $docxml = FilesFolders::readFile($xml);

            $pathLogo = (file_exists("$dir\\images\\logo.jpg")) ? "$dir\\images\\logo.jpg" : '';

            if ($this->tipoNF == 65) {
                try {
                    $danfce = new Danfce($docxml, $pathLogo, 0);
                    $id = $danfce->monta();
                    $pdf = $danfce->render();
                    $pathtopdf = "$dir\\pdf\\$fileName.pdf";
                    file_put_contents($pathtopdf, $pdf);
                    exec('PDFtoPrinter "' . $pathtopdf . '"');
                } catch (InvalidArgumentException $e) {
                    trigger_error("Ocorreu um erro durante o processamento do PDF DANFCE :" . $e->getMessage(), E_USER_WARNING);
                }
            }
            if ($this->tipoNF == 55) {
                try {
                    $danfe = new Danfe($docxml, 'P', 'A4', "$dir\\images\\logo.jpg", 'I', '');
                    $id = $danfe->montaDANFE();
                    $pdf = $danfe->render();
                    file_put_contents("$dir\\pdf\\$fileName.pdf", $pdf);
                } catch (InvalidArgumentException $e) {
                    trigger_error("Ocorreu um erro durante o processamento do PDF:" . $e->getMessage(), E_USER_WARNING);
                }
            }

            return $ret;
        } else {
            // Cria Arquivo de retorno
            $ret = new Retorno();
            if ($this->tipoNF == 55) {
                $ret->processo = 1;
                $ret->tipo_ret = "Nfe";
            } else {
                $ret->processo = 5;
                $ret->tipo_ret = "Nfce";
            }
            $ret->numero_nota_fiscal = $this->nNF;
            $ret->chave_nfe = $std->protNFe->infProt->chNFe;
            if (isset($std->protNFe->infProt->nProt)) {
                $ret->protocolo = $std->protNFe->infProt->nProt;
            } else {
                $ret->protocolo = $std->nRec;
            }
            $ret->status = $std->protNFe->infProt->cStat;
            $ret->identifica_origem_erro = 2;
            $ret->descricao_erro = $std->protNFe->infProt->xMotivo;

            $ret->data_autorizacao = $std->protNFe->infProt->dhRecbto;
            $ret->uf_autorizou = $std->cUF;

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

    /**
     * Consulta o protocolo
     *
     * @param string $fileName nome do arquivo
     * @param string $recibo   numero do recibo
     *
     * @return $ret
     */
    public function consultaProtocolo($fileName, $recibo)
    {
        $dir = $this->dir;
        $json = $this->json;
        $certif = $this->certif;

        $tools = new Tools($json, Certificate::readPfx($certif, $this->senha));
        $idLote = str_pad(100, 15, '0', STR_PAD_LEFT); // Identificador do lote

        $protocolo = $tools->sefazConsultaRecibo($recibo);

        $stdCl = new Standardize($protocolo);
        //$std irá conter uma representação em stdClass do XML
        $std = $stdCl->toStd();

        $cStat2 = $std->protNFe->infProt->cStat;
        $xMotivo = $std->protNFe->infProt->xMotivo;

        // verifica se o Cstat é 100
        if ($cStat2 != 100) {
            // Cria Arquivo de retorno
            $ret = new Retorno();
            if ($this->tipoNF == 55) {
                $ret->processo = 1;
                $ret->tipo_ret = "Nfe";
            } else {
                $ret->processo = 5;
                $ret->tipo_ret = "Nfce";
            }
            $ret->numero_nota_fiscal = $this->nNF;
            $ret->chave_nfe = $std->protNFe->infProt->chNFe;
            if (isset($std->protNFe->infProt->nProt)) {
                $ret->protocolo = $std->protNFe->infProt->nProt;
            } else {
                $ret->protocolo = $std->nRec;
            }
            $ret->status = $std->protNFe->infProt->cStat;
            $ret->identifica_origem_erro = 2;
            $ret->descricao_erro = $std->protNFe->infProt->xMotivo;

            $ret->data_autorizacao = "";
            $ret->uf_autorizou = "";

            $ret->numero_nf_final = 0;

            $ret->dir = $dir;

            $ret->montaTxt();
            $ret->error = true;
            return $ret;
        }

        $xmlAssinado = file_get_contents("$dir\\assinadas\\$fileName.xml");

        //Salva o protocolo na pasta recibos
        $xmlPath = file_put_contents(
            "$dir\\protocolos\\$fileName.xml",
            $protocolo
        );

        try {
            $xmlProtocolado = Complements::toAuthorize($xmlAssinado, $protocolo);
            $xmlPath = file_put_contents(
                "$dir\\enviadas\\$fileName.xml",
                $xmlProtocolado
            );
        } catch (\Exception $e) {
            trigger_error(print_r($e, true), E_USER_WARNING);
        }

        if ($xmlPath) {
            // Cria Arquivo de retorno
            $ret = new Retorno();
            if ($this->tipoNF == 55) {
                $ret->processo = 1;
                $ret->tipo_ret = "Nfe";
            } else {
                $ret->processo = 5;
                $ret->tipo_ret = "Nfce";
            }
            $ret->numero_nota_fiscal = $this->nNF;
            $ret->chave_nfe = $std->protNFe->infProt->chNFe;
            if (isset($std->protNFe->infProt->nProt)) {
                $ret->protocolo = $std->protNFe->infProt->nProt;
            } else {
                $ret->protocolo = $std->nRec;
            }
            $ret->status = $std->protNFe->infProt->cStat;
            $ret->identifica_origem_erro = 2;
            $ret->descricao_erro = $std->protNFe->infProt->xMotivo;

            $ret->data_autorizacao = $std->protNFe->infProt->dhRecbto;
            $ret->uf_autorizou = $std->cUF;

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
            if ($this->tipoNF == 55) {
                $ret->processo = 1;
                $ret->tipo_ret = "Nfe";
            } else {
                $ret->processo = 5;
                $ret->tipo_ret = "Nfce";
            }
            $ret->numero_nota_fiscal = $this->nNF;
            $ret->chave_nfe = $std->protNFe->infProt->chNFe;
            if (isset($std->protNFe->infProt->nProt)) {
                $ret->protocolo = $std->protNFe->infProt->nProt;
            } else {
                $ret->protocolo = $std->nRec;
            }
            $ret->status = $std->protNFe->infProt->cStat;
            $ret->identifica_origem_erro = 2;
            $ret->descricao_erro = $std->protNFe->infProt->xMotivo;

            $ret->data_autorizacao = $std->protNFe->infProt->dhRecbto;
            $ret->uf_autorizou = $std->cUF;

            $ret->numero_nf_final = 0;

            $ret->dir = $dir;

            $ret->montaTxt();

            //delete incoming Txt file
            $fileName = str_replace("nfe", "Rec", $fileName);
            $stripedDir = substr($dir, 0, -15);
            unlink("$stripedDir\\txt\\$fileName.txt");
            $ret->error = true;
            return $ret;
        }
    }
}
