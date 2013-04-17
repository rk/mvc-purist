<?php

/**
 * @property IndexModel $model
 */
class IndexController extends Controller {

    public function language() {
        if (isset($_GET['lang'])) {
            $this->model->language = $_GET['lang'];
        }
    }
}