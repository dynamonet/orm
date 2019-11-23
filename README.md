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
