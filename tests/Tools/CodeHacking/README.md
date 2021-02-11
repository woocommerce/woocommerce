# Code Hacking

Code hacking is a mechanism that modifies PHP code files while they are loaded. It's intended to ease unit testing code that would otherwise be very difficult or impossible to test (and **only** for this - see [An important note](#an-important-note) about that).

Currently, the code hacker allows to do the following inside unit tests:

* Replace standalone functions with custom callbacks.
* Replace invocations to public static methods with custom callbacks.
* Create subclasses of `final` classes.

## How to use

Let's go through an example.

First, create a file named `class-wc-admin-foobar.php` in `includes/admin` with the following code:

```
<?php

class WC_Admin_Foobar {
	public function do_something_that_depends_on_an_option() {
		return 'The option returns: ' . get_option('some_option', 'default option value');
	}

	public function do_something_that_depends_on_the_legacy_service( $what ) {
		return 'The legacy service returns: ' . WC_Some_Legacy_Service::do_something( $what );
	}
}

class WC_Some_Legacy_Service {
	public static function do_something( $what ) {
		return "The legacy service does something with: " . $what;
	}
}
```

This class has two bits that are difficult to unit test: the call to `WC_Some_Legacy_Service::do_something` (let's assume that we can't refactor that one) and the `get_option` invocation. Let's see how the code hacker can help us with that. 

Now, modify `tests/legacy/mockable-functions.php` so that the returned array contains `'get_option'` (if it doesn't already), and modify `tests/legacy/classes-with-mockable-static-methods.php` so that the returned array contains `WC_Some_Legacy_Service`.

Create a file named `class-wc-tests-admin-foobar.php` in `tests/unit-tests/admin` with this code:

```
<?php

use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\FunctionsMockerHack;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\StaticMockerHack;

class WC_Tests_Admin_Foobar extends WC_Unit_Test_Case {
	public function test_functions_mocking() {
		$tested = new WC_Admin_Foobar();

		FunctionsMockerHack::add_function_mocks([
			'get_option' => function( $name, $default = false ) {
				return "Mocked get_option invoked for '$name'";
			}
		]);

		$expected = "The option returns: Mocked get_option invoked for 'some_option'";
		$actual = $tested->do_something_that_depends_on_an_option();
		$this->assertEquals( $expected, $actual );
	}

	public function test_static_method_mocking() {
		$tested = new WC_Admin_Foobar();

		StaticMockerHack::add_method_mocks([
			'WC_Some_Legacy_Service' => [
				'do_something' => function( $what ) {
					return "MOCKED do_something invoked for '$what'";
				}
			]
		]);

		$expected = "The legacy service returns: MOCKED do_something invoked for 'foobar'";
		$actual = $tested->do_something_that_depends_on_the_legacy_service( 'foobar' );
		$this->assertEquals( $expected, $actual );
	}
}
```

Then run `vendor/bin/phpunit tests/legacy/unit-tests/admin/class-wc-tests-admin-foobar.php` and see the magic happen.

### Mocking functions

For a function to be mockable its name needs to be included in the array returned by `tests/legacy/mockable-functions.php`, so if you need to mock a function that is not included in the array, just go and add it.

Function mocks can be defined by using `FunctionsMockerHack::add_function_mocks`. This method accepts an associative array where keys are function names and values are callbacks with the same signature as the functions they are replacing.

If you ever need to remove the configured function mocks from inside a test, you can do so by executing `FunctionsMockerHack::get_hack_instance()->reset()`. This is done automatically before each test via PHPUnit's `BeforeTestHook`, so normally you won't need to do that.

Note that the code hacker is configured so that only the production code files are modified, the tests code itself is **not** modified. This means that you can use the original functions within your tests even if you have mocked them, for example the following would work:

```
//Mock get_option but only if the requested option name is 'foo'
FunctionsMockerHack::add_function_mocks([
    'get_option' => function($name, $default = false) {
        return 'foo' === $name ? 'mocked value for option foo' : get_option( $name, $default );
    }
]);
```

### Mocking public static methods

For a public static method to be mockable the name of the class that defines it needs to be included in the array returned by `tests/legacy/classes-with-mockable-static-methods.php`, so if you need to mock a static method for a class that is not included in the array, just go and add it.
 
Static method mocks can be defined by using `StaticMockerHack::add_method_mocks`. This method accepts an associative array where keys are class names and values are in turn associative arrays, those having method names as keys and callbacks with the same signature as the methods they are replacing as values.
 
If you ever need to remove the configured static method mocks from inside a test, you can do so by executing `StaticMockerHack::get_hack_instance()->reset()`. This is done automatically before each test via PHPUnit's `BeforeTestHook`, so normally you won't need to do that.
 
Note that the code hacker is configured so that only the production code files are modified, the tests code itself is **not** modified. This means that you can use the original static methods within your tests even if you have mocked them, for example the following would work:

```
StaticMockerHack::add_method_mocks([
    'WC_Some_Legacy_Service' => [
        //Mock WC_Some_Legacy_Service::do_something but only if the supplied parameter is 'foo'
        'do_something' => function( $what ) {
            return 'foo' === $what ? "MOCKED do_something invoked for '$what'" : WC_Some_Legacy_Service::do_something( $what );
        }
    ]
]);
```

### Subclassing `final` classes

Inside your test files you can create classes that extend classes marked as `final` thanks to the `BypassFinalsHack` that is registered at bootstrap time. No extra configuration is needed.

If you want to try it out, mark the `WC_Admin_Foobar` in the previos example as `final`, then add the following to the tests file: `class WC_Admin_Foobar_Subclass extends WC_Admin_Foobar {}`. Without the hack you would get a `Class WC_Admin_Foobar_Subclass may not inherit from final class (WC_Admin_Foobar)` error when trying to run the tests. 

## How it works under the hood

The core of the code hacker is the `CodeHacker` class, which is based on [the Bypass Finals project](https://github.com/dg/bypass-finals) by David Grudl. This class is actually [a streamWrapper class](https://www.php.net/manual/en/class.streamwrapper.php) for the regular filesystem, most of its methods are just short-circuited to the regular PHP filesystem handling functions but the `stream_open` method contains some code that allows the magic to happen. What it does (for PHP files only) is to read the file contents and apply all the necessary modifications to it, then if the code has been modified it is stored in a temporary file which is then the one that receives any further filesystem operations instead of the original file. That way, for all practical purposes the content of the file is the "hacked" content.  

The files inside `tests/Tools/CodeHacking/Hacks` implement the "hacks" (code file modifications) that are registered via `CodeHacker::add_hack` within `tests/legacy/bootstrap.php`.  

A `BeforeTestHook` is used to reset all hacks to its initial state to ensure that no functions or methods are being mocked when the test starts.

The functions mocker works by replacing all instances of `the_function(...)` with `FunctionsMockerHack::the_function(...)`, then `FunctionsMockerHack::__call_static` is implemented in a way that invokes the appropriate callback if defined for the invoked function, or reverts to executing the original function if not. The static methods mocker works similarly, but replacing instances of `TheClass::the_method(...)` with `StaticMockerHack::invoke__the_method__for__TheClass(...)`.   

## Creating new hacks

If you ever need to define a new hack to cover a different kind of code that's difficult to test, that's what you need to do.

First, implement the hack as a class that contains a `public function hack($code, $path)` method and a `public function reset()` method. The former takes in `$code` a string with the contents of the file pointed by `$path` and returns the modified code, the later reverts the hack to its original state (e.g. for `FunctionsMockerHack` it unregisters all the previously registered function mocks). For convenience you can make your hack a subclass of `CodeHack` but that's not mandatory.

Second, configure the hack as required inside the `initialize_code_hacker` method in `tests/legacy/bootstrap.php`, and register it using `CodeHacker::add_hack`.

## Temporarily disabling the code hacker

In a few rare cases the code hacker will cause problems with tests that do write operations on the local filesystem. In these cases it is possible to temporarily disable the code hacker using `self::disable_code_hacker()` and `self::reenable_code_hacker()` in the test (these methods are defined in `WC_Unit_Test_Case`). These methods are carefully written so that they won't enable the code hacker if it wasn't enabled when the test started, and there's a disabling requests count in place to ensure that the code hacker isn't enabled before it should.

One of these cases is the usage of the `copy` command to copy files. Since this function is used in a few tests, a convenience `file_copy` method is defined in `WC_Unit_Test_Case`; it just temporarily disables the hacker, does the copy, and reenables the hacker.

## An important note

The code hacker is intended to be a **last resort** mechanism to test stuff that it's **really** difficult or impossible to test otherwise - the mechanisms already in place to help testing (e.g. the PHPUnit's mocks or the Woo helpers) should still be used whenever possible. And of course, the code hacker should not be an excuse to write code that's difficult to test.
