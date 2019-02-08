<?php

namespace Illuminatech\Balance\Test;

use Illuminatech\Balance\Test\Support\BalanceDataSerialize;

class DataSerializableTest extends TestCase
{
    public function testSerialize()
    {
        $manager = new BalanceDataSerialize();

        $manager->increase(1, 50, ['extra' => 'custom']);
        $transaction = $manager->getLastTransaction();
        $this->assertEquals(50, $transaction['amount']);
        $this->assertStringContains('custom', $transaction['data']);
    }

    public function testUnserialize()
    {
        $manager = new BalanceDataSerialize();
        $manager->extraAccountLinkAttribute = 'extraAccountId';

        $fromId = 10;
        $toId = 20;
        $transactionIds = $manager->transfer($fromId, $toId, 10);
        $manager->revert($transactionIds[0]);

        $this->assertCount(4, $manager->transactions);
    }
}
