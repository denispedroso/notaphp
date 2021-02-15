<?php

namespace Project;

class Files
{
    public $adress;

    public $file;

    public $content;

    public $fileName;

    public $type;

    public function __construct($adress)
    {
        $this->adress = $adress;
    }

    public function findFiles()
    {
        $adress = $this->adress;
        
        $files = scandir($this->adress);
        $this->file = end($files);
        $file = $this->file;
        
        if (is_file("$adress\\$file")) {
            $this->content = file("$adress\\$file");
        } else {
            trigger_error("Não foi possivel abrir o arquivo : $file ", E_USER_WARNING);
            exit();
        }
        return $file;
    }

    public function getFileName()
    {
        $adress = $this->adress;
        $file = $this->file;

        $fileName = pathinfo("$adress\\$file", PATHINFO_FILENAME);
        return $this->fileName = strtolower($fileName);
    }

    public function getFileType()
    {
        return $this->type = substr($this->fileName, 0, 3);
    }
}
