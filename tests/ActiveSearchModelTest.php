<?php

namespace yii2tech\tests\unit\ar\search;

use yii2tech\ar\search\ActiveSearchModel;

class ActiveSearchModelTest extends TestCase
{
    public function testSetup()
    {
        $model = new ActiveSearchModel();

        $searchAttributes = [
            'name' => 'string'
        ];
        $model->setSearchAttributes($searchAttributes);
        $this->assertEquals($searchAttributes, $model->getSearchAttributes());

        $rules = [
            ['name', 'string'],
        ];
        $model->setRules($rules);
        $this->assertEquals($rules, $model->getRules());
    }
}