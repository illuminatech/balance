<?php
/**
 * @link https://github.com/illuminatech
 * @copyright Copyright (c) 2019 Illuminatech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace Illuminatech\Balance;

/**
 * BalanceContract defines balance manager interface.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
interface BalanceContract
{
    /**
     * Increases account current balance ('debit' operation).
     *
     * @param  array|mixed  $account  account ID or filter condition.
     * @param  int|float  $amount amount.
     * @param  array  $data extra data, which should be associated with the transaction
     * @return mixed transaction ID.
     */
    public function increase($account, $amount, $data = []);

    /**
     * Decreases account current balance ('credit' operation).
     *
     * @param  array|mixed  $account account ID or filter condition.
     * @param  int|float  $amount amount.
     * @param  array  $data extra data, which should be associated with the transaction.
     * @return mixed transaction ID.
     */
    public function decrease($account, $amount, $data = []);

    /**
     * Transfers amount from one account to the other one.
     *
     * @param  array|mixed  $from account ID or filter condition.
     * @param  array|mixed  $to account ID or filter condition.
     * @param  int|float  $amount amount.
     * @param  array  $data extra data, which should be associated with the transaction.
     * @return array list of created transaction IDs.
     */
    public function transfer($from, $to, $amount, $data = []);

    /**
     * Reverts specified transaction.
     * This method does not deletes original transaction, but creates new one, which compensate it.
     * If transaction has been created via {@see transfer()} method, 2 transactions will be created affecting both
     * accounts used at {@see transfer()}.
     *
     * @param  mixed  $transactionId ID of the transaction to be reverted.
     * @param  array  $data extra transaction data
     * @return array|mixed transaction ID or list of transaction IDs.
     */
    public function revert($transactionId, $data = []);

    /**
     * Calculates current account balance summarizing all related transactions.
     *
     * @param  array|mixed  $account account ID or filter condition.
     * @return int|float current balance.
     */
    public function calculateBalance($account);
}
