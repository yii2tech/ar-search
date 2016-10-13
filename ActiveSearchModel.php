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
use yii\helpers\StringHelper;
use yii\validators\BooleanValidator;
use yii\validators\EachValidator;
use yii\validators\FilterValidator;
use yii\validators\NumberValidator;
use yii\validators\RangeValidator;
use yii\validators\StringValidator;
use yii2tech\ar\search\validators\NumberCompareValidator;

/**
 * ActiveSearchModel
 *
 * @property ActiveRecordInterface|Model|array|string|callable $model model to be used for filter attributes validation.
 * @property string $formName form name to be used at [[formName()]] method.
 * @property array $searchAttributeTypes array search attribute types in format: `[attribute => type]`.
 * @property array $rules validation rules in format of [[rules()]] return value.
 * @property array $filterOperators array filter operators in format: `[type => operator]`.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class ActiveSearchModel extends Model
{
    /**
     * @event ActiveSearchEvent an event that is triggered after search query is created.
     */
    const EVENT_AFTER_CREATE_QUERY = 'afterCreateQuery';

    const TYPE_INTEGER = 'integer';
    const TYPE_FLOAT = 'float';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_STRING = 'string';
    const TYPE_ARRAY = 'array';

    /**
     * @var ActiveDataProvider|array|callable data provider to be used.
     * This could be a data provider instance or its DI compatible configuration.
     */
    public $dataProvider;

    /**
     * @var ActiveRecordInterface|Model|array|string|callable model to be used for filter attributes validation.
     */
    private $_model;
    /**
     * @var array list of attributes in format: `[name => value]`
     */
    private $_attributes;
    /**
     * @var array search attribute types in format: `[attribute => type]`.
     * Result of the [[attributes()]] method of this model will be composed from this field.
     */
    private $_searchAttributeTypes;
    /**
     * @var array validation rules.
     */
    private $_rules;
    /**
     * @var string form name to be used at [[formName()]] method.
     */
    private $_formName;
    /**
     * @var array filter operators in format: `[type => operator]`.
     * For example:
     *
     * ```php
     * [
     *     ActiveSearchModel::TYPE_STRING => 'like',
     *     ActiveSearchModel::TYPE_ARRAY => 'in',
     * ]
     * ```
     *
     * Defined operator will be used while composing filter condition for the attribute with corresponding type.
     *
     * Particular operator can be a PHP callback of following format:
     *
     * ```php
     * function (\yii\db\ActiveQueryInterface $query, string $attribute, mixed $value) {}
     * ```
     */
    private $_filterOperators;


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
     * @return boolean whether [[model]] is populated.
     */
    public function hasModel()
    {
        return $this->_model !== null;
    }

    /**
     * @return array search attributes in format: `[attribute => type]`
     */
    public function getSearchAttributeTypes()
    {
        if ($this->_searchAttributeTypes === null) {
            $this->populateFromModel($this->getModel());
        }
        return $this->_searchAttributeTypes;
    }

    /**
     * @param array $searchAttributeTypes search attributes in format: `[attribute => type]`
     */
    public function setSearchAttributeTypes($searchAttributeTypes)
    {
        $this->_searchAttributeTypes = $searchAttributeTypes;
    }

    /**
     * @return array validation rules.
     */
    public function getRules()
    {
        if ($this->_rules === null) {
            $this->populateFromModel($this->getModel());
        }
        return $this->_rules;
    }

    /**
     * @param array $rules validation rules in format of [[rules()]] return value.
     */
    public function setRules($rules)
    {
        $this->_rules = $rules;
    }

    /**
     * @return string form name to be used at [[formName()]] method.
     */
    public function getFormName()
    {
        if ($this->_formName === null) {
            if ($this->hasModel()) {
                $this->_formName = StringHelper::basename(get_class($this->getModel())) . 'Search';
            } else {
                $this->_formName = 'Search';
            }
        }
        return $this->_formName;
    }

    /**
     * @param string $formName form name to be used at [[formName()]] method.
     */
    public function setFormName($formName)
    {
        $this->_formName = $formName;
    }

    /**
     * @return array filter operators in format: `[type => operator]`
     */
    public function getFilterOperators()
    {
        if ($this->_filterOperators === null) {
            $this->_filterOperators = $this->defaultFilterOperators();
        }
        return $this->_filterOperators;
    }

    /**
     * @param array $filterOperators filter operators in format: `[type => operator]`
     */
    public function setFilterOperators($filterOperators)
    {
        $this->_filterOperators = $filterOperators;
    }

    /**
     * Determines default value [[filterOperators]].
     * @return array filter operators in format: `[type => operator]`
     */
    protected function defaultFilterOperators()
    {
        $stringOperator = 'like';

        if ($this->hasModel()) {
            $model = $this->getModel();
            if ($model instanceof \yii\db\ActiveRecord) {
                if ($model->getDb()->driverName === 'pgsql') {
                    $stringOperator = 'ilike';
                }
            }
        }

        return [
            self::TYPE_ARRAY => 'in',
            self::TYPE_STRING => $stringOperator,
        ];
    }

    // Model specific :

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return array_keys($this->getSearchAttributeTypes());
    }

    /**
     * @inheritdoc
     */
    public function formName()
    {
        return $this->getFormName();
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return $this->getRules();
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        if ($this->hasModel()) {
            return $this->getModel()->attributeLabels();
        }
        return [];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        if ($this->hasModel()) {
            return $this->getModel()->attributeHints();
        }
        return [];
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

        $this->afterCreateQuery($query);

        $filterOperators = $this->getFilterOperators();
        foreach ($this->getSearchAttributeTypes() as $attribute => $type) {
            if (isset($filterOperators[$type])) {
                if (is_scalar($filterOperators[$type])) {
                    $query->andFilterWhere([$filterOperators[$type], $attribute, $this->{$attribute}]);
                } else {
                    call_user_func($filterOperators[$type], $query, $attribute, $this->{$attribute});
                }
            } else {
                if (in_array($type, [self::TYPE_INTEGER, self::TYPE_FLOAT]) && $query->hasMethod('andFilterCompare')) {
                    $query->andFilterCompare($attribute, $this->{$attribute});
                } else {
                    $query->andFilterWhere([$attribute => $this->{$attribute}]);
                }
            }
        }

        return $dataProvider;
    }

    /**
     * Populates internal fields from the given model.
     * @param Model $model source model instance.
     */
    protected function populateFromModel($model)
    {
        $metaData = $this->extractModelMetaData($model);

        if ($this->_searchAttributeTypes === null) {
            $this->_searchAttributeTypes = $metaData['attributes'];
        }

        if ($this->_rules === null) {
            $this->_rules = $metaData['rules'];
        }
    }

    /**
     * Extract meta data from give model.
     * Following keys will be return in result array:
     *
     * - attributes: array, list of model attributes with types in format: `[attribute => type]`
     * - rules: array, list of search validation rules
     *
     * @param Model $model source model instance.
     * @return array meta data.
     */
    protected function extractModelMetaData($model)
    {
        $attributeTypes = [];
        foreach ($model->activeAttributes() as $attribute) {
            $attributeTypes[$attribute] = 'string';
        }

        $rules = [
            [array_keys($attributeTypes), 'safe']
        ];
        foreach ($model->getValidators() as $validator) {
            $type = null;
            if ($validator instanceof FilterValidator || $validator instanceof RangeValidator) {
                $rules[] = $validator;
            } elseif ($validator instanceof NumberValidator) {
                $rules[] = [$validator->attributes, NumberCompareValidator::className(), 'integerOnly' => $validator->integerOnly];
                $type = $validator->integerOnly ? self::TYPE_INTEGER : self::TYPE_FLOAT;
            } elseif ($validator instanceof BooleanValidator) {
                $rules[] = $validator;
                $type = self::TYPE_BOOLEAN;
            } elseif ($validator instanceof StringValidator) {
                $rules[] = $validator;
                $type = self::TYPE_STRING;
            } elseif ($validator instanceof EachValidator) {
                $type = self::TYPE_ARRAY;
            }

            if ($type !== null) {
                foreach ((array)$validator->attributes as $attribute) {
                    $attributeTypes[$attribute] = $type;
                }
            }
        }

        if ($model instanceof ActiveRecordInterface) {
            foreach ($model->primaryKey() as $key => $attribute) {
                if (!isset($attributeTypes[$attribute])) {
                    $type = self::TYPE_STRING;
                    if ($model instanceof \yii\db\ActiveRecord) {
                        switch ($model->getTableSchema()->getColumn($attribute)->phpType) {
                            case 'integer':
                                $type = self::TYPE_INTEGER;
                                break;
                            case 'boolean':
                                $type = self::TYPE_BOOLEAN;
                                break;
                            case 'double':
                            case 'float':
                                $type = self::TYPE_FLOAT;
                                break;
                            case 'string':
                            case 'resource':
                                $type = self::TYPE_STRING;
                                break;
                        }
                    }
                    $attributeTypes[$attribute] = $type;
                }
            }
        }

        return [
            'attributes' => $attributeTypes,
            'rules' => $rules,
        ];
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

    // Property access :

    /**
     * @inheritdoc
     */
    public function canGetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        if (isset($this->getSearchAttributeTypes()[$name])) {
            return true;
        }
        return parent::canGetProperty($name, $checkVars, $checkBehaviors);
    }

    /**
     * @inheritdoc
     */
    public function canSetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        if (isset($this->getSearchAttributeTypes()[$name])) {
            return true;
        }
        return parent::canSetProperty($name, $checkVars, $checkBehaviors);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        if (isset($this->_attributes[$name])) {
            return $this->_attributes[$name];
        } elseif (isset($this->getSearchAttributeTypes()[$name])) {
            return null;
        } else {
            return parent::__get($name);
        }
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if (isset($this->getSearchAttributeTypes()[$name])) {
            $this->_attributes[$name] = $value;
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * @inheritdoc
     */
    public function __isset($name)
    {
        if (isset($this->_attributes[$name])) {
            return true;
        } else {
            return parent::__isset($name);
        }
    }

    /**
     * @inheritdoc
     */
    public function __unset($name)
    {
        if (isset($this->getSearchAttributeTypes()[$name])) {
            unset($this->_attributes[$name]);
        } else {
            parent::__unset($name);
        }
    }

    // Events :

    /**
     * This method is invoked before search query is created.
     * The default implementation raises the [[EVENT_AFTER_CREATE_QUERY]] event.
     * @param \yii\db\ActiveQueryInterface $query active query instance.
     */
    public function afterCreateQuery($query)
    {
        $event = new ActiveSearchEvent();
        $event->query = $query;

        $this->trigger(self::EVENT_AFTER_CREATE_QUERY, $event);
    }
}