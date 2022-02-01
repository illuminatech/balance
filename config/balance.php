<?php
/**
 * Configuration for balance manager.
 * This file returns an 'array factory' compatible definition for {@link \Illuminatech\Balance\BalanceDb} object.
 *
 * @see https://github.com/illuminatech/balance
 * @see \Illuminatech\Balance\BalanceDb
 * @see \Illuminatech\ArrayFactory\FactoryContract
 */

return [
    'accountTable' => 'balance_accounts',
    'transactionTable' => 'balance_transactions',
    'accountBalanceAttribute' => 'balance',
    'extraAccountLinkAttribute' => 'extra_account_id',
    'dataAttribute' => 'data',
    //'newBalanceAttribute' => 'new_balance',
    'dbTransactionEnabled' => true,
    'dbTransactionNestedEnabled' => false,
];
