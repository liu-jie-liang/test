<?php

class BaseAction extends Yaf_Action_Abstract {
    public function getJs($jsFileName) {
        return JS_PATH . $jsFileName;
    }

    public function getCss($cssFileName) {
        return CSS_PATH . $cssFileName;
    }

    public function getImg($imgFileName) {
        return IMG_PATH . $imgFileName;
    }
    
    public function execute() {
        
    }
}