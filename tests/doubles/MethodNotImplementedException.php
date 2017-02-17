<?php
namespace TestDoubles;

use Exception;

class MethodNotImplementedException extends \Exception
{
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        $caller = debug_backtrace()[1];
        $message = trim("Method {$caller['class']}::{$caller['function']}() not implemented. $message");

        parent::__construct($message, $code, $previous);
    }
}
