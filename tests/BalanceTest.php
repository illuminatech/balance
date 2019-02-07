<?php

namespace Illuminatech\Balance\Test;

use Illuminate\Events\Dispatcher;
use Illuminatech\Balance\BalanceFake;
use Illuminate\Support\Testing\Fakes\EventFake;
use Illuminatech\Balance\Events\CreatingTransaction;
use Illuminatech\Balance\Events\TransactionCreated;
use InvalidArgumentException;

class BalanceTest extends TestCase
{
    protected function fakeEventDispatcher(): EventFake
    {
        return new EventFake(new Dispatcher());
    }

    public function testIncrease()
    {
        $manager = new BalanceFake();

        $manager->increase(1, 50);
        $transaction = $manager->getLastTransaction();
        $this->assertEquals(50, $transaction['amount']);

        $manager->increase(1, 50, ['extra' => 'custom']);
        $transaction = $manager->getLastTransaction();
        $this->assertEquals('custom', $transaction['extra']);
    }

    /**
     * @depends testIncrease
     */
    public function testDecrease()
    {
        $manager = new BalanceFake();

        $manager->decrease(1, 50);
        $transaction = $manager->getLastTransaction();
        $this->assertEquals(-50, $transaction['amount']);
    }

    /**
     * @depends testIncrease
     */
    public function testTransfer()
    {
        $manager = new BalanceFake();

        $manager->transfer(1, 2, 10);
        $transactions = $manager->getLastTransactionPair();
        $this->assertEquals(-10, $transactions[0]['amount']);
        $this->assertEquals(10, $transactions[1]['amount']);

        $manager->transfer(1, 2, 10, ['extra' => 'custom']);
        $transaction = $manager->getLastTransaction();
        $this->assertEquals('custom', $transaction['extra']);
    }

    /**
     * @depends testIncrease
     */
    public function testDateAttributeValue()
    {
        $manager = new BalanceFake();

        $now = time();
        $manager->increase(1, 10);
        $transaction = $manager->getLastTransaction();
        $this->assertTrue($transaction['created_at']->getTimestamp() >= $now);

        $manager->dateAttributeValue = function() {
            return 'callback';
        };
        $manager->increase(1, 10);
        $transaction = $manager->getLastTransaction();
        $this->assertEquals('callback', $transaction['created_at']);

        $manager->dateAttributeValue = new \DateTime();
        $manager->increase(1, 10);
        $transaction = $manager->getLastTransaction();
        $this->assertEquals($manager->dateAttributeValue, $transaction['created_at']);
    }

    /**
     * @depends testIncrease
     */
    public function testAutoCreateAccount()
    {
        $manager = new BalanceFake();

        $manager->autoCreateAccount = true;
        $manager->increase(['userId' => 5], 10);
        $this->assertCount(1, $manager->accounts);

        $manager->autoCreateAccount = false;
        $this->expectException(InvalidArgumentException::class);
        $manager->increase(['userId' => 10], 10);
    }

    /**
     * @depends testIncrease
     */
    public function testIncreaseAccountBalance()
    {
        $manager = new BalanceFake();

        $manager->accountBalanceAttribute = 'balance';
        $accountId = 10;
        $amount = 50;
        $manager->increase($accountId, $amount);
        $this->assertEquals($amount, $manager->accountBalances[$accountId]);

        $manager->accountBalanceAttribute = null;
        $accountId = 20;
        $amount = 40;
        $manager->increase($accountId, $amount);
        $this->assertArrayNotHasKey($accountId, $manager->accountBalances);
    }

    /**
     * @depends testTransfer
     */
    public function testSaveExtraAccount()
    {
        $manager = new BalanceFake();

        $manager->extraAccountLinkAttribute = 'extraAccountId';
        $manager->transfer(1, 2, 10);
        $transactions = $manager->getLastTransactionPair();
        $this->assertEquals(2, $transactions[0][$manager->extraAccountLinkAttribute]);
        $this->assertEquals(1, $transactions[1][$manager->extraAccountLinkAttribute]);
    }

    /**
     * @depends testIncreaseAccountBalance
     * @depends testTransfer
     */
    public function testRevert()
    {
        $manager = new BalanceFake();
        $manager->accountBalanceAttribute = 'balance';
        $manager->extraAccountLinkAttribute = 'extraAccountId';

        $accountId = 1;
        $transactionId = $manager->increase($accountId, 10);
        $manager->revert($transactionId);

        $this->assertEquals(0, $manager->accountBalances[$accountId]);

        $fromId = 10;
        $toId = 20;
        $transactionIds = $manager->transfer($fromId, $toId, 10);
        $manager->revert($transactionIds[0]);

        $this->assertEquals(0, $manager->accountBalances[$fromId]);
        $this->assertEquals(0, $manager->accountBalances[$toId]);
    }

    /**
     * @depends testIncrease
     */
    public function testEventBeforeCreateTransaction()
    {
        $manager = new BalanceFake();
        $manager->setEventDispatcher(new Dispatcher());

        $manager->getEventDispatcher()->listen(CreatingTransaction::class, function (CreatingTransaction $event) {
            $event->data['extra'] = 'event';
        });

        $manager->increase(1, 50);
        $transaction = $manager->getLastTransaction();
        $this->assertEquals('event', $transaction['extra']);
    }

    /**
     * @depends testIncrease
     */
    public function testEventAfterCreateTransaction()
    {
        $manager = new BalanceFake();
        $manager->setEventDispatcher(new Dispatcher());

        $eventTransactionId = null;
        $manager->getEventDispatcher()->listen(TransactionCreated::class, function (TransactionCreated $event) use (&$eventTransactionId) {
            $eventTransactionId = $event->transactionId;
        });

        $manager->increase(1, 50);
        $transaction = $manager->getLastTransaction();
        $this->assertNotNull($eventTransactionId);
        $this->assertSame($eventTransactionId, $transaction['id']);
    }
}
