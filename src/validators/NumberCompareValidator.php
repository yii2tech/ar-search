<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\ar\search\validators;

use yii\validators\NumberValidator;

/**
 * NumberCompareValidator is an enhanced version of {@see NumberValidator}, which allows value to be prefixed by
 * compare validator like `=`, `<`, `>` and so on.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class NumberCompareValidator extends NumberValidator
{
    /**
     * {@inheritdoc}
     */
    public $integerPattern = '/^\s*(<>|>=|>|<=|<|=)?\s*[+-]?\d+\s*$/';
    /**
     * {@inheritdoc}
     */
    public $numberPattern = '/^\s*(<>|>=|>|<=|<|=)?\s*[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?\s*$/';
}