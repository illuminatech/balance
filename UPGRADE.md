Upgrading Instructions for Laravel Balance Accounting System
============================================================

!!!IMPORTANT!!!

The following upgrading instructions are cumulative. That is,
if you want to upgrade from version A to version C and there is
version B between A and C, you need to following the instructions
for both A and B.

Upgrade from 1.1.0
------------------

* New abstract method `findLastTransaction()` has been added to `\Illuminatech\Balance\Balance` class.
  Make sure you provide implementation for the new method in case you extend this class.


Upgrade from 1.0.0
------------------

* "illuminate/database" package requirements have been raised to 6.0. Make sure to upgrade your code accordingly.
