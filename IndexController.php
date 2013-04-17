<?php

/**
 * @property IndexModel $model
 */
class IndexController extends Controller {

    public function language() {
        if ($lang = strtolower($this->request->segment(1))) {
            $this->model->language = $lang;
        }
    }
}