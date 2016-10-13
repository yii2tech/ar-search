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

        $searchAttributeTypes = [
            'name' => ActiveSearchModel::TYPE_STRING
        ];
        $searchModel->setSearchAttributeTypes($searchAttributeTypes);
        $this->assertEquals($searchAttributeTypes, $searchModel->getSearchAttributeTypes());

        $rules = [
            ['name', 'string'],
        ];
        $searchModel->setRules($rules);
        $this->assertEquals($rules, $searchModel->getRules());

        $formName = 'Some';
        $searchModel->setFormName($formName);
        $this->assertEquals($formName, $searchModel->getFormName());
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
     * @depends testSetupModel
     */
    public function testHasModel()
    {
        $searchModel = new ActiveSearchModel();

        $this->assertFalse($searchModel->hasModel());

        $searchModel->setModel(Item::className());
        $this->assertTrue($searchModel->hasModel());
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
        $this->assertEquals($expectedSearchAttributes, $searchModel->getSearchAttributeTypes());
    }

    /**
     * @depends testSetup
     * @depends testSetupModel
     */
    public function testFormName()
    {
        $searchModel = new ActiveSearchModel();
        $this->assertEquals('Search', $searchModel->formName());

        $searchModel = new ActiveSearchModel();
        $searchModel->setModel(Item::className());
        $this->assertEquals('ItemSearch', $searchModel->formName());

        $searchModel = new ActiveSearchModel();
        $searchModel->setFormName('Some');
        $this->assertEquals('Some', $searchModel->formName());
    }

    /**
     * @depends testSetup
     */
    public function testAttributeAccess()
    {
        $searchModel = new ActiveSearchModel();
        $searchModel->setSearchAttributeTypes([
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
        $searchModel->setFormName('');

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