<p align="center">
    <a href="https://github.com/illuminatech" target="_blank">
        <img src="https://avatars1.githubusercontent.com/u/47185924" height="100px">
    </a>
    <h1 align="center">Balance Accounting System extension for Laravel</h1>
    <br>
</p>

This extension provides basic support for balance accounting (bookkeeping) system based on [debit and credit](https://en.wikipedia.org/wiki/Debits_and_credits) principle.

For license information check the [LICENSE](LICENSE.md)-file.

[![Latest Stable Version](https://poser.pugx.org/illuminatech/balance/v/stable.png)](https://packagist.org/packages/illuminatech/balance)
[![Total Downloads](https://poser.pugx.org/illuminatech/balance/downloads.png)](https://packagist.org/packages/illuminatech/balance)
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
