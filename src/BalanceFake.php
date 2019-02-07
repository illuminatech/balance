<?php
/**
 * @link https://github.com/illuminatech
 * @copyright Copyright (c) 2015 Illuminatech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace Illuminatech\Balance;

/**
 * BalanceFake is a fake balance manager, which stores transactions and accounts in its internal fields.
 *
 * This class may be useful for unit test writing.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class BalanceFake extends Balance
{
    /**
     * @var array[] list of accounts.
     */
    public $accounts = [];

    /**
     * @var array account current balances in format: `[accountId => balanceValue]`.
     */
    public $accountBalances = [];

    /**
     * @var array[] list of performed transactions.
     */
    public $transactions = [];

    /**
     * @return array last transaction data.
     */
    public function getLastTransaction()
    {
        return end($this->transactions);
    }

    /**
     * @return array[] last 2 transactions data.
     */
    public function getLastTransactionPair()
    {
        $last = end($this->transactions);
        $preLast = prev($this->transactions);

        return [$preLast, $last];
    }

    /**
     * {@inheritdoc}
     */
    public function calculateBalance($account)
    {
        $accountId = $this->findAccountId($account);

        return $this->accountBalances[$accountId];
    }

    /**
     * {@inheritdoc}
     */
    protected function createTransaction($attributes)
    {
        $transactionId = count($this->transactions);
        $attributes['id'] = $transactionId;
        $this->transactions[] = $attributes;

        return $transactionId;
    }

    /**
     * {@inheritdoc}
     */
    protected function findAccountId($attributes)
    {
        $id = serialize($attributes);
        if (isset($this->accounts[$id])) {
            return $id;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function createAccount($attributes)
    {
        $id = serialize($attributes);
        $this->accounts[$id] = $id;

        return $id;
    }

    /**
     * {@inheritdoc}
     */
    protected function incrementAccountBalance($accountId, $amount)
    {
        if (!isset($this->accountBalances[$accountId])) {
            $this->accountBalances[$accountId] = 0;
        }
        $this->accountBalances[$accountId] += $amount;
    }

    /**
     * {@inheritdoc}
     */
    protected function findTransaction($id)
    {
        if (isset($this->transactions[$id])) {
            return $this->transactions[$id];
        }

        return null;
    }
}
