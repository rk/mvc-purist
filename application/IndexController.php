<?php
use Purist\Controller;

/**
 * @property IndexModel $model
 */
class IndexController extends Controller {

    public function languageAction() {
        if ($lang = strtolower($this->request->segment(1))) {
            $this->model->language = $lang;
        }
    }
}