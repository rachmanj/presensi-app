<?php

namespace App\Exceptions;

use Exception;

class MatrixRuleNotFoundException extends Exception
{
    public function __construct(
        public readonly string $homeSite,
        public readonly string $visitSite,
        string $message = 'Matrix rule not found',
    ) {
        parent::__construct($message);
    }
}
