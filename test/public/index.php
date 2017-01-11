<?php

define("APP", $_SERVER["APP"]);
define("APP_PATH", realpath(dirname(__FILE__) . '/../'));
define("CONF_PATH", APP_PATH . "/conf/");
define("CONF_FILE", CONF_PATH . "/application.ini");
define("JS_PATH", APP_PATH . "/public/js/");
define("CSS_PATH", APP_PATH . "/public/css/");
define("IMG_PATH", APP_PATH . "/public/img/");

$app  = new Yaf_Application(CONF_FILE);
$app->bootstrap() //call bootstrap methods defined in Bootstrap.php
->run();
