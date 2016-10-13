<?php

namespace yii2tech\tests\unit\ar\search\validators;

use yii\base\DynamicModel;
use yii2tech\ar\search\validators\NumberCompareValidator;
use yii2tech\tests\unit\ar\search\TestCase;

class NumberCompareValidatorTest extends TestCase
{
    /**
     * Data provider for [[testValidateAttribute()]]
     * @return array test data.
     */
    public function dataProviderValidateAttribute()
    {
        return [
            ['10.5', true],
            ['>10.5', true],
            ['abc', false],
            ['>abc', false],
            ['15', true],
            ['>15', true],
            ['>=10.5', true],
            ['>=abc', false],
            ['>==16', false],
        ];
    }

    /**
     * @dataProvider dataProviderValidateAttribute
     *
     * @param mixed $value
     * @param boolean $isValid
     */
    public function testValidateAttribute($value, $isValid)
    {
        $model = (new DynamicModel(['number' => $value]))
            ->addRule('number', NumberCompareValidator::className(), ['integerOnly' => false]);

        $this->assertEquals($isValid, $model->validate());
    }

    /**
     * Data provider for [[testValidateIntegerAttribute()]]
     * @return array test data.
     */
    public function dataProviderValidateIntegerAttribute()
    {
        return [
            ['10.5', false],
            ['abc', false],
            ['>abc', false],
            ['15', true],
            ['>15', true],
            ['>=10', true],
            ['>=abc', false],
            ['>==16', false],
        ];
    }

    /**
     * @dataProvider dataProviderValidateIntegerAttribute
     *
     * @param mixed $value
     * @param boolean $isValid
     */
    public function testValidateIntegerAttribute($value, $isValid)
    {
        $model = (new DynamicModel(['number' => $value]))
            ->addRule('number', NumberCompareValidator::className(), ['integerOnly' => true]);

        $this->assertEquals($isValid, $model->validate());
    }

    /**
     * Data provider for [[testValidateValue()]]
     * @return array test data.
     */
    public function dataProviderValidateValue()
    {
        return [
            ['10.5', true],
            ['>10.5', true],
            ['abc', false],
            ['>abc', false],
            ['15', true],
            ['>15', true],
            ['>=10.5', true],
            ['>=abc', false],
            ['>==16', false],
        ];
    }

    /**
     * @dataProvider dataProviderValidateValue
     *
     * @param mixed $value
     * @param boolean $isValid
     */
    public function testValidateValue($value, $isValid)
    {
        $validator = new NumberCompareValidator();

        $this->assertEquals($isValid, $validator->validate($value));
    }
}