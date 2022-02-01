<?php
/**
 * @link https://github.com/illuminatech
 * @copyright Copyright (c) 2019 Illuminatech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace Illuminatech\Balance;

use Throwable;

/**
 * BalanceDbTransaction allows performing all balance operations as a single Database transaction.
 *
 * While wrapping all balance operations into transaction, this class ensures such transactions to be not nested.
 *
 * @see \Illuminatech\Balance\Balance
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
abstract class BalanceDbTransaction extends Balance
{
    /**
     * @var bool whether to wrap internal operations into a Database transaction.
     * @since 1.3.0
     */
    public $dbTransactionEnabled = true;

    /**
     * @var bool whether to wrap internal operations into a Database transaction, even if Database transaction already started at related Database connection.
     * If enabled - nested DB transaction (savepoint) will be created to wrap balance operations.
     * If disabled - no DB transaction will be started in case related DB connection already holds opened DB transaction.
     * @since 1.3.0
     */
    public $dbTransactionNestedEnabled = true;

    /**
     * @var int internal transaction stack level.
     */
    private $dbTransactionLevel = 0;

    /**
     * {@inheritdoc}
     */
    public function increase($account, $amount, $data = [])
    {
        $this->incrementDbTransactionLevel();
        try {
            $result = parent::increase($account, $amount, $data);
            $this->decrementDbTransactionLevel();

            return $result;
        } catch (Throwable $e) {
            $this->decrementDbTransactionLevel(true);
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function transfer($from, $to, $amount, $data = [])
    {
        $this->incrementDbTransactionLevel();
        try {
            $result = parent::transfer($from, $to, $amount, $data);
            $this->decrementDbTransactionLevel();

            return $result;
        } catch (Throwable $e) {
            $this->decrementDbTransactionLevel(true);
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function revert($transactionId, $data = [])
    {
        $this->incrementDbTransactionLevel();
        try {
            $result = parent::revert($transactionId, $data);

            $this->decrementDbTransactionLevel();

            return $result;
        } catch (Throwable $e) {
            $this->decrementDbTransactionLevel(true);
            throw $e;
        }
    }

    /**
     * Increments internal counter of transaction level.
     * Begins new transaction, if counter is zero.
     */
    protected function incrementDbTransactionLevel()
    {
        if (!$this->isDbTransactionAllowed()) {
            return;
        }

        if ($this->dbTransactionLevel === 0) {
            $this->beginDbTransaction();
        }

        $this->dbTransactionLevel++;
    }

    /**
     * Decrements internal counter of transaction level.
     * Ends current transaction with commit or rollback, if counter becomes zero.
     *
     * @param bool $rollback whether to perform rollback instead of commit.
     */
    protected function decrementDbTransactionLevel($rollback = false)
    {
        if (!$this->isDbTransactionAllowed()) {
            return;
        }

        $this->dbTransactionLevel--;

        if ($this->dbTransactionLevel === 0) {
            if ($rollback) {
                $this->rollBackDbTransaction();
            } else {
                $this->commitDbTransaction();
            }
        }
    }

    /**
     * Checks whether Database transaction usage is allowed or not.
     * @since 1.3.0
     *
     * @return bool whether DB transaction usage is allowed.
     */
    protected function isDbTransactionAllowed()
    {
        if (!$this->dbTransactionEnabled) {
            return false;
        }

        if (!$this->dbTransactionNestedEnabled && $this->getDbTransactionLevel() > 0) {
            return false;
        }

        return true;
    }

    /**
     * Begins new DB transaction.
     */
    abstract protected function beginDbTransaction();

    /**
     * Commits current DB transaction.
     */
    abstract protected function commitDbTransaction();

    /**
     * Rolls back current DB transaction.
     */
    abstract protected function rollBackDbTransaction();

    /**
     * Get the number of active DB transactions.
     * @since 1.3.0
     *
     * @return int count of active DB transactions.
     */
    abstract protected function getDbTransactionLevel();
}
