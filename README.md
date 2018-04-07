# PHP Optimize

A package for micro optimizations within your PHP-code, intended to use for phars.

## The idea

The goal of this package is to provide multiple optimizations that you would not want to use in your code normally
but can be used within phars.

Currently it can replace calls to constants, e.g.
```php
return Foo::BAR; 
```

with its actual value, to gain a microscopic preformace boost.

This package should not be ran on your normal code, as it may change code style, however it can be ran as part of
the creation of a phar, to increase performance.
