<?php

namespace Devium\Processes\Exceptions;

use Exception;

class PIDCanOnlyBeAnIntegerException extends Exception
{
    public function __construct()
    {
        parent::__construct('PID can only be an integer', 0, null);
    }
}
