# Active-ORM
ActiveRecord-based ORM for PHP 7, inspired in Eloquent and Yii2 ORM.

## Getting Started

Create a new PDO instance, and pass the instance to Query:
```php
use Dynamo\ORM\Query;
....
$pdo = new \PDO('mysql:dbname=goldoni;host=localhost;charset=utf8', 'root', 'root');
$query = (new Query($pdo))
```
or better yet, set the PDO globally:
```php
Query::setPdo($pdo);
....
$query = (new Query()) // this will use the static PDO instance.
```

## Query Builder
Fluent and intuitive Query Builder:
```php
$users = (new Query)
  ->select('*') // This is the default select
  ->from('users')
  ->where([
    'role' => 'ADMIN', // translates to "role = ?", where "?" will be securely replaced by the PDO layer
    'age > $minAge', // insecure! $minAge is not verified! However, we allow this form for convenience
    [ 'age', '<=', $maxAge ], // better
  ], false) // false "OR's" all the previous conditions. Default is true, which will "AND" all the conditions. 
  ->all(); // Fetches all the results
```

### Installing

```php
composer require dynamonet/orm
```

### Defining Models
Your database models classes should extend the ```Dynamo\ORM\ActiveModel``` class:

```php
<?php

namespace MyApp;

use Dynamo\ORM\ActiveModel;

class User extends ActiveModel
{
    //
}
```
By convention, the "snake case" name of the class will be used as the table name. If you want to specify a different table name, you can do so by simply overriding the static "getTableName" method:

```php
<?php

namespace MyApp;

use Dynamo\ORM\ActiveModel;

class User extends ActiveModel
{
    public static function getTableName()
    {
        return 'my_users_table';
    }
}
```

### Relationships
Defining relationships is done quite the same way you would in Yii2's ORM or Eloquent.

#### one-to-one relationships
(work in progress)



