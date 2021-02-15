<?php
/**
 * @link https://github.com/illuminatech
 * @copyright Copyright (c) 2019 Illuminatech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace Illuminatech\Balance;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Illuminatech\ArrayFactory\Factory;

/**
 * BalanceServiceProvider bootstraps balance manager to Laravel application.
 *
 * This service provider registers balance manager as a singleton, facilitating functioning of the
 * {@see \Illuminatech\Balance\Facades\Balance} facade.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class BalanceServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->registerPublications();

        $this->app->singleton(BalanceContract::class, function () {
            $balance = $this->createBalance();

            if ($balance instanceof Balance && $this->app->has(Dispatcher::class)) {
                $balance->setEventDispatcher($this->app->get(Dispatcher::class));
            }

            return $balance;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function provides()
    {
        return [
            BalanceContract::class,
        ];
    }

    /**
     * Creates new balance manager instance, which should be used as singleton.
     *
     * @return BalanceContract balance manager instance.
     */
    protected function createBalance(): BalanceContract
    {
        return (new Factory($this->app))->make(array_merge(
            [
                '__class' => BalanceDb::class,
            ],
            $this->app->get('config')->get('balance', [])
        ));
    }

    /**
     * Register resources to be published by the publish command.
     */
    protected function registerPublications(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../config/balance.php' => $this->app->make('path.config').DIRECTORY_SEPARATOR.'balance.php',
        ], 'config');

        if (! class_exists(\CreateBalanceTables::class)) {
            $timestamp = date('Y_m_d_His', time());

            $this->publishes([
                __DIR__.'/../database/migrations/create_balance_tables.php.stub' => $this->app->databasePath().'/migrations/'.$timestamp.'_create_balance_tables.php',
            ], 'migrations');
        }
    }
}
