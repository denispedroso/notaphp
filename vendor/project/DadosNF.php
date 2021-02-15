<?php

namespace Project;

class DadosNF
{
    public $nNF;

    public $nCNPJ;

    public $tipoNF;

    public $content;

    public function __construct($content)
    {
        $this->content = $content;
    }

    public function getNumbers()
    {
        foreach ($this->content as $value) {
            if (strstr($value, 'B') == true) {
                $line = explode("|", $value);
                $this->nNF = $line[6];
                $this->tipoNF = $line[4];
                continue;
            }
            if (strstr($value, 'C02') == true) {
                $line = explode("|", $value);
                $this->nCNPJ = $line[1];
                break;
            }
        }
    }
}
