<?php

class IndexModel extends Model {

    protected $texts = array(
        'en' => 'Hello, World',
        'es' => 'Hola mundo'
    );

    public $language = 'en';

    public function getText() {
        if (isset($this->texts[$this->language])) {
            return $this->texts[$this->language];
        }

        return "Can't speak {$this->language}";
    }

    public function getLanguages() {
        return array_keys($this->texts);
    }
}