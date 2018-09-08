<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\mutex;

use yii\base\InvalidConfigException;

/**
 * MysqlMutex implements mutex "lock" mechanism via MySQL locks.
 *
 * Application configuration example:
 *
 * ```
 * [
 *     'components' => [
 *         'db' => [
 *             '__class' => \yii\db\Connection::class,
 *             'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
 *         ]
 *         'mutex' => [
 *             '__class' => \yii\mutex\MysqlMutex::class,
 *         ],
 *     ],
 * ]
 * ```
 *
 * @see Mutex
 *
 * @author resurtm <resurtm@gmail.com>
 * @since 2.0
 */
class MysqlMutex extends DbMutex
{
    /**
     * Initializes MySQL specific mutex component implementation.
     * @throws InvalidConfigException if [[db]] is not MySQL connection.
     */
    public function init()
    {
        parent::init();
        if ($this->db->driverName !== 'mysql') {
            throw new InvalidConfigException('In order to use MysqlMutex connection must be configured to use MySQL database.');
        }
    }

    /**
     * Acquires lock by given name.
     * @param string $name of the lock to be acquired.
     * @param int $timeout time (in seconds) to wait for lock to become released.
     * @return bool acquiring result.
     * @see http://dev.mysql.com/doc/refman/5.0/en/miscellaneous-functions.html#function_get-lock
     */
    protected function acquireLock($name, $timeout = 0)
    {
        return $this->db->useMaster(function ($db) use ($name, $timeout) {
            /* @var $db \yii\db\Connection */
            return (bool) $db->createCommand(
                'SELECT GET_LOCK(:name, :timeout)',
                [':name' => $name, ':timeout' => $timeout]
            )->queryScalar();
        });
    }

    /**
     * Releases lock by given name.
     * @param string $name of the lock to be released.
     * @return bool release result.
     * @see http://dev.mysql.com/doc/refman/5.0/en/miscellaneous-functions.html#function_release-lock
     */
    protected function releaseLock($name)
    {
        return $this->db->useMaster(function ($db) use ($name) {
            /* @var $db \yii\db\Connection */
            return (bool) $db->createCommand(
                'SELECT RELEASE_LOCK(:name)',
                [':name' => $name]
            )->queryScalar();
        });
    }
}
