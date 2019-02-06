<?php
/**
 * @link https://github.com/illuminatech
 * @copyright Copyright (c) 2015 Illuminatech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace Illuminatech\Balance\Events;

/**
 * CreatingTransaction is an event dispatched before new balance transaction creation.
 *
 * The listener of this event may use {@link $data} field to adjust actual transaction data to be saved.
 *
 * @see \Illuminatech\Balance\Balance
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class CreatingTransaction
{
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
     * @param  mixed  $accountId balance account ID.
     * @param  array  $data extra data, which should be associated with the transaction.
     */
    public function __construct($accountId, array $data = [])
    {
        $this->accountId = $accountId;
        $this->data = $data;
    }
}
