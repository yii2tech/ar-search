ActiveRecord Search Model Extension for Yii2
============================================

This extension provides unified search model for Yii ActiveRecord.

For license information check the [LICENSE](LICENSE.md)-file.

[![Latest Stable Version](https://poser.pugx.org/yii2tech/ar-search/v/stable.png)](https://packagist.org/packages/yii2tech/ar-search)
[![Total Downloads](https://poser.pugx.org/yii2tech/ar-search/downloads.png)](https://packagist.org/packages/yii2tech/ar-search)
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
[[\yii2tech\ar\search\ActiveSearchModel]].

This model is able to fetch its attributes, validation rules and filtering logic from the 'slave'
source ActiveRecord model specified via [[\yii2tech\ar\search\ActiveSearchModel::model]].
Thus you do not need to declare a separated model class for searching and define a filter logic.
For example:

```php
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

The main method of [[\yii2tech\ar\search\ActiveSearchModel]] is `search()`. It loads filter attributes
from given data array, validates them an creates a [[\yii\data\ActiveDataProvider]] instance applying
own attributes as a query filter condition.

`ActiveSearchModel` uses a sophisticated logic for the query filtering, based on the attribute types,
specified by [[\yii2tech\ar\search\ActiveSearchModel::$searchAttributeTypes]], which value is extracted
from [[\yii2tech\ar\search\ActiveSearchModel::$model]] by default and filter operators list, specified via
[[\yii2tech\ar\search\ActiveSearchModel::$filterOperators]].
By default [[\yii\db\QueryInterface::andFilterWhere()]] will be used for the filter composition. For the
'string' attributes it will be used with 'like' operator. For 'integer' and 'float' ('double') method
`andFilterCompare()` will be used, if it is available.

**Heads up!** Do not abuse `ActiveSearchModel` usage. It has been designed to cover only the simplest
cases, when search logic is trivial. You should always create a separated search model in case, it
requires complex logic of composition of the search query.


## Adjusting Data Provider <span id="adjusting-data-provider"></span>

You may want to change some settings of the data provider, created by the `search()` method: change
pagination or sort settings and so on. You can do this via [[\yii2tech\ar\search\ActiveSearchModel::$dataProvider]].
For example:

```php
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


## Filter Operators <span id="filter-operators"></span>


## Events <span id="events"></span>
