<p align="center">
    <a href="https://github.com/yii2tech" target="_blank">
        <img src="https://avatars2.githubusercontent.com/u/12951949" height="100px">
    </a>
    <h1 align="center">ActiveRecord Search Model Extension for Yii2</h1>
    <br>
</p>

This extension provides unified search model for Yii ActiveRecord.

For license information check the [LICENSE](LICENSE.md)-file.

[![Latest Stable Version](https://img.shields.io/packagist/v/yii2tech/ar-search.svg)](https://packagist.org/packages/yii2tech/ar-search)
[![Total Downloads](https://img.shields.io/packagist/dt/yii2tech/ar-search.svg)](https://packagist.org/packages/yii2tech/ar-search)
[![Build Status](https://travis-ci.org/yii2tech/ar-search.svg?branch=master)](https://travis-ci.org/yii2tech/ar-search)


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist yii2tech/ar-search
```

or add

```json
"yii2tech/ar-search": "*"
```

to the require section of your composer.json.


Usage
-----

This extension provides unified search model for Yii ActiveRecord via special model class -
`\yii2tech\ar\search\ActiveSearchModel`.

This model is able to fetch its attributes, validation rules and filtering logic from the 'slave'
source ActiveRecord model specified via `\yii2tech\ar\search\ActiveSearchModel::$model`.
Thus you do not need to declare a separated model class for searching and define a filter logic.
For example:

```php
<?php

use yii2tech\ar\search\ActiveSearchModel;

$searchModel = new ActiveSearchModel([
    'model' => 'app\models\Item'
]);
$dataProvider = $searchModel->search(Yii::$app->request->queryParams);
```

`ActiveSearchModel` picks all 'safe' attributes of the 'slave' model and use them as own attributes.
Thus you can use any attribute, which is marked as 'safe' in related ActiveRecord model in the scope
of this class. For example:

```php
<?php

namespace app\models;

// ActiveRecord to be searched:
class Item extends \yii\db\ActiveRecord
{
    public function rules()
    {
        return [
            [['name', 'status', 'price'], 'required'],
            ['name', 'string'],
            ['status', 'integer'],
            ['price', 'number'],
        ];
    }
}

use yii2tech\ar\search\ActiveSearchModel;

// Create search model for declared ActiveRecord:
$searchModel = new ActiveSearchModel([
    'model' => 'app\models\Item'
]);

// safe attributes of `Item` are inherited:
$searchModel->name = 'Paul';
$searchModel->price = 10.5;
```

Inherited attributes may be used while composing web forms, which should collect filter data.
For example:

```php
<?php
use yii2tech\ar\search\ActiveSearchModel;
use yii\widgets\ActiveForm;

$searchModel = new ActiveSearchModel([
    'model' => 'app\models\Item'
]);
?>
<?php $form = ActiveForm::begin(); ?>

<?= $form->field($model, 'name')->textInput() ?>
<?= $form->field($model, 'price')->textInput() ?>
...

<?php ActiveForm::end(); ?>
```

Attribute labels and hints are also inherited from the 'slave' model.

The main method of `\yii2tech\ar\search\ActiveSearchModel` is `search()`. It loads filter attributes
from given data array, validates them an creates a `\yii\data\ActiveDataProvider` instance applying
own attributes as a query filter condition.

`ActiveSearchModel` uses a sophisticated logic for the query filtering, based on the attribute types,
specified by `\yii2tech\ar\search\ActiveSearchModel::$searchAttributeTypes`, which value is extracted
from `\yii2tech\ar\search\ActiveSearchModel::$model` by default and filter operators list, specified via
`\yii2tech\ar\search\ActiveSearchModel::$filterOperators`.
By default `\yii\db\QueryInterface::andFilterWhere()` will be used for the filter composition. For the
'string' attributes it will be used with 'like' operator. For 'integer' and 'float' ('double') method
`andFilterCompare()` will be used, if it is available.

**Heads up!** Do not abuse `ActiveSearchModel` usage. It has been designed to cover only the simplest
cases, when search logic is trivial. You should always create a separated search model in case, it
requires complex logic of composition of the search query.


## Adjusting Data Provider <span id="adjusting-data-provider"></span>

You may want to change some settings of the data provider, created by the `search()` method: change
pagination or sort settings and so on. You can do this via `\yii2tech\ar\search\ActiveSearchModel::$dataProvider`.
For example:

```php
<?php

use yii2tech\ar\search\ActiveSearchModel;

$searchModel = new ActiveSearchModel([
    'model' => 'app\models\Item',
    'dataProvider' => [
        'class' => 'yii\data\ActiveDataProvider',
        'pagination' => [
            'defaultPageSize' => 40
        ],
    ],
]);
$dataProvider = $searchModel->search(Yii::$app->request->queryParams);
echo $dataProvider->pagination->defaultPageSize; // outputs `40`
```


## Adjusting Search Query <span id="adjusting-search-query"></span>

You may use `\yii2tech\ar\search\ActiveSearchModel::EVENT_AFTER_CREATE_QUERY` event to adjust the search query instance
adding relation eager loading or permanent conditions. For example:

```php
<?php

use yii2tech\ar\search\ActiveSearchModel;
use yii2tech\ar\search\ActiveSearchEvent;

$searchModel = new ActiveSearchModel([
    'model' => 'app\models\Item',
]);
$searchModel->on(ActiveSearchModel::EVENT_AFTER_CREATE_QUERY, function(ActiveSearchEvent $event) {
    $event->query
        ->with(['category'])
        ->andWhere(['status' => 1]);
});
```

You may also specify query object directly via `\yii2tech\ar\search\ActiveSearchModel::$dataProvider`. For example:

```php
<?php

use yii2tech\ar\search\ActiveSearchModel;
use yii\data\ActiveDataProvider;
use app\models\Item;

$searchModel = new ActiveSearchModel([
    'model' => Item::className(),
    'dataProvider' => function () {
        $query = Item::find()
            ->with(['category'])
            ->andWhere(['status' => 1]);

        return ActiveDataProvider(['query' => $query]);
    },
]);
```


## Filter Operators <span id="filter-operators"></span>

You can control the operators to be used for the query filtering via `\yii2tech\ar\search\ActiveSearchModel::$filterOperators`.
It defines a mapping between the attribute type and the operator to be used with `\yii\db\QueryInterface::andFilterWhere()`.
Each value can be a scalar operator name or a PHP callback, accepting query instance, attribute name and value.
For example:

```php
<?php

use yii2tech\ar\search\ActiveSearchModel;

$searchModel = new ActiveSearchModel([
    'model' => 'app\models\Item',
    'filterOperators' => [
        ActiveSearchModel::TYPE_STRING => '=', // use strict comparison for the string attributes
        ActiveSearchModel::TYPE_INTEGER => function (\yii\db\ActiveQueryInterface $query, $attribute, $value) {
            if ($attribute === 'commentsCount') {
                $query->andHaving(['commentsCount' => $value]);
            } else {
                $query->andFilterWhere([$attribute => $value]);
            }
        },
    ],
]);
```

`ActiveSearchModel` allows filtering for the attributes using `andFilterCompare()` method of the query (for example:
`\yii\db\Query::andFilterCompare()`), which allows specifying filter value in format: `{operator}{value}` (for
example: `>10`, `<=100` and so on). The list of attribute names, for which usage of such comparison is allowed is controlled
by `\yii2tech\ar\search\ActiveSearchModel::$compareAllowedAttributes`. For example:

```php
<?php

use yii2tech\ar\search\ActiveSearchModel;

$searchModel = new ActiveSearchModel([
    'model' => 'app\models\Item',
    'compareAllowedAttributes' => [
        'price' // allow compare for 'price' only, excluding such fields like 'categoryId', 'status' and so on.
    ],
]);
```

You can set `compareAllowedAttributes` to `*`, which indicates any float or integer attribute will be allowed for comparison.

> Note: `\yii2tech\ar\search\ActiveSearchModel::$filterOperators` take precedence over `\yii2tech\ar\search\ActiveSearchModel::$compareAllowedAttributes`.


## Working Without 'Slave' Model <span id="working-without-model"></span>

Although in most cases setup of `\yii2tech\ar\search\ActiveSearchModel::$model` is a quickest way to configure `ActiveSearchModel`
instance, it is not mandatory. You can avoid setup of the 'slave' model and configure all search related properties
directly. For example:

```php
<?php

use yii2tech\ar\search\ActiveSearchModel;
use yii\data\ActiveDataProvider;
use app\models\Item;

$searchModel = new ActiveSearchModel([
    'searchAttributeTypes' => [
        'id' => ActiveSearchModel::TYPE_INTEGER,
        'name' => ActiveSearchModel::TYPE_STRING,
        'price' => ActiveSearchModel::TYPE_FLOAT,
    ],
    'rules' => [
        ['id', 'integer'],
        ['name', 'string'],
        ['price', 'number'],
    ],
    'compareAllowedAttributes' => [],
    'dataProvider' => function () {
        $query = Item::find()
            ->with(['category'])
            ->andWhere(['status' => 1]);

        return ActiveDataProvider(['query' => $query]);
    },
]);
```
