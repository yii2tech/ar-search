<?php

namespace yii2tech\tests\unit\ar\search;

use yii\helpers\ArrayHelper;
use Yii;

/**
 * Base class for the test cases.
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->mockApplication();

        $this->setupTestDbData();
    }

    protected function tearDown()
    {
        $this->destroyApplication();
    }

    /**
     * Populates Yii::$app with a new application
     * The application will be destroyed on tearDown() automatically.
     * @param array $config The application configuration, if needed
     * @param string $appClass name of the application class to create
     */
    protected function mockApplication($config = [], $appClass = '\yii\console\Application')
    {
        new $appClass(ArrayHelper::merge([
            'id' => 'testapp',
            'basePath' => __DIR__,
            'vendorPath' => $this->getVendorPath(),
            'components' => [
                'db' => [
                    'class' => 'yii\db\Connection',
                    'dsn' => 'sqlite::memory:',
                ],
            ],
        ], $config));
    }

    /**
     * @return string vendor path
     */
    protected function getVendorPath()
    {
        return dirname(__DIR__) . '/vendor';
    }

    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication()
    {
        Yii::$app = null;
    }

    /**
     * Setup tables for test ActiveRecord
     */
    protected function setupTestDbData()
    {
        $db = Yii::$app->getDb();

        // Structure :

        $table = 'Item';
        $columns = [
            'id' => 'pk',
            'name' => 'string',
            'status' => 'integer',
            'price' => 'float',
        ];
        $db->createCommand()->createTable($table, $columns)->execute();

        // Data :

        $db->createCommand()->batchInsert('Item', ['name', 'status', 'price'], [
            ['item1', 1, '10.5'],
            ['item2', 2, '20.5'],
            ['item3', 3, '20'],
            ['item4', 4, '50.2'],
            ['item5', 5, '40.7'],
            ['item6', 1, '10.5'],
            ['item7', 2, '20.5'],
            ['item8', 3, '20'],
            ['item9', 4, '50.2'],
            ['item0', 5, '40.7'],
        ])->execute();
    }

    /**
     * Invokes object method, even if it is private or protected.
     * @param object $object object.
     * @param string $method method name.
     * @param array $args method arguments
     * @return mixed method result
     */
    protected function invoke($object, $method, array $args = [])
    {
        $classReflection = new \ReflectionClass(get_class($object));
        $methodReflection = $classReflection->getMethod($method);
        $methodReflection->setAccessible(true);
        $result = $methodReflection->invokeArgs($object, $args);
        $methodReflection->setAccessible(false);
        return $result;
    }
}
