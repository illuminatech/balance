<?php
/**
 * @link https://github.com/illuminatech
 * @copyright Copyright (c) 2015 Illuminatech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace Illuminatech\Balance;

use Illuminate\Database\Connection;

/**
 * BalanceDb is a balance manager, which uses relational database as data storage.
 *
 *
 *
 * This manager will attempt to save value from transaction data in the table column, which name matches data key.
 * If such column does not exist data will be saved in [[dataAttribute]] column in serialized state.
 *
 * > Note: watch for the keys you use in transaction data: make sure they do not conflict with columns, which are
 *   reserved for other purposes, like primary keys.
 *
 * @see Manager
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class BalanceDb extends BalanceDbTransaction
{
    use ManagerDataSerializeTrait;

    /**
     * @var Connection the DB connection instance.
     */
    public $db = 'db';
    /**
     * @var string name of the database table, which should store account records.
     */
    public $accountTable = 'balance_account';
    /**
     * @var string name of the database table, which should store transaction records.
     */
    public $transactionTable = 'balance_transaction';

    /**
     * @var string name of the account ID attribute at [[accountTable]]
     */
    private $_accountIdAttribute;
    /**
     * @var string name of the transaction ID attribute at [[transactionTable]]
     */
    private $_transactionIdAttribute;


    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * @return string
     */
    public function getAccountIdAttribute()
    {
        if ($this->_accountIdAttribute === null) {
            $this->_accountIdAttribute = 'id';
        }

        return $this->_accountIdAttribute;
    }

    /**
     * @param string $accountIdAttribute
     */
    public function setAccountIdAttribute($accountIdAttribute)
    {
        $this->_accountIdAttribute = $accountIdAttribute;
    }

    /**
     * @return string
     */
    public function getTransactionIdAttribute()
    {
        if ($this->_transactionIdAttribute === null) {
            $this->_transactionIdAttribute = 'id';
        }
        return $this->_transactionIdAttribute;
    }

    /**
     * @param string $transactionIdAttribute
     */
    public function setTransactionIdAttribute($transactionIdAttribute)
    {
        $this->_transactionIdAttribute = $transactionIdAttribute;
    }

    /**
     * {@inheritdoc}
     */
    protected function findAccountId($attributes)
    {
        $id = $this->db->query()
            ->select([$this->getAccountIdAttribute()])
            ->from($this->accountTable)
            ->where($attributes)
            ->value($this->getAccountIdAttribute());

        if ($id === false) {
            return null;
        }

        return $id;
    }

    /**
     * {@inheritdoc}
     */
    protected function findTransaction($id)
    {
        $idAttribute = $this->getTransactionIdAttribute();

        $row = $this->db->query()
            ->from($this->transactionTable)
            ->where([$idAttribute => $id])
            ->first();

        if ($row === false) {
            return null;
        }

        return $this->unserializeAttributes($row);
    }

    /**
     * {@inheritdoc}
     */
    protected function createAccount($attributes)
    {
        return $this->db->table($this->accountTable)->insertGetId($attributes);
    }

    /**
     * {@inheritdoc}
     */
    protected function createTransaction($attributes)
    {
        $allowedAttributes = [];

        foreach ($this->db->getSchemaBuilder()->getColumnListing($this->transactionTable) as $column) {
            if ($column->isPrimaryKey && $column->autoIncrement) {
                continue;
            }
            $allowedAttributes[] = $column->name;
        }
        $attributes = $this->serializeAttributes($attributes, $allowedAttributes);

        return $this->db->table($this->transactionTable)->insertGetId($attributes);
    }

    /**
     * {@inheritdoc}
     */
    protected function incrementAccountBalance($accountId, $amount)
    {
        $value = new Expression("[[{$this->accountBalanceAttribute}]]+:amount", ['amount' => $amount]);
        $this->db->createCommand()
            ->update($this->accountTable, [$this->accountBalanceAttribute => $value], [$this->getAccountIdAttribute() => $accountId])
            ->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function calculateBalance($account)
    {
        $accountId = $this->fetchAccountId($account);

        return $this->db->query()
            ->from($this->transactionTable)
            ->where([$this->accountLinkAttribute => $accountId])
            ->sum($this->amountAttribute);
    }

    /**
     * {@inheritdoc}
     */
    protected function createDbTransaction()
    {
        if ($this->db->getTransaction() !== null) {
            return null;
        }
        return $this->db->beginTransaction();
    }
}
