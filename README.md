This Yii2 command can fill your database with fake data.

You can switch between different data generators (or write your own) and
different database connectors.

Generators included:
- FakeGenerator: this is proxy to [`Faker`](https://github.com/fzaninotto/Faker)
and use same logic, paths and templates as [`yii2-faker`](https://github.com/yiisoft/yii2-faker) . Read yii2-faker documentation about
creating fixtures template file

Database connectors (called here as dbproviders):
- Csv: simple csv writer
- ActiveRecord: create model, fill with data and call ->save()
- YiiDAO: insert data using sql (via Yii's PDO), can insert multiple rows per one insert

Installation
------------

You should configure your application as follows
(usually in console.php; and you can use any alias, not only "faker"):

```php
'controllerMap' => [
 'faker' => [
     'class' => 'MiksIr\Yii2DbFaker\FakerController',
 ],
],
```

Call help for current generator and dbprovider

```
yii faker/help
```

Examples
--------

```
yii faker/generate --count=1000000 --dbprovider=YiiDAO generator_template=users dbprovider_table=users truncate=1
```

Truncate table users and create about 1 million rows using template from \@tests/unit/templates/fixtures/users.php