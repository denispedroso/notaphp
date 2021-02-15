<?php
namespace Retorno;

/**
 * Gera arquivo de texto, retornando valores a serem lidos no COBOL-NETEXPRESS 5.1
 *usando  http://github.com/nfephp-org/sped-nfe
 * @category  Retorno
 * @package   Retorno\Retorno
 * @copyright Project Copyright (c) 2018-2018
 * @license   http://www.gnu.org/licenses/lgpl.txt LGPLv3+
 * @license   https://opensource.org/licenses/MIT MIT
 * @license   http://www.gnu.org/licenses/gpl.txt GPLv3+
 * @author    Denis B. Pedroso <denisbpedroso at gmail dot com>
 * @link
 */

class Retorno
{
    public $tipo_ret;
    public $processo; //pic 9(002)
        /*
        1 - retorno de nota fiscal
        2 - retorno de cancelamento
        3 - retorno de inutilização
        4 - carta de correção
        */
    public $numero_nota_fiscal; //pic 9(009)
    public $numero_nf_final; //pic 9(009)
    public $chave_nfe; //pic x(044)
    public $protocolo; //pic x(040)
    public $data_autorizacao; //pic x(025)
    public $uf_autorizou; // pic x(02)
    public $status; //pic 9(004)
    public $identifica_origem_erro; // pic 9(001)
        /*
        1 - validador php
        2 - validador sefaz
        */
    public $descricao_erro; // pic x(512)

    public $dir; // diretorio para ser salvo
    public $row; // linha com os valores

    public $error = false;

    public function montaTxt()
    {
        $linha = [];

        array_push($linha, str_pad($this->processo, 2, '0', STR_PAD_LEFT));        

        array_push($linha, str_pad($this->numero_nota_fiscal, 9, '0', STR_PAD_LEFT));
        $this->numero_nota_fiscal = str_pad($this->numero_nota_fiscal, 9, '0', STR_PAD_LEFT);

        array_push($linha, str_pad($this->numero_nf_final, 9, '0', STR_PAD_LEFT));

        array_push($linha, str_pad($this->chave_nfe, 44, ' ', STR_PAD_RIGHT));

        array_push($linha, str_pad($this->protocolo, 40, ' ', STR_PAD_RIGHT));

        array_push($linha, str_pad($this->data_autorizacao, 25, ' ', STR_PAD_RIGHT));

        array_push($linha, str_pad($this->uf_autorizou, 2, '0', STR_PAD_LEFT));

        array_push($linha, str_pad($this->status, 4, '0', STR_PAD_LEFT));

        array_push($linha, $this->identifica_origem_erro);

        array_push($linha, str_pad($this->descricao_erro, 512, ' ', STR_PAD_RIGHT));
    
        $resultado = implode("", $linha);

        $dir = $this->dir;
        $tipo_ret = $this->tipo_ret;
        $numNF = $this->numero_nota_fiscal;

        $log = file_put_contents("$dir\\retorno\\Retorno_$tipo_ret$numNF.txt", $resultado);
        if ($log == false) {
            trigger_error("Erro ao gravar retorno: $this", E_USER_WARNING);
        }
    }
}
