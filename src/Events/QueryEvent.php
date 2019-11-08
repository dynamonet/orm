<?php

namespace Dynamo\ORM\Events;

use Dynamo\ORM\Events\Event;

class QueryEvent extends Event
{
    protected $sql;

    function __construct($name, $sql)
    {
        parent::__construct($name);
        $this->sql = $sql;
    }

    public function getSql()
    {
        return $this->sql;
    }
}