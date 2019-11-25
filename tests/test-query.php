<?php

require __DIR__.'/../vendor/autoload.php';

use Dynamo\ORM\Query;

$query = (new Query())
    ->select('*')
    ->from('users')
    ->where([
        'active' => 1,
        'age >' => 10,
        'retired' => null,
        'type' => [ 'MASTER', 'ADMIN', 'MANAGER' ],
        'type NOT IN' => [ 'EMPLOYEE', 'SUPERVISOR' ],
        'age BETWEEN' => [ 25, 35 ] 
    ], false);

echo $query->getSql(true)."\n";
echo $query->getSql(false)."\n";