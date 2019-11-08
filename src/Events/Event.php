<?php

namespace Dynamo\ORM\Events;

class Event
{
    protected $name;

    function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}
