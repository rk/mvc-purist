<?php

class IndexModel extends Model {

    public $language = 'en';

    protected $texts = array(
        'en' => 'Hello world',
        'es' => 'Hola mundo',
        'nl' => 'Hallo wereld',
        'fi' => 'Moi maailma',
    );

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