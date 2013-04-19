<?php

/**
 * @property IndexController $controller
 * @property IndexModel      $model
 */
class IndexView extends View {

    static public $controller_class = 'IndexController';

    public function render() {
        echo "<p>", $this->model->getText(), "</p>";

        foreach ($this->model->getLanguages() as $language) {
            echo '<a href="', url("language/$language"), '">', $language, '</a> ';
        }
    }
}