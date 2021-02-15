<?php
/**
 * @link https://github.com/illuminatech
 * @copyright Copyright (c) 2019 Illuminatech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace Illuminatech\Balance;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use Illuminatech\Balance\Events\CreatingTransaction;
use Illuminatech\Balance\Events\TransactionCreated;
use InvalidArgumentException;

/**
 * Balance is a base class for the balance managers.
 *
 * @see BalanceContract
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
abstract class Balance implements BalanceContract
{
    /**
     * @var bool whether to automatically create requested account, if it does not yet exist.
     */
    public $autoCreateAccount = true;

    /**
     * @var string name of the transaction entity attribute, which should store amount.
     */
    public $amountAttribute = 'amount';

    /**
     * @var string name of the transaction entity attribute, which should be used to link transaction entity with
     * account entity (store associated account ID).
     */
    public $accountLinkAttribute = 'account_id';

    /**
     * @var string name of the transaction entity attribute, which should store additional affected account ID.
     * This attribute will be filled only at `transfer()` method execution and will store ID of the account transferred
     * from or to, depending on the context.
     * If not set, no information about the extra account context will be saved.
     *
     * Note: absence of this field will affect logic of some methods like {@see revert()}.
     */
    public $extraAccountLinkAttribute;

    /**
     * @var string|null name of the account entity attribute, which should store current balance value.
     */
    public $accountBalanceAttribute;

    /**
     * @var string|null name of the transaction entity attribute, which should store new balance value.
     * @since 1.2.0
     */
    public $newBalanceAttribute;

    /**
     * @var string name of the transaction entity attribute, which should store date.
     */
    public $dateAttribute = 'created_at';

    /**
     * @var mixed|callable value which should be used for new transaction date composition.
     * This can be plain value, object like {@see \Illuminate\Database\Query\Expression} or a PHP callback, which returns it.
     * If not set new {@see \Illuminate\Support\Carbon} instance will be used.
     */
    public $dateAttributeValue;

    /**
     * @var \Illuminate\Events\Dispatcher event dispatcher.
     */
    private $eventDispatcher;


    /**
     * @param  Dispatcher  $dispatcher
     * @return static self reference.
     */
    public function setEventDispatcher(Dispatcher $dispatcher): self
    {
        $this->eventDispatcher = $dispatcher;

        return $this;
    }

    /**
     * @return Dispatcher|null event dispatcher instance, `null` - if not available.
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function increase($account, $amount, $data = [])
    {
        $accountId = $this->fetchAccountId($account);

        if (!isset($data[$this->dateAttribute])) {
            $data[$this->dateAttribute] = $this->getDateAttributeValue();
        }
        $data[$this->amountAttribute] = $amount;
        $data[$this->accountLinkAttribute] = $accountId;

        if ($this->newBalanceAttribute !== null && !isset($data[$this->newBalanceAttribute])) {
            $lastTransaction = $this->findLastTransaction($accountId);
            $data[$this->newBalanceAttribute] = ($lastTransaction === null) ? $amount : ($lastTransaction[$this->newBalanceAttribute] + $amount);
        }

        $data = $this->beforeCreateTransaction($accountId, $data);

        if ($this->accountBalanceAttribute !== null) {
            $this->incrementAccountBalance($accountId, $data[$this->amountAttribute]);
        }
        $transactionId = $this->createTransaction($data);

        $this->afterCreateTransaction($transactionId, $accountId, $data);

        return $transactionId;
    }

    /**
     * {@inheritdoc}
     */
    public function decrease($account, $amount, $data = [])
    {
        return $this->increase($account, -$amount, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function transfer($from, $to, $amount, $data = [])
    {
        $fromId = $this->fetchAccountId($from);
        $toId = $this->fetchAccountId($to);

        $data[$this->dateAttribute] = $this->getDateAttributeValue();
        $fromData = $data;
        $toData = $data;

        if ($this->extraAccountLinkAttribute !== null) {
            $fromData[$this->extraAccountLinkAttribute] = $toId;
            $toData[$this->extraAccountLinkAttribute] = $fromId;
        }

        return [
            $this->decrease($fromId, $amount, $fromData),
            $this->increase($toId, $amount, $toData)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function revert($transactionId, $data = [])
    {
        $transaction = $this->findTransaction($transactionId);
        if (empty($transaction)) {
            throw new InvalidArgumentException("Unable to find transaction '{$transactionId}'");
        }

        $amount = $transaction[$this->amountAttribute];

        if ($this->extraAccountLinkAttribute !== null && isset($transaction[$this->extraAccountLinkAttribute])) {
            $fromId = $transaction[$this->accountLinkAttribute];
            $toId = $transaction[$this->extraAccountLinkAttribute];

            return $this->transfer($fromId, $toId, $amount, $data);
        }

        $accountId = $transaction[$this->accountLinkAttribute];

        return $this->decrease($accountId, $amount, $data);
    }

    /**
     * @param  mixed  $idOrFilter account ID or filter condition.
     * @return mixed account ID.
     */
    protected function fetchAccountId($idOrFilter)
    {
        if (is_array($idOrFilter)) {
            $accountId = $this->findAccountId($idOrFilter);
            if ($accountId === null) {
                if ($this->autoCreateAccount) {
                    $accountId = $this->createAccount($idOrFilter);
                } else {
                    throw new InvalidArgumentException('Unable to find account matching filter: ' . print_r($idOrFilter, true));
                }
            }
        } else {
            $accountId = $idOrFilter;
        }

        return $accountId;
    }

    /**
     * Finds account ID matching given filter attributes.
     *
     * @param  array  $attributes filter attributes.
     * @return mixed|null account ID, `null` - if not found.
     */
    abstract protected function findAccountId($attributes);

    /**
     * Finds transaction data by ID.
     *
     * @param  mixed  $id transaction ID.
     * @return array|null transaction data, `null` - if not found.
     */
    abstract protected function findTransaction($id);

    /**
     * Finds the last transaction data for the given account.
     * @since 1.2.0
     *
     * @param  mixed  $accountId balance account ID.
     * @return array|null transaction data, `null` - if not found.
     */
    abstract protected function findLastTransaction($accountId);

    /**
     * Creates new account with given attributes.
     *
     * @param  array  $attributes account attributes in format: attribute => value
     * @return mixed created account ID.
     */
    abstract protected function createAccount($attributes);

    /**
     * Writes transaction data into persistent storage.
     *
     * @param  array  $attributes attributes associated with transaction in format: attribute => value
     * @return mixed  new transaction ID.
     */
    abstract protected function createTransaction($attributes);

    /**
     * Increases current account balance value.
     *
     * @param  mixed  $accountId account ID.
     * @param  int|float  $amount amount to be added to the current balance.
     */
    abstract protected function incrementAccountBalance($accountId, $amount);

    /**
     * Returns actual now date value for the transaction.
     *
     * @return mixed date attribute value.
     */
    protected function getDateAttributeValue()
    {
        if ($this->dateAttributeValue === null) {
            return new Carbon;
        }

        if (is_callable($this->dateAttributeValue)) {
            return call_user_func($this->dateAttributeValue);
        }

        return $this->dateAttributeValue;
    }

    // Events :

    /**
     * This method is invoked before creating transaction.
     *
     * @param  mixed  $accountId account ID.
     * @param  array  $data transaction data.
     * @return array adjusted transaction data.
     */
    protected function beforeCreateTransaction($accountId, $data): array
    {
        if ($this->eventDispatcher === null) {
            return $data;
        }

        $event = new CreatingTransaction($accountId, $data);

        $this->eventDispatcher->dispatch($event);

        return $event->data;
    }

    /**
     * This method is invoked after transaction has been created.
     *
     * @param  mixed  $transactionId transaction ID.
     * @param  mixed  $accountId account ID.
     * @param  array  $data transaction data.
     */
    protected function afterCreateTransaction($transactionId, $accountId, $data)
    {
        if ($this->eventDispatcher === null) {
            return;
        }

        $event = new TransactionCreated($transactionId, $accountId, $data);

        $this->eventDispatcher->dispatch($event);
    }
}
