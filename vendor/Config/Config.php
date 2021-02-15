<?php
namespace Config;

class Config
{
    public $line = "-------------------------------------------------\n\r";

    public $configs = [
        [
        "atualizacao" => "",
        "tpAmb" => 2,
        "razaosocial" => "",
        "siglaUF" => "",
        "cnpj" => "",
        "schemes" => "PL_009_V4",
        "versao" => "4.00",
        "tokenIBPT" => "",
        "CSC" => "",
        "CSCid" => "",
        "certificado" => ""
        ]
    ];

    public function __construct($dir)
    {
        if (is_file("$dir\\configs.txt")) {
            $configs = file_get_contents("$dir\\configs.txt");
            $this->configs = unserialize($configs);
        } else {
            $configs = [];
            $configs = serialize($configs);
            file_put_contents("$dir\\configs.txt", $configs);
            $this->configs = [];
        }
    }

    public function options()
    {
        $this->clearscreen();
        echo $this->line;
        echo "Opções:\n\r";
        echo "[1] -> Alterar Senha\n\r";
        echo "[2] -> Nome da Empresa\n\r";
        echo "[3] -> CNPJ da Empresa\n\r";
        echo "[31] -> SiglaUF\n\r";
        echo "[4] -> Listar Empresas\n\r";
        echo "[5] -> Tipo de ambiente\n\r";
        echo "[6] -> CSC\n\r";
        echo "[7] -> CSCid\n\r";
        echo "[8] -> Gerar Pastas\n\r";
        echo "[9] -> Sair do Config\n\r";
        echo "Insira uma das opções acima: ";
        $opcao = trim(fgets(STDIN));
        return $opcao;
    }

    public function clearscreen($out = true)
    {
        $clearscreen = chr(27)."[H".chr(27)."[2J";
        if ($out) {
            print $clearscreen;
        } else {
            return $clearscreen;
        }
    }

    public function alteraSenha($dir)
    {
        echo $this->line;
        echo "Informe a senha atual: ";
        $oldpass = trim(fgets(STDIN));

        if (is_file($dir."\master2.txt")) {
            $hash = file_get_contents($dir."\master2.txt");
        } else {
            $hash = file_get_contents($dir."\master.txt");
        }

        if (!password_verify($oldpass, $hash)) {
            echo "Senha inválida!";
            return;
        }
        echo "Informe a nova senha: ";
        $senha = trim(fgets(STDIN));
        echo "Confirme a nova senha: ";
        $senha2 = trim(fgets(STDIN));

        if ($senha != '' && $senha == $senha2) {
            $hash = password_hash($senha, PASSWORD_ARGON2I);
            $save = file_put_contents($dir."\master2.txt", $hash);
            if ($save == false) {
                echo "Erro ao salvar nova senha\n\r";
                return 9;
            }
            echo "Senha alterada com sucesso!\n\r";
            opcoes:
            echo 'Deseja retornar ao Menu de Opções?(s\n): ';
            $res =  strtolower(trim(fgets(STDIN)));
            if ($res == 's') {
                return;
            }
            if ($res == 'n') {
                exit();
            }
            goto opcoes;
        }
    }

    public function alteraNome($dir)
    {
        inserirNumero:
        echo "Numero da empresa:";
        $numEmpresa = trim(fgets(STDIN));
        $numEmpresa = (int)$numEmpresa;
        if (is_numeric($numEmpresa) == false || $numEmpresa > 5) {
            echo "Numero inválido, informe numero entre 1 e 5!";
            goto inserirNumero;
        }
        
        ini_set('default_charset', 'ISO-8859-1');
        echo $this->line;
        echo 'Nome atual: ' . $this->configs[$numEmpresa]["razaosocial"]. "\n\r";
        alterarnome:
        echo 'Deseja alterar o nome?(s\n): ';
        $res = trim(fgets(STDIN));
        if ($res == 'n') {
            ini_set('default_charset', 'UTF-8');
            return;
        }
        if ($res == 's') {
            goto informenome;
        }
        goto alterarnome;
        informenome:
        echo "Informe novo nome: ";
        $novoNome = trim(fgets(STDIN));
        $str = mb_convert_encoding($novoNome, "UTF-8", mb_internal_encoding());
        if ($str != '') {
            $this->configs[$numEmpresa]['razaosocial'] = $str;
            $configs = serialize($this->configs);
            $save = file_put_contents($dir."\configs.txt", $configs);
            if ($save == false) {
                echo "Erro ao salvar alterações\n\r";
                return 9;
            }
            echo "Nome alterado com sucesso!\n\r";
            ini_set('default_charset', 'UTF-8');
            opcoes:
            echo 'Deseja retornar ao Menu de Opções?(s\n): ';
            $res =  strtolower(trim(fgets(STDIN)));
            if ($res == 's') {
                return;
            }
            if ($res == 'n') {
                exit();
            }
            goto opcoes;
        }
    }

    public function alteraCnpj($dir)
    {
        inserirNumero:
        echo "Numero da empresa:";
        $numEmpresa = trim(fgets(STDIN));
        $numEmpresa = (int)$numEmpresa;
        if (is_numeric($numEmpresa) == false || $numEmpresa > 5) {
            echo "Numero inválido, informe numero entre 1 e 5!";
            goto inserirNumero;
        }

        echo $this->line;
        echo 'CNPJ atual: ' . $this->configs[$numEmpresa]["cnpj"]. "\n\r";
        alterar:
        echo 'Deseja alterar o CNPJ?(s\n): ';
        $res = trim(fgets(STDIN));
        if ($res == 'n') {
            return;
        }
        if ($res == 's') {
            goto inserir;
        }
        goto alterar;
        inserir:
        echo "Inserir novo CNPJ: ";
        $novoCnpj = trim(fgets(STDIN));
        if ($this->validar_cnpj($novoCnpj)) {
            $this->configs[$numEmpresa]['cnpj'] = $novoCnpj;
            $configs = serialize($this->configs);
            $save = file_put_contents($dir."\configs.txt", $configs);
            if ($save == false) {
                echo "Erro ao salvar alterações\n\r";
                return 9;
            }
            echo "CNPJ alterado com sucesso!\n\r";
            opcoes:
            echo 'Deseja retornar ao Menu de Opções?(s\n): ';
            $res =  strtolower(trim(fgets(STDIN)));
            if ($res == 's') {
                return;
            }
            if ($res == 'n') {
                exit();
            }
            goto opcoes;
        } else {
            echo "CNPJ inválido!\n\r";
            goto opcoes;
        }
    }

    public function siglaUF($dir)
    {
        inserirNumero:
        echo "Numero da empresa:";
        $numEmpresa = trim(fgets(STDIN));
        $numEmpresa = (int)$numEmpresa;
        if (is_numeric($numEmpresa) == false || $numEmpresa > 5) {
            echo "Numero inválido, informe numero entre 1 e 5!";
            goto inserirNumero;
        }
        
        ini_set('default_charset', 'ISO-8859-1');
        echo $this->line;
        echo 'SiglaUF atual: ' . $this->configs[$numEmpresa]["siglaUF"]. "\n\r";
        alterarUF:
        echo 'Deseja alterar a UF?(s\n): ';
        $res = trim(fgets(STDIN));
        if ($res == 'n') {
            ini_set('default_charset', 'UTF-8');
            return;
        }
        if ($res == 's') {
            goto informeUF;
        }
        goto alterarUF;
        informeUF:
        echo "Informe nova UF: ";
        $novaUF = trim(fgets(STDIN));
        $str = mb_convert_encoding($novaUF, "UTF-8", mb_internal_encoding());
        $str = strtoupper($str);
        if ($str!= '') {
            if ($this->checkUF($str)) {
                echo "UF inválida!\n\r";
                goto informeUF;
            }
            $this->configs[$numEmpresa]['siglaUF'] = $str;
            $configs = serialize($this->configs);
            $save = file_put_contents($dir."\configs.txt", $configs);
            if ($save == false) {
                echo "Erro ao salvar alterações\n\r";
                return 9;
            }
            echo "UF alterada com sucesso!\n\r";
            ini_set('default_charset', 'UTF-8');
            opcoes:
            echo 'Deseja retornar ao Menu de Opções?(s\n): ';
            $res =  strtolower(trim(fgets(STDIN)));
            if ($res == 's') {
                return;
            }
            if ($res == 'n') {
                exit();
            }
            goto opcoes;
        } else {
            echo "UF inválida!\n\r";
            goto informeUF;
        }
    }

    public function checkUF($uf)
    {
        $estados = array(
            'AC'=>'Acre',
            'AL'=>'Alagoas',
            'AP'=>'Amapá',
            'AM'=>'Amazonas',
            'BA'=>'Bahia',
            'CE'=>'Ceará',
            'DF'=>'Distrito Federal',
            'ES'=>'Espírito Santo',
            'GO'=>'Goiás',
            'MA'=>'Maranhão',
            'MT'=>'Mato Grosso',
            'MS'=>'Mato Grosso do Sul',
            'MG'=>'Minas Gerais',
            'PA'=>'Pará',
            'PB'=>'Paraíba',
            'PR'=>'Paraná',
            'PE'=>'Pernambuco',
            'PI'=>'Piauí',
            'RJ'=>'Rio de Janeiro',
            'RN'=>'Rio Grande do Norte',
            'RS'=>'Rio Grande do Sul',
            'RO'=>'Rondônia',
            'RR'=>'Roraima',
            'SC'=>'Santa Catarina',
            'SP'=>'São Paulo',
            'SE'=>'Sergipe',
            'TO'=>'Tocantins'
            );

        if (array_key_exists($uf, $estados)) {
            return false;
        }
        return true;
    }

    public function validar_cnpj($cnpj)
    {
        $cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);

        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        // Valida tamanho
        if (strlen($cnpj) != 14) {
            return false;
        }
        // Valida primeiro dígito verificador
        for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
            $soma += $cnpj{$i} * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        $resto = $soma % 11;
        if ($cnpj{12} != ($resto < 2 ? 0 : 11 - $resto)) {
            return false;
        }
        // Valida segundo dígito verificador
        for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++) {
            $soma += $cnpj{$i} * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        $resto = $soma % 11;
        return $cnpj{13} == ($resto < 2 ? 0 : 11 - $resto);
    }

    public function ListarEmpresas($dir)
    {
        echo $this->line."\n\r";
        echo "Empresas cadastradas"."\n\r";

        foreach ($this->configs as $key => $config) {
            echo "Numero: ".$key." Nome: ".$config["razaosocial"]."\n\r";
        }
        echo $this->line."\n\r";
        $quantEmpresas = count($this->configs);

        if ($quantEmpresas < 5) {
            echo 'Deseja incluir empresa?(s\n): ';
            $res = trim(fgets(STDIN));
            $res = strtolower($res);
            if ($res == 's') {
                $this->incluirEmpresa($dir, $quantEmpresas);
            }
        } else {
            echo 'Deseja alterar empresa?(s\n): ';
            $res = trim(fgets(STDIN));
            $res = strtolower($res);
            if ($res == 's') {
                $this->incluirEmpresa($dir, $quantEmpresas);
            }
        }
    }

    public function incluirEmpresa($dir)
    {
        echo $this->line."\n\r";
        echo "Incluir/alterar Empresa"."\n\r";

        $cd = [
            "atualizacao" => "",
            "tpAmb" => 2,
            "razaosocial" => "",
            "siglaUF" => "",
            "cnpj" => "",
            "schemes" => "PL_009_V4",
            "versao" => "4.00",
            "tokenIBPT" => "",
            "CSC" => "",
            "CSCid" => "",
            "certificado" => "",
            "senhaCertificado" => ""
        ];
        inserirNumero:
        echo "Numero da empresa:";
        $key = trim(fgets(STDIN));
        $key = (int)$key;
        if (is_numeric($key) == false || $key > 5) {
            echo "Numero inválido, informe numero entre 1 e 5!";
            goto inserirNumero;
        }

        if (isset($this->configs[$key])) {
            $cd = $this->configs[$key];
        }

        echo 'Razão Social:';
        ini_set('default_charset', 'ISO-8859-1');
        
        $cd["razaosocial"] = trim(fgets(STDIN));
        inserirCNPJ:
        echo 'CNPJ: ';
        $cd["cnpj"]  = trim(fgets(STDIN));
        if (!$this->validar_cnpj($cd["cnpj"])) {
            echo "CNPJ inválido!\n\r";
            goto inserirCNPJ;
        }
        echo 'Arquivo Certificado: ';
        $cd["certificado"]  = trim(fgets(STDIN));
        echo 'Senha Certificado: ';
        $cd["senhaCertificado"]  = trim(fgets(STDIN));
        informeUF:
        echo 'UF: ';
        $cd["siglaUF"]  = trim(fgets(STDIN));
        $cd["siglaUF"] = mb_convert_encoding($cd["siglaUF"], "UTF-8", mb_internal_encoding());
        $cd["siglaUF"] = strtoupper($cd["siglaUF"]);
        if ($cd["siglaUF"] != '') {
            if ($this->checkUF($cd["siglaUF"])) {
                echo "UF inválida!\n\r";
                goto informeUF;
            }
        } else {
            echo "UF inválida!\n\r";
            goto informeUF;
        }
        echo 'CSC: ';
        $cd["CSC"]  = trim(fgets(STDIN));
        echo 'CSCid: ';
        $cd["CSCid"]  = trim(fgets(STDIN));

        $this->configs[$key] = $cd;

        $configs = serialize($this->configs);
        $save = file_put_contents($dir.'\configs.txt', $configs);
        if ($save == false) {
            echo "Erro ao salvar alterações\n\r";
            ini_set('default_charset', 'UTF-8');
            exit();
        }
        ini_set('default_charset', 'UTF-8');
    }

    public function gerarPastas($dir)
    {
        echo $this->line."\n\r";
        echo 'Deseja gerar pastas ?(s\n): ';
        $res = trim(fgets(STDIN));
        if ($res == 'n') {
            ini_set('default_charset', 'UTF-8');
            return;
        }
        if ($res == 's') {
            goto gerarPastas;
        }
        gerarPastas:
        $pastasBase = array(
            'txt',
            'txtreinf',
            'certifs'
        );

        $pastas = array(
            'xmls',
            'retorno',
            'images',
            'corrigidas',
            'enviadas',
            'protocolos',
            'recibos',
            'canceladas',
            'assinadas',
            'inutilizadas',
            'pdf'
        );

        $pastasReinf = array(
            'xmls',
            'jsonSchemes'
        );

        // variaveis para definir progresso
        $progress = 0;
        $liveProgress = 0;
        $result = 0;
        

        $configs = file_get_contents("$dir\\configs.txt");
        $configs = unserialize($configs);
        $pastasCnpj = [];
        foreach ($configs as $empresa) {
            array_push($pastasCnpj, $empresa['cnpj']);
            ++$progress;
        }

        echo "Iniciando, aguarde, $result% concluído...\n\r";
        system("mkdir $dir");

        foreach ($pastasBase as $pasta) {
            system("mkdir $dir\\$pasta");
            echo "$dir\\$pasta \n\r";
        }

        //cria pastas de cnpj
        foreach ($pastasCnpj as $pastaCnpj) {
            system("mkdir $dir\\$pastaCnpj");
            echo "$dir\\$pastaCnpj \n\r";

            foreach ($pastas as $pasta) {
                system("mkdir $dir\\$pastaCnpj\\$pasta");
                echo "$dir\\$pastaCnpj\\$pasta \n\r";
            }
    
            system("mkdir $dir\\$pastaCnpj\\reinf");
            foreach ($pastasReinf as $pasta) {
                $subpath = "$dir\\$pastaCnpj\\reinf\\$pasta";
                system("mkdir $subpath");
                echo "$subpath \n\r";
            }
            ++$liveProgress;
            $result = ($liveProgress * 100 / $progress);
            echo "$result% concluído...\n\r";
        }
        echo "Concluído! \n\r";
        echo 'Deseja retornar ao menu ?(s\n): ';
        $res = trim(fgets(STDIN));
        if ($res == 'n') {
            exit();
        }
        if ($res == 's') {
            return "";
        }
    }
}
