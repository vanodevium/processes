<?php

namespace Devium\Processes\Exceptions;

use Exception;

class LooksLikeBusyBoxException extends Exception
{
    public function __construct()
    {
        parent::__construct('Looks like Busy Box');
    }
}
