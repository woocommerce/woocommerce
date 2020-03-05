# WooCommerce `src` files

This directory is home to new WooCommerce class files under the \Automattic\WooCommerce\ namespace using PSR-4 file naming. This is to take full advantage of autoloading.

Currently, these classes have a PHP 5.6 requirement. No required core classes will be added here until this PHP version is enforced. If running an older version of PHP, these class files will not be used.

## Installing Composer

Composer is used to generate autoload class-maps for the files here. The stable release of WooCommerce comes with the autoloader, however, if you're running a development version you'll need to use Composer.

If you don't have Composer installed, go and check how to [install Composer](https://github.com/woocommerce/woocommerce/wiki/How-to-set-up-WooCommerce-development-environment) and then continue here.

## Installing packages

To install the packages WooCommerce requires, from the main directory run:

```
composer install
```

To update packages run:

```
composer update
```

If you add a class to WooCommerce and want to ensure it's included in the autoloader class-maps, run:

```
composer dump-autoload
```

### Using classes

To use something a namespaced class you have to declare it at the top of the file before any other instruction, and then use it in the code. For example:

```php
use Automattic\WooCommerce\TestClass;

// other code...

$test_class = new TestClass();
```

If you need to rule out conflicts, you can alias it:

```php
use Automattic\WooCommerce\TestClass as Test_Class_Alias;

// other code...

$test_class = new Test_Class_Alias();
```
