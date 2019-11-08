<?php

namespace Dynamo\ORM;

interface HydratableInterface
{
    public function hydrate($data);
    public function getHydratableFields();
    public function getIgnoreFields();
}