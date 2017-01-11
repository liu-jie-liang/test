<?php

class IndexController extends BaseAction {
   public function indexAction() {
       $app = APP;
       $this->redirect("/$app/employees/employees/table");
   }
}