<?php

namespace Illuminatech\Balance\Test;

use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Events\Dispatcher;
use InvalidArgumentException;
use Illuminatech\Balance\BalanceDb;
use Illuminate\Database\Schema\Blueprint;

/**
 * @group db
 */
class BalanceDbTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
    }

    /**
     * Setup the database schema.
     *
     * @return void
     */
    protected function createSchema()
    {
        $this->getSchemaBuilder()->create('balance_accounts', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('user_id');
            $table->bigInteger('balance')->default(0);
        });

        $this->getSchemaBuilder()->create('balance_transactions', function (Blueprint $table) {
            $table->id('id');
            $table->timestamp('created_at');
            $table->unsignedBigInteger('account_id');
            $table->integer('amount');
            $table->integer('new_balance')->nullable();
            $table->text('data')->nullable();
        });
    }

    /**
     * @return object last saved transaction data.
     */
    protected function getLastTransaction()
    {
        return $this->getConnection()->query()
            ->from('balance_transactions')
            ->orderBy('id', 'desc')
            ->first();
    }

    public function testIncrease()
    {
        $manager = new BalanceDb($this->getConnection());

        $manager->increase(1, 50);
        $transaction = $this->getLastTransaction();
        $this->assertEquals(50, $transaction->amount);

        $manager->increase(1, 50, ['extra' => 'custom']);
        $transaction = $this->getLastTransaction();
        $this->assertStringContains('custom', $transaction->data);
    }

    /**
     * @depends testIncrease
     */
    public function testAutoCreateAccount()
    {
        $manager = new BalanceDb($this->getConnection());

        $manager->autoCreateAccount = true;
        $manager->increase(['user_id' => 5], 10);
        $accounts = $this->getConnection()->query()
            ->from('balance_accounts')
            ->get();
        $this->assertCount(1, $accounts);
        $this->assertEquals(5, $accounts[0]->user_id);

        $manager->autoCreateAccount = false;
        $this->expectException(InvalidArgumentException::class);
        $manager->increase(['user_id' => 10], 10);
    }

    /**
     * @depends testAutoCreateAccount
     */
    public function testIncreaseAccountBalance()
    {
        $manager = new BalanceDb($this->getConnection());
        $manager->autoCreateAccount = true;
        $manager->accountBalanceAttribute = 'balance';

        $amount = 50;
        $manager->increase(['user_id' => 1], $amount);
        $account = $this->getConnection()->query()
            ->from('balance_accounts')
            ->where(['user_id' => 1])
            ->first();

        $this->assertEquals($amount, $account->balance);

        // update :
        $amount = 50;
        $manager->increase(['user_id' => 1], $amount);
        $account = $this->getConnection()->query()
            ->from('balance_accounts')
            ->where(['user_id' => 1])
            ->first();
        $this->assertEquals(100, $account->balance);
    }

    /**
     * @depends testIncrease
     */
    public function testRevert()
    {
        $manager = new BalanceDb($this->getConnection());

        $accountId = 1;
        $amount = 10;
        $transactionId = $manager->increase($accountId, $amount);
        $manager->revert($transactionId);

        $transaction = $this->getLastTransaction();
        $this->assertEquals($accountId, $transaction->account_id);
        $this->assertEquals(-$amount, $transaction->amount);
    }

    /**
     * @depends testIncrease
     */
    public function testCalculateBalance()
    {
        $manager = new BalanceDb($this->getConnection());

        $manager->increase(1, 50);
        $manager->increase(2, 50);
        $manager->decrease(1, 25);

        $this->assertEquals(25, $manager->calculateBalance(1));
    }

    /**
     * @depends testIncrease
     */
    public function testSkipAutoIncrement()
    {
        $manager = new BalanceDb($this->getConnection());

        $manager->transfer(
            1,
            2,
            10,
            [
                'id' => 123456789,
            ]
        );
        $transaction = $this->getLastTransaction();
        $this->assertStringContains('123456789', $transaction->data);
    }

    /**
     * @depends testIncrease
     */
    public function testSaveNewBalance()
    {
        $manager = new BalanceDb($this->getConnection());
        $manager->newBalanceAttribute = 'new_balance';

        $manager->increase(1, 50);
        $transaction = $this->getLastTransaction();
        $this->assertEquals(50, $transaction->new_balance);

        $manager->increase(1, 25);
        $transaction = $this->getLastTransaction();
        $this->assertEquals(75, $transaction->new_balance);

        $manager->decrease(1, 50);
        $transaction = $this->getLastTransaction();
        $this->assertEquals(25, $transaction->new_balance);
    }

    /**
     * @depends testIncrease
     */
    public function testDbTransaction()
    {
        $db = $this->getConnection();
        $manager = new BalanceDb($db);
        $manager->dbTransactionEnabled = true;
        $manager->dbTransactionNestedEnabled = true;

        $db->beginTransaction();
        $manager->increase(1, 50);
        $db->rollBack();

        $this->assertEquals(0, $db->table('balance_transactions')->count());
    }

    /**
     * @depends testDbTransaction
     */
    public function testDisableDbTransaction()
    {
        $db = $this->getConnection();
        $db->setEventDispatcher(new Dispatcher());

        $transactionCount = 0;
        $db->getEventDispatcher()->listen(TransactionBeginning::class, function (TransactionBeginning $event) use (&$transactionCount) {
            $transactionCount++;
        });

        $manager = new BalanceDb($db);

        $transactionCount = 0;
        $manager->dbTransactionEnabled = true;
        $manager->dbTransactionNestedEnabled = true;
        $manager->increase(1, 50);
        $this->assertEquals(1, $transactionCount);

        $transactionCount = 0;
        $manager->dbTransactionEnabled = true;
        $manager->dbTransactionNestedEnabled = true;
        $db->beginTransaction();
        $manager->increase(1, 50);
        $this->assertEquals(2, $transactionCount);
        $db->rollBack();

        $transactionCount = 0;
        $manager->dbTransactionEnabled = false;
        $manager->dbTransactionNestedEnabled = true;
        $db->beginTransaction();
        $manager->increase(1, 50);
        $this->assertEquals(1, $transactionCount);
        $db->rollBack();

        $transactionCount = 0;
        $manager->dbTransactionEnabled = true;
        $manager->dbTransactionNestedEnabled = false;
        $db->beginTransaction();
        $manager->increase(1, 50);
        $this->assertEquals(1, $transactionCount);
        $db->rollBack();
    }
}
