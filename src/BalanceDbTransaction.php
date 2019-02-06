<?php
/**
 * @link https://github.com/illuminatech
 * @copyright Copyright (c) 2015 Illuminatech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace Illuminatech\Balance;

use Throwable;

/**
 * BalanceDbTransaction allows performing all balance operations as a single Database transaction.
 *
 * @see Balance
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
abstract class BalanceDbTransaction extends Balance
{
    /**
     * @var int internal transaction stack level.
     */
    private $dbTransactionLevel = 0;

    /**
     * {@inheritdoc}
     */
    public function increase($account, $amount, $data = [])
    {
        $this->beginDbTransaction();
        try {
            $result = parent::increase($account, $amount, $data);
            $this->commitDbTransaction();
            return $result;
        } catch (Throwable $e) {
            $this->rollBackDbTransaction();
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function transfer($from, $to, $amount, $data = [])
    {
        $this->beginDbTransaction();
        try {
            $result = parent::transfer($from, $to, $amount, $data);
            $this->commitDbTransaction();
            return $result;
        } catch (Throwable $e) {
            $this->rollBackDbTransaction();
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
            $this->decrementDbTransactionLevel(false);
            throw $e;
        }
    }

    /**
     * Increments internal counter of transaction level.
     * Begins new transaction, if counter is zero.
     */
    protected function incrementDbTransactionLevel()
    {
        if ($this->dbTransactionLevel === 0) {
            $this->beginDbTransaction();
        }

        $this->dbTransactionLevel++;
    }

    /**
     * Decrements internal counter of transaction level.
     * Ends current transaction with commit or rollback, if counter becomes zero.
     *
     * @param  bool  $rollback whether to perform rollback instead of commit.
     */
    protected function decrementDbTransactionLevel($rollback = false)
    {
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
}
