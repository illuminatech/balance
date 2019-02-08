<?php

namespace Illuminatech\Balance\Test;

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
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->integer('balance')->default(0);
        });

        $this->getSchemaBuilder()->create('balance_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamp('created_at');
            $table->unsignedInteger('account_id');
            $table->integer('amount');
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
        $this->assertContains('custom', $transaction->data);
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
        $this->assertContains('123456789', $transaction->data);
    }
}
