<?php

declare(strict_types=1);

namespace App;

class ApiOutput
{
    public $count;

    public $result;

    public function __construct($result)
    {
        $this->result = $result;
    }
}
