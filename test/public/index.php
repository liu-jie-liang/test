<?php

define("APP_PATH", realpath(dirname(__FILE__) . '/../'));
define("CONF_PATH", APP_PATH . "/conf/");
define("CONF_FILE", CONF_PATH . "/application.ini");

$app  = new Yaf_Application(CONF_FILE);
$app->bootstrap() //call bootstrap methods defined in Bootstrap.php
->run();
