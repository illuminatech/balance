<?php
/**
 * @link https://github.com/illuminatech
 * @copyright Copyright (c) 2015 Illuminatech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace Illuminatech\Balance\Events;

/**
 * TransactionCreated is an event dispatched after new balance transaction has been created.
 *
 * @see \Illuminatech\Balance\Balance
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class TransactionCreated
{
    /**
     * @var mixed newly created transaction ID.
     */
    public $transactionId;

    /**
     * @var mixed balance account ID.
     */
    public $accountId;

    /**
     * @var array extra data, which should be associated with the transaction.
     */
    public $data = [];

    /**
     * Constructor.
     *
     * @param  mixed  newly created transaction ID.
     * @param  mixed  $accountId balance account ID.
     * @param  array  $data extra data, which should be associated with the transaction.
     */
    public function __construct($transactionId, $accountId, array $data = [])
    {
        $this->transactionId = $transactionId;
        $this->accountId = $accountId;
        $this->data = $data;
    }
}
