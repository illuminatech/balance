<p align="center">
    <a href="https://github.com/illuminatech" target="_blank">
        <img src="https://avatars1.githubusercontent.com/u/47185924" height="100px">
    </a>
    <h1 align="center">Balance Accounting System extension for Laravel</h1>
    <br>
</p>

This extension provides basic support for balance accounting (bookkeeping) system based on [debit and credit](https://en.wikipedia.org/wiki/Debits_and_credits) principle.

For license information check the [LICENSE](LICENSE.md)-file.

[![Latest Stable Version](https://img.shields.io/packagist/v/illuminatech/balance.svg)](https://packagist.org/packages/illuminatech/balance)
[![Total Downloads](https://img.shields.io/packagist/dt/illuminatech/balance.svg)](https://packagist.org/packages/illuminatech/balance)
[![Build Status](https://travis-ci.org/illuminatech/balance.svg?branch=master)](https://travis-ci.org/illuminatech/balance)


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist illuminatech/balance
```

or add

```json
"illuminatech/balance": "*"
```

to the require section of your composer.json.


Usage
-----

This extension provides basic support for balance accounting (bookkeeping) system based on [debit and credit](https://en.wikipedia.org/wiki/Debits_and_credits) principle.
Balance system is usually used for the accounting (bookkeeping) and money operations. However, it may also be used for any
resource transferring from one location to another. For example: transferring goods from storehouse to the shop and so on.

There 2 main terms related to the balance system:

 - account - virtual storage of the resources, which have some logical meaning.
 - transaction - represents actual transfer of the resources to or from particular account.

Lets assume we have a system, which provides virtual money balance for the user. Money on the balance can be used for the
goods purchasing, user can top up his balance via some payment gateway. In such example, each user should have 3 virtual
balance accounts: 'virtual-money', 'payment-gateway' and 'purchases'. When user tops up his virtual balance, our system
should remove money from 'payment-gateway' and add them to 'virtual-money'. When user purchases an item, our system should
remove money from 'virtual-money' and add them to 'purchases'.
The trick is: if you sum current amount over all user related accounts ('payment-gateway' + 'virtual-money' + 'purchases'),
it will always be equal to zero. Such check allows you to verify is something went wrong any time.

This extension introduces term 'balance manager' as a service, which should handle all balance transactions.
Public contract for such manager is determined by `\Illuminatech\Balance\BalanceContract` interface.
Following particular implementations are provided:

 - [\Illuminatech\Balance\BalanceDb](src/BalanceDb.php) - uses a relational database as a data storage.

Please refer to the particular manager class for more details.

This extension provides `\Illuminatech\Balance\BalanceServiceProvider` service provider, which binds `\Illuminatech\Balance\BalanceContract`
as a singleton in DI container. Thus you can get balance manager via automatic DI injections or via container instance.
For example:

```php
<?php

use Illuminate\Container\Container;
use App\Http\Controllers\Controller;
use Illuminatech\Balance\BalanceContract;

class BalanceController extends Controller
{
    public function increase(BalanceContract $balance, $accountId, $amount)
    {
        $balance->increase($accountId, $amount);
        
        // ...
    }
    
    public function decrease($accountId, $amount)
    {
        $balance = Container::getInstance()->get(BalanceContract::class);
        
        $balance->decrease($accountId, $amount);
        
        // ...
    }
    
    // ...
}
```

You may as well use `\Illuminatech\Balance\Facades\Balance` facade. For example:

```php
<?php

use Illuminatech\Balance\Facades\Balance;

Balance::increase($accountId, $amount);
```

In these documentation facade is used in code snippets for simplicity.


## Application configuration <span id="application-configuration"></span>

This extension uses [illuminatech/array-factory](https://github.com/illuminatech/array-factory) for configuration.
Make sure you are familiar with 'array factory' concept before configuring this extension.
Configuration is stored at 'config/balance.php' file.

You can publish predefined configuration file using following console command:

```
php artisan vendor:publish --provider="Illuminatech\Balance\BalanceServiceProvider" --tag=config
```

In case you are using `\Illuminatech\Balance\BalanceDb`, you can publish predefined database migration for it
using following console command:

```
php artisan vendor:publish --provider="Illuminatech\Balance\BalanceServiceProvider" --tag=migrations
```


## Basic operations <span id="basic-operations"></span>

In order to increase (debit) balance at particular account, `\Illuminatech\Balance\BalanceContract::increase()` method is used:

```php
<?php

use Illuminatech\Balance\Facades\Balance;

Balance::increase($accountId, 500); // add 500 credits to account
```

In order to decrease (credit) balance at particular account, `\Illuminatech\Balance\BalanceContract:decrease()` method is used:

```php
<?php

use Illuminatech\Balance\Facades\Balance;

Balance::decrease($accountId, 100); // remove 100 credits from account
```

> Tip: actually, method `decrease()` is redundant, you can call `increase()` with negative amount in order to achieve same result.

It is unlikely you will use plain `increase()` and `decrease()` methods in your application. In most cases there is a need
to **transfer** money from one account to another at once. Method `\Illuminatech\Balance\BalanceContract::transfer()` can be
used for this:

```php
<?php

use Illuminatech\Balance\Facades\Balance;

$fromId = 1;
$toId = 2;
Balance::transfer($fromId, $toId, 100); // remove 100 credits from account 1 and add 100 credits to account 2
```

Note that method `transfer()` creates 2 separated transactions: one per each affected account. Thus you can easily fetch
all money transfer history for particular account, simply selecting all transactions linked to it. 'Debit' transactions
will have positive amount, while 'credit' ones - negative.

> Note: If you wish each transaction created by `transfer()` remember another account involved in the process, you'll need
  to setup `\Illuminatech\Balance\Balance::$extraAccountLinkAttribute`.

You may revert particular transaction using `\Illuminatech\Balance\BalanceContract::revert()` method:

```php
<?php

use Illuminatech\Balance\Facades\Balance;

Balance::revert($transactionId);
```

This method will not remove original transaction, but create new one, which compensates it.


## Querying accounts <span id="querying-accounts"></span>

Using account IDs for the balance manager is not very practical. In our above example, each system user have 3 virtual
accounts, each of which has its own unique ID. However, while performing purchase, we operate user ID and account type,
so we need to query actual account ID before using balance manager.
Thus there is an ability to specify account for the balance manager methods using their attributes set. For example:

```php
<?php

use Illuminatech\Balance\Facades\Balance;

$user = request()->user();

Balance::transfer(
    [
        'userId' => $user->id,
        'type' => 'virtual-money',
    ],
    [
        'userId' => $user->id,
        'type' => 'purchases',
    ],
    500
);
```

In this example balance manager will find ID of the affected accounts automatically, using provided attributes as a filter.

You may enable `\Illuminatech\Balance\Balance::$autoCreateAccount`, allowing automatic creation of the missing accounts, if they
are specified as attributes set. This allows accounts creation on the fly, by demand only, eliminating necessity of their
pre-creation.

**Heads up!** Actually 'account' entity is redundant at balance system, and its usage can be avoided. However, its presence
provides more flexibility and saves performance. Storing of account data is not mandatory for this extension, you can
configure your balance manager in the way it is not used.


## Finding account current balance <span id="finding-account-current-balance"></span>

Current money amount at particular account can always be calculated as a sum of amounts over related transactions.
You can use `\Illuminatech\Balance\BalanceContract::calculateBalance()` method for that:

```php
<?php

use Illuminatech\Balance\Facades\Balance;

Balance::transfer($fromAccount, $toAccount, 100); // assume this is first time accounts are affected

echo Balance::calculateBalance($fromAccount); // outputs: -100
echo Balance::calculateBalance($toAccount); // outputs: 100
```

However, calculating current balance each time you need it, is not efficient. Thus you can specify an attribute of account
entity, which will be used to store current account balance. This can be done via `\Illuminatech\Balance\Balance::$accountBalanceAttribute`.
Each time balance manager performs a transaction it will update this attribute accordingly:

```php
<?php

use Illuminate\Support\Facades\DB;
use Illuminatech\Balance\Facades\Balance;

Balance::transfer($fromAccountId, $toAccountId, 100); // assume this is first time accounts are affected

$currentBalance = DB::table('balance_accounts')
    ->select(['balance'])
    ->where(['id' => $fromAccountId])
    ->value('balance');

echo $currentBalance; // outputs: -100
```


## Saving extra transaction data <span id="saving-extra-transaction-data"></span>

Usually there is a necessity to save extra information along with the transaction. For example: we may need to save
payment ID received from payment gateway. This can be achieved in following way:

```php
<?php

use Illuminatech\Balance\Facades\Balance;

$user = request()->user();

// simple increase :
Balance::increase(
    [
        'userId' => $user->id,
        'type' => 'virtual-money',
    ],
    100,
    // extra data associated with transaction :
    [
        'paymentGateway' => 'PayPal',
        'paymentId' => 'abcxyzerft',
    ]
);

// transfer :
Balance::transfer(
    [
        'userId' => $user->id,
        'type' => 'payment-gateway',
    ],
    [
        'userId' => $user->id,
        'type' => 'virtual-money',
    ],
    100,
    // extra data associated with transaction :
    [
        'paymentGateway' => 'PayPal',
        'paymentId' => 'abcxyzerft',
    ]
);
```

The way extra attributes are stored in the data storage depends on particular balance manager implementation.
For example: `\Illuminatech\Balance\BalanceDb` will try to store extra data inside transaction table columns, if their name
equals the parameter name. You may as well setup special data field via `\Illuminatech\Balance\BalanceDb::$dataAttribute`,
which will store all extra parameters, which have no matching column, in serialized state.

> Note: watch for the keys you use in transaction data: make sure they do not conflict with columns, which are
  reserved for other purposes, like primary keys.


## Events <span id="events"></span>

`\Illuminatech\Balance\Balance` provides several events, which can be handled via event listener:

 - [\Illuminatech\Balance\Events\CreatingTransaction](src/Events/CreatingTransaction.php) - raised before creating new transaction.
 - [\Illuminatech\Balance\Events\TransactionCreated](src/Events/TransactionCreated.php) - raised after creating new transaction.

For example:

```php
<?php

use Illuminate\Support\Facades\Event;
use Illuminatech\Balance\Facades\Balance;
use Illuminatech\Balance\Events\TransactionCreated;
use Illuminatech\Balance\Events\CreatingTransaction;

Event::listen(CreatingTransaction::class, function (CreatingTransaction $event) {
    $event->data['amount'] += 10; // you may adjust transaction data to be saved, including transaction amount
    $event->data['comment'] = 'adjusted by event handler';
});

Event::listen(TransactionCreated::class, function (TransactionCreated $event) {
    echo 'new transaction: '.$event->transactionId; // you may get newly created transaction ID
});

Balance::increase(1, 100); // outputs: 'new transaction: 1'
echo Balance::calculateBalance(1); // outputs: 110
```
