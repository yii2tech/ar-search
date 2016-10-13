<?php

namespace yii2tech\tests\unit\ar\search\data;

use yii\db\ActiveRecord;

/**
 * @property integer $id
 * @property string $name
 * @property integer $status
 * @property float $price
 */
class Item extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'Item';
    }

    /**
     * @inheritdoc
     */
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