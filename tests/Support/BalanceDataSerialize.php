<?php

namespace Illuminatech\Balance\Test\Support;

use Illuminatech\Balance\BalanceFake;
use Illuminatech\Balance\DataSerializable;

class BalanceDataSerialize extends BalanceFake
{
    use DataSerializable;

    /**
     * {@inheritdoc}
     */
    protected function createTransaction($attributes)
    {
        static $allowedAttributes = [
            'date',
            'accountId',
            'amount',
        ];
        $attributes = $this->serializeAttributes($attributes, $allowedAttributes);

        return parent::createTransaction($attributes);
    }

    /**
     * {@inheritdoc}
     */
    protected function findTransaction($id)
    {
        $transaction = parent::findTransaction($id);
        if ($transaction === null) {
            return $transaction;
        }

        return $this->unserializeAttributes($transaction);
    }
}
