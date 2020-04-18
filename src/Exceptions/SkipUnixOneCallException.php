<?php

namespace Devium\Processes\Exceptions;

use Exception;

class SkipUnixOneCallException extends Exception
{
    public function __construct()
    {
        parent::__construct('Skip UnixOne() call', 0, null);
    }
}
