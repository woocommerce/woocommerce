# WooCommerce `includes` files

This directory contains WooCommerce legacy code. Ideally, the code in this folder should only get the minimum required changes for bug fixing, and any new code should go in [the `src` directory](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/src/README.md) instead.


## A note on `@internal` annotations

Some classes, methods and functions in this folder have an `@internal` annotation in their documentation comment block. This means that the code entity is intended for internal usage of WooCommerce core only, and **must not** be used in extensions. Backwards compatibility for these code entities is not guaranteed: they could be renamed, modified (in behavior, return value or arguments accepted) or deleted.


## Interacting with the `src` folder

Whenever you need to get an instance of a class from the `src` directory, please don't instantiate it directly, but instead use [the container](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/src/README.md#the-container). To get an instance of the container itself you can use the `wc_get_container` function, for example:


```php
$container = wc_get_container();
$service = $container->get( \Automattic\WooCommerce\TheNamespace\TheService::class );
$service->do_something();
```

The exception to this rule might be data-only classes that could be created the old way (using a plain `new` statement); but in general, all classes in the `src` directory are registered in the container and should be resolved using it.


## Adding new actions and filters

Please take a look at [the considerations for creation new hooks in `src` code](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/src/README.md#defining-new-actions-and-filters), as they apply for `includes` code as well. The short version is that **new hooks should be introduced only if they provide a valuable extension point for plugins**, and not with the purpose of driving WooCommerce's internal logic.


## Writing unit tests

[As it's the case for the `src` folder](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/src/README.md#writing-unit-tests), writing unit tests is generally mandatory if you are a WooCommerce team member or a contributor from another Automattic team, and encouraged if you are an external contributor. Tests should cover any new code (although as mentioned, adding new code in `includes` should be rare) and any modifications to existing code.

In order to make it easier to write unit tests, there are a couple of mechanisms in place that you can use:

* [The code hacker](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/Tools/CodeHacking/README.md). Pros: you don't need to do any special changes to your code to make it testable. Cons: it's a hack, the tested code is being actually modified while being loaded by the PHP engine, so not an ideal solution.

* [The legacy proxy and the related helper methods in WC_Unit_Test_case](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/src/README.md#interacting-with-legacy-code): although these are intended in principle for writing tests for code in the `src` directory, they can be used for `includes` code as well. Pros: a clean approach, no hacks involved. Cons: you need to modify your code to use the proxy whenever you need to call a function or static method that makes the code difficult to test.

It's up to you as a contributor to decide which mechanism to use in each case. Choose wisely.

