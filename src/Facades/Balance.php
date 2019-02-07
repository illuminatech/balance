<?php
/**
 * @link https://github.com/illuminatech
 * @copyright Copyright (c) 2015 Illuminatech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace Illuminatech\Balance\Facades;

use Illuminatech\Balance\BalanceFake;
use Illuminate\Support\Facades\Facade;
use Illuminatech\Balance\BalanceContract;

/**
 * Balance is a facade for balance manager access.
 *
 * This facade requires {@link \Illuminatech\Balance\BalanceContract} implementation to be bound as singleton
 * to the application service container.
 *
 * @see \Illuminatech\Balance\BalanceContract
 *
 * @method static mixed increase(array|mixed $account, int|float $amount, array $data = [])
 * @method static mixed decrease(array|mixed $account, int|float $amount, array $data = [])
 * @method static array transfer(array|mixed $from, array|mixed $to, int|float $amount, array $data = [])
 * @method static array|mixed revert(mixed $transactionId, array $data = [])
 * @method static int|float calculateBalance(array|mixed $account)
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class Balance extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return BalanceContract::class;
    }

    /**
     * Replace the bound instance with a fake.
     *
     * @return \Illuminatech\Balance\BalanceFake
     */
    public static function fake()
    {
        static::swap($fake = new BalanceFake);

        return $fake;
    }
}
