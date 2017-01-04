<?php

/* bootstrap class should be defined under ./application/Bootstrap.php */
class Bootstrap extends Yaf_Bootstrap_Abstract {
    public function _initConfig(Yaf_Dispatcher $dispatcher) {
        
    }
    
    public function _initPlugin(Yaf_Dispatcher $dispatcher) {
        
    }
    
    public function _initTimeZone(Yaf_Dispatcher $dispatcher) {
        $config = Yaf_Application::app()->getConfig();
        $timeZone = $config->timezone;
        date_default_timezone_set($timeZone);
    }
    
    public function _initSession(Yaf_Dispatcher $dispatcher) {
        
    }
    
    public function _initRouter(Yaf_Dispatcher $dispather) {
        
    }
}