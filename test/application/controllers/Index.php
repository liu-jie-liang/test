<?php

class IndexController extends BaseAction {
   public function indexAction() {
       $this->redirect('/employees/employees/table');
   }
}