<?php

namespace yii2tech\tests\unit\ar\search;

use yii\data\ActiveDataProvider;
use yii2tech\ar\search\ActiveSearchModel;
use yii2tech\tests\unit\ar\search\data\Item;

class ActiveSearchModelTest extends TestCase
{
    public function testSetup()
    {
        $searchModel = new ActiveSearchModel();

        $searchAttributes = [
            'name' => 'string'
        ];
        $searchModel->setSearchAttributes($searchAttributes);
        $this->assertEquals($searchAttributes, $searchModel->getSearchAttributes());

        $rules = [
            ['name', 'string'],
        ];
        $searchModel->setRules($rules);
        $this->assertEquals($rules, $searchModel->getRules());
    }

    public function testSetupModel()
    {
        $searchModel = new ActiveSearchModel();

        $searchModel->setModel(Item::className());
        $model = $searchModel->getModel();
        $this->assertTrue($model instanceof Item);

        $searchModel->setModel([
            'class' => Item::className(),
            'scenario' => 'search',
        ]);
        $model = $searchModel->getModel();
        $this->assertTrue($model instanceof Item);
        $this->assertEquals('search', $model->scenario);

        $model = new Item();
        $searchModel->setModel($model);
        $this->assertSame($model, $searchModel->getModel());
    }

    /**
     * @depends testSetup
     * @depends testSetupModel
     */
    public function testPopulateFromModel()
    {
        $searchModel = new ActiveSearchModel();
        $searchModel->setModel(Item::className());

        $expectedSearchAttributes = [
            'id' => ActiveSearchModel::TYPE_INTEGER,
            'name' => ActiveSearchModel::TYPE_STRING,
            'status' => ActiveSearchModel::TYPE_INTEGER,
            'price' => ActiveSearchModel::TYPE_FLOAT,
        ];
        $this->assertEquals($expectedSearchAttributes, $searchModel->getSearchAttributes());
    }

    /**
     * @depends testSetup
     */
    public function testAttributeAccess()
    {
        $searchModel = new ActiveSearchModel();
        $searchModel->setSearchAttributes([
            'id' => ActiveSearchModel::TYPE_INTEGER,
            'name' => ActiveSearchModel::TYPE_STRING,
        ]);

        $searchModel->name = 'some';
        $this->assertEquals('some', $searchModel->name);

        $this->assertFalse(isset($searchModel->id));
        $searchModel->id = 2;
        $this->assertTrue(isset($searchModel->id));

        unset($searchModel->id);
        $this->assertFalse(isset($searchModel->id));
    }

    public function testCreateDataProvider()
    {
        $searchModel = new ActiveSearchModel();
        $searchModel->setModel(Item::className());

        $dataProvider = $this->invoke($searchModel, 'createDataProvider');
        $this->assertTrue($dataProvider instanceof ActiveDataProvider);

        $searchModel->dataProvider = [
            'class' => ActiveDataProvider::className(),
            'pagination' => false,
        ];
        $dataProvider = $this->invoke($searchModel, 'createDataProvider');
        $this->assertTrue($dataProvider instanceof ActiveDataProvider);
        $this->assertEquals(false, $dataProvider->getPagination());

        $searchModel->dataProvider = function() {
            return new ActiveDataProvider(['sort' => false]);
        };
        $dataProvider = $this->invoke($searchModel, 'createDataProvider');
        $this->assertTrue($dataProvider instanceof ActiveDataProvider);
        $this->assertEquals(false, $dataProvider->getSort());
    }

    /**
     * @depends testAttributeAccess
     * @depends testCreateDataProvider
     */
    public function testSearch()
    {
        $searchModel = new ActiveSearchModel();
        $searchModel->setModel(Item::className());

        $dataProvider = $searchModel->search(['name' => '', 'status' => '', 'price' => '']);
        $this->assertEquals(10, $dataProvider->getTotalCount());

        $dataProvider = $searchModel->search(['name' => '', 'status' => 2, 'price' => '']);
        $this->assertEquals(2, $dataProvider->getTotalCount());

        $dataProvider = $searchModel->search(['name' => '2', 'status' => '', 'price' => '']);
        $this->assertEquals(1, $dataProvider->getTotalCount());

        $dataProvider = $searchModel->search(['name' => 'item', 'status' => '', 'price' => '']);
        $this->assertEquals(10, $dataProvider->getTotalCount());
    }
}