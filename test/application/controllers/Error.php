<?php

/**
 * 未捕获的异常会交由ErrorController的errorAction方法处理
 * @author liujieliang
 *
 */
class ErrorController extends BaseAction {
    public function errorAction($exception) {
        $code = $exception->getCode();
        $message = $exception->getMessage();
        $file = $exception->getFile();
        $line = $exception->getLine();
    }
}