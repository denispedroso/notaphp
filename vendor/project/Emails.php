<?php

namespace Project;

class Emails
{
    public $emails = [];

    public $content;

    public function __construct($content)
    {
        $value = end($content);
        if (strstr($value, '@') == true) {
            $lines = explode("|", $value);
            foreach ($lines as $key => $linha) {
                if ($key == 0 || $linha == "" || strstr($linha, '@') == false) {
                    continue;
                }
                array_push($this->emails, $linha);
            }
            array_pop($content);
            
            $this->content = $content;
        }
        $this->content = $content;
    }
}
