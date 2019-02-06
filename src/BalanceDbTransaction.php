<?php
/**
 * @link https://github.com/illuminatech
 * @copyright Copyright (c) 2015 Illuminatech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace Illuminatech\Balance;

use Throwable;

/**
 * BalanceDbTransaction allows performing balance operations as a single Database transaction.
 *
 * @see Manager
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
abstract class BalanceDbTransaction extends Balance
{
    /**
     * @var array internal transaction instances stack.
     */
    private $dbTransactions = [];

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
        $this->beginDbTransaction();
        try {
            $result = parent::revert($transactionId, $data);
            $this->commitDbTransaction();
            return $result;
        } catch (Throwable $e) {
            $this->rollBackDbTransaction();
            throw $e;
        }
    }

    /**
     * Begins transaction.
     */
    protected function beginDbTransaction()
    {
        $this->dbTransactions[] = $this->createDbTransaction();
    }

    /**
     * Commits current transaction.
     */
    protected function commitDbTransaction()
    {
        $transaction = array_pop($this->dbTransactions);
        if ($transaction !== null) {
            $transaction->commit();
        }
    }

    /**
     * Rolls back current transaction.
     */
    protected function rollBackDbTransaction()
    {
        $transaction = array_pop($this->dbTransactions);
        if ($transaction !== null) {
            $transaction->rollBack();
        }
    }

    /**
     * Creates transaction instance, actually beginning transaction.
     * If transactions are not supported, `null` will be returned.
     *
     * @return object|\yii\db\Transaction|null transaction instance, `null` if transaction is not supported.
     */
    abstract protected function createDbTransaction();
}
