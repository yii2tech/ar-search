<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\ar\search\validators;

use yii\validators\NumberValidator;

/**
 * NumberCompareValidator is an enhanced version of [[NumberValidator]], which allows value to be prefixed by
 * compare validator like `=`, `<`, `>` and so on.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class NumberCompareValidator extends NumberValidator
{
    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $originValue = $model->$attribute;

        if (preg_match('/^(<>|>=|>|<=|<|=)/', $originValue, $matches)) {
            $operator = $matches[1];
            $model->$attribute = substr($originValue, strlen($operator));
            parent::validateAttribute($model, $attribute);
            $model->$attribute = $originValue;
        } else {
            parent::validateAttribute($model, $attribute);
        }
    }

    /**
     * @inheritdoc
     */
    protected function validateValue($value)
    {
        if (is_scalar($value)) {
            if (preg_match('/^(<>|>=|>|<=|<|=)/', "$value", $matches)) {
                $operator = $matches[1];
                $value = substr($value, strlen($operator));
            }
        }

        return parent::validateValue($value);
    }
}