<?php
/**
 * @link https://github.com/illuminatech
 * @copyright Copyright (c) 2019 Illuminatech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace Illuminatech\Balance;

use Illuminate\Database\Connection;

/**
 * BalanceDb is a balance manager, which uses relational database as data storage.
 *
 * Configuration example:
 *
 * ```php
 * return [
 *     '__class' => Illuminatech\Balance\BalanceDb::class,
 *     'accountTable' => 'balance_accounts',
 *     'transactionTable' => 'balance_transactions',
 *     'accountBalanceAttribute' => 'balance',
 *     'extraAccountLinkAttribute' => 'extra_account_id',
 *     'dataAttribute' => 'data',
 * ];
 * ```
 *
 * Database migration example:
 *
 * ```php
 * Schema::create('balance_accounts', function (Blueprint $table) {
 *     $table->id('id');
 *     $table->bigInteger('balance')->default(0);
 *     // ...
 * });
 *
 * Schema::create('balance_transactions', function (Blueprint $table) {
 *     $table->id('id');
 *     $table->timestamp('created_at');
 *     $table->unsignedBigInteger('account_id');
 *     $table->unsignedBigInteger('extra_account_id');
 *     $table->integer('amount');
 *     $table->text('data')->nullable();
 *     // ...
 * });
 * ```
 *
 * You can publish the predefined migration using following command:
 *
 * ```
 * php artisan vendor:publish --provider="Illuminatech\Balance\BalanceServiceProvider" --tag="migrations"
 * ```
 *
 * This manager will attempt to save value from transaction data in the table column, which name matches data key.
 * If such column does not exist data will be saved in {@see dataAttribute} column in serialized state.
 *
 * > Note: watch for the keys you use in transaction data: make sure they do not conflict with columns, which are
 *   reserved for other purposes, like primary keys.
 *
 * @see Balance
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class BalanceDb extends BalanceDbTransaction
{
    use DataSerializable;

    /**
     * @var string name of the database table, which should store account records.
     */
    public $accountTable = 'balance_accounts';

    /**
     * @var string name of the database table, which should store transaction records.
     */
    public $transactionTable = 'balance_transactions';

    /**
     * @var string name of the account ID attribute at {@see accountTable}
     */
    public $accountIdAttribute = 'id';

    /**
     * @var string name of the transaction ID attribute at {@see transactionTable}
     */
    public $transactionIdAttribute = 'id';

    /**
     * @var \Illuminate\Database\Connection the DB connection instance.
     */
    private $connection;

    /**
     * Constructor.
     *
     * @param \Illuminate\Database\Connection $connection DB connection to be used.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return \Illuminate\Database\Connection DB connection instance.
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @param \Illuminate\Database\Connection $connection  DB connection to be used.
     * @return static self reference.
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function findAccountId($attributes)
    {
        $id = $this->connection->query()
            ->select([$this->accountIdAttribute])
            ->from($this->accountTable)
            ->where($attributes)
            ->value($this->accountIdAttribute);

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
        $idAttribute = $this->transactionIdAttribute;

        $row = $this->connection->query()
            ->from($this->transactionTable)
            ->where([$idAttribute => $id])
            ->first();

        if ($row === null) {
            return null;
        }

        return $this->unserializeAttributes((array)$row);
    }

    /**
     * {@inheritdoc}
     */
    protected function findLastTransaction($accountId)
    {
        $row = $this->connection->query()
            ->from($this->transactionTable)
            ->where([$this->accountLinkAttribute => $accountId])
            ->orderBy($this->dateAttribute, 'DESC')
            ->orderBy($this->transactionIdAttribute, 'DESC')
            ->first();

        if ($row === null) {
            return null;
        }

        return $this->unserializeAttributes((array)$row);
    }

    /**
     * {@inheritdoc}
     */
    protected function createAccount($attributes)
    {
        return $this->connection->table($this->accountTable)->insertGetId($attributes);
    }

    /**
     * {@inheritdoc}
     */
    protected function createTransaction($attributes)
    {
        $allowedAttributes = [];

        foreach ($this->connection->getSchemaBuilder()->getColumnListing($this->transactionTable) as $column) {
            if ($column === $this->transactionIdAttribute) {
                continue;
            }
            $allowedAttributes[] = $column;
        }

        $attributes = $this->serializeAttributes($attributes, $allowedAttributes);

        return $this->connection->table($this->transactionTable)->insertGetId($attributes);
    }

    /**
     * {@inheritdoc}
     */
    protected function incrementAccountBalance($accountId, $amount)
    {
        $this->connection->table($this->accountTable)
            ->where([$this->accountIdAttribute => $accountId])
            ->increment($this->accountBalanceAttribute, $amount);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateBalance($account)
    {
        $accountId = $this->fetchAccountId($account);

        return $this->connection->query()
            ->from($this->transactionTable)
            ->where([$this->accountLinkAttribute => $accountId])
            ->sum($this->amountAttribute);
    }

    /**
     * {@inheritdoc}
     */
    protected function beginDbTransaction()
    {
        $this->connection->beginTransaction();
    }

    /**
     * {@inheritdoc}
     */
    protected function commitDbTransaction()
    {
        $this->connection->commit();
    }

    /**
     * {@inheritdoc}
     */
    protected function rollBackDbTransaction()
    {
        $this->connection->rollBack();
    }
}
