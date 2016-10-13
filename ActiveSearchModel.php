<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\ar\search;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecordInterface;
use yii\validators\BooleanValidator;
use yii\validators\FilterValidator;
use yii\validators\NumberValidator;
use yii\validators\RangeValidator;
use yii\validators\StringValidator;

/**
 * ActiveSearchModel
 *
 * @property ActiveRecordInterface|Model|array|string|callable $model model to be used for filter attributes validation.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class ActiveSearchModel extends Model
{
    /**
     * @var ActiveDataProvider|array|callable data provider to be used.
     */
    public $dataProvider;

    /**
     * @var ActiveRecordInterface|Model|array|string|callable model to be used for filter attributes validation.
     */
    private $_model;
    /**
     * @var array search attribute names
     */
    private $_searchAttributes;
    /**
     * @var array validation rules.
     */
    private $_rules;


    /**
     * @return ActiveRecordInterface|Model model instance.
     * @throws InvalidConfigException on invalid configuration.
     */
    public function getModel()
    {
        if (!is_object($this->_model) || $this->_model instanceof \Closure) {
            $model = Yii::createObject($this->_model);
            if (!$model instanceof Model) {
                throw new InvalidConfigException('`' . get_class($this) . '::model` should be an instance of `' . Model::className() . '` or its DI compatible configuration.');
            }
            $this->_model = $model;
        }
        return $this->_model;
    }

    /**
     * @param Model|ActiveRecordInterface|array|string|callable $model model instance or its DI compatible configuration.
     * @throws InvalidConfigException on invalid configuration.
     */
    public function setModel($model)
    {
        if (is_object($model)) {
            if (!$model instanceof ActiveRecordInterface && !$model instanceof \Closure) {
                throw new InvalidConfigException('`' . get_class($this) . '::model` should be an instance of `' . Model::className() . '` or its DI compatible configuration.');
            }
        }
        $this->_model = $model;
    }

    /**
     * @return array
     */
    public function getSearchAttributes()
    {
        if ($this->_searchAttributes === null) {
            ;
        }
        return $this->_searchAttributes;
    }

    /**
     * @param array $searchAttributes
     */
    public function setSearchAttributes($searchAttributes)
    {
        $this->_searchAttributes = $searchAttributes;
    }

    /**
     * @return array
     */
    public function getRules()
    {
        if ($this->_rules === null) {
            ;
        }
        return $this->_rules;
    }

    /**
     * @param array $rules
     */
    public function setRules($rules)
    {
        $this->_rules = $rules;
    }

    // Model specific :

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return $this->getSearchAttributes();
    }

    /**
     * @inheritdoc
     */
    public function formName()
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return $this->extractValidationRules($this->getModel());
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return $this->getModel()->attributeLabels();
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return $this->getModel()->attributeHints();
    }
    
    // Main :

    /**
     * Creates data provider instance with search query applied
     * @param array $params request search params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $dataProvider = $this->createDataProvider();

        $query = $this->getModel()->find();
        $dataProvider->query = $query;

        $this->load($params);

        if (!$this->validate()) {
            $query->where('0=1');
            return $dataProvider;
        }

        foreach ($this->safeAttributes() as $attribute) {
            $query->andFilterWhere([$attribute => $this->{$attribute}]);
        }

        return $dataProvider;
    }

    /**
     * @param Model $model
     * @return array
     */
    protected function extractValidationRules($model)
    {
        $rules = [
            [$model->activeAttributes(), 'safe']
        ];
        foreach ($model->getValidators() as $validator) {
            if ($validator instanceof FilterValidator || $validator instanceof NumberValidator || $validator instanceof StringValidator || $validator instanceof BooleanValidator || $validator instanceof RangeValidator) {
                $rules[] = $validator;
            }
        }

        return $rules;
    }

    /**
     * Creates new data provider from [[dataProvider]].
     * @return ActiveDataProvider data provider instance.
     * @throws InvalidConfigException on invalid configuration.
     */
    protected function createDataProvider()
    {
        $dataProvider = $this->dataProvider;
        if ($dataProvider === null) {
            $dataProvider = ActiveDataProvider::className();
        }
        return Yii::createObject($dataProvider);
    }
}