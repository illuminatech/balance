<?php
/**
 * @link https://github.com/illuminatech
 * @copyright Copyright (c) 2015 Illuminatech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace Illuminatech\Balance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * BalanceServiceProvider bootstraps balance manager to Laravel application.
 *
 * This service provider registers balance manager as a singleton, facilitating functioning of the
 * {@link \Illuminatech\Balance\Facades\Balance} facade.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class BalanceServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    protected $defer = false;

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton(BalanceContract::class, function () {
            $balance = $this->createBalance();

            if ($balance instanceof Balance && $this->app->has(Dispatcher::class)) {
                $balance->setEventDispatcher($this->app->get(Dispatcher::class));
            }

            return $balance;
        });
    }

    /**
     * Creates new balance manager instance, which should be used as singleton.
     *
     * @return BalanceContract balance manager instance.
     */
    protected function createBalance(): BalanceContract
    {
        return new BalanceDb(DB::connection());
    }
}
