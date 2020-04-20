# Code Hacking

Code hacking is a mechanism that allows to temporarily modifying PHP code files while they are loaded. It's intended to ease unit testing code that would otherwise be very difficult or impossible to test (and **only** for this - see [An important note](#an-important-note) about that).

The code hacker consists of the following classes:

 * `CodeHacker`: the core class that performs the hacking and has some public configuration and activation methods.
 * `CodeHackerTestHook`: a PHPUnit hook class that wires everything so that the code hacking mechanism can be used within unit tests.
 * Hack classes inside the `Hack` folders: some predefined frequently used hacks.

## How to use

Let's go through an example.

First, create a file named `class-wc-admin-hello-worlder` in `includes/admin/class-wc-admin-hello-worlder.php` with the following code:

```
<?php

class WC_Admin_Hello_Worlder {

	public function say_hello( $to_who ) {
		Logger::log_hello( $to_who );
		$site_name = get_option( 'site_name' );
		return "Hello {$to_who}, welcome to {$site_name}!";
	}
}
```

This class has two bits that are difficult to unit test: the call to `Logger::log_hello` and the `get_option` invocation. Let's see how the code hacker can help us with that. 

Create a file named `class-wc-tests-admin-hello-worlder.php` in `tests/unit-tests/admin` with this code:

```
<?php

use Automattic\WooCommerce\Testing\CodeHacking\CodeHacker;
use Automattic\WooCommerce\Testing\CodeHacking\Hacks\StaticMockerHack;
use Automattic\WooCommerce\Testing\CodeHacking\Hacks\FunctionsMockerHack;

class LoggerMock {

	public static $_logged = null;

	public static function log_hello( $to_who ) {
		self::$_logged = $to_who;
	}
}

class FunctionsMock {

	public static $_option_requested = null;
	public static $_option_value     = null;

	public static function get_option( $option, $default = false ) {
		self::$_option_requested = $option;
		return self::$_option_value;
	}
}

class WC_Tests_Admin_Hello_Worlder extends WC_Unit_Test_Case {

	public static function before_test_say_hello() {
		CodeHacker::add_hack( new StaticMockerHack( 'Logger', 'LoggerMock' ) );
		CodeHacker::add_hack( new FunctionsMockerHack( 'FunctionsMock' ) );
		CodeHacker::enable();
	}

	public function test_say_hello() {
		FunctionsMock::$_option_value = 'MSX world';

		$sut    = new WC_Admin_Hello_Worlder();
		$actual = $sut->say_hello( 'Nestor' );

		$this->assertEquals( 'Hello Nestor, welcome to MSX world!', $actual );
		$this->assertEquals( 'site_name', FunctionsMock::$_option_requested );
		$this->assertEquals( 'Nestor', LoggerMock::$_logged );
	}
}
```

Then run `vendor/bin/phpunit tests/unit-tests/admin/class-wc-tests-admin-hello-worlder.php` and see the magic happen. Note that this works because `CodeHackerTestHook` has been registered in `phpunit.xml`.

As you can see, the basic mechanism consists of creating a `public static before_[test_method_name]` method in the test class, where you register whatever hacks you need with `CodeHacker::add_hack` and finally you invoke `CodeHacker::enable()` to enable the hacking. `StaticMockerHack` and `FunctionsMockerHack` are two of the predefined hack classes inside the `Hack` folder, see their source code for details on how they work and how to use them. 

You can define a `before_all` method too, which will run before all the tests (and before the `before_[test_method_name]` method).

You might be asking why special `before_` are required if we already have PHPUnit's `setUp` method. The answer is: by the time `setUp` runs the code files to test are already loaded, so there's no way to hack them.

Alternatively, hacks can be defined using class and method annotations as well (with the added bonus that you don't need the `use` statements anymore):

```
/**
 * @hack StaticMocker Logger LoggerMock
 */
class WC_Tests_Admin_Hello_Worlder extends WC_Unit_Test_Case {

	/**
	 * @hack FunctionsMocker FunctionsMock
	 * @hack BypassFinals
	 */
	public function test_say_hello() {
            ...
	}
}
```

The syntax is `@hack HackClassName [hack class constructor parameters]`. Important bits:

* Specify constructor parameters after the class name. If a parameter has a space, enclose it in quotation marks, `""`. 
* If the hack class is inside the `Automattic\WooCommerce\Testing\CodeHacking\Hacks` namespace you don't need to specify the namespace.
* If the hack class name has the `Hack` suffix you can omit the suffix (e.g. `@hack FunctionsMocker` for the `FunctionsMockerHack` class).
* If the annotation is applied to the test class definition the hack will be applied to all the tests within the class. 
* You don't need to `CodeHacker::enable()` when using `@hack`, this will be done for you.

## Creating new hacks

New hacks can be created and used the same way as the predefined ones. A hack is defined as one of these:

* A function with the signature `hack($code, $path)`.
* An object containing a `public function hack($code, $path)`.

The `hack` function/method receives a string with the entire contents of the code file in `$code`, and the full path of the code file in `$path`. It must return a string with the hacked file contents (or, if no hacking is required, the unmodified value of `$code`).

There's a `CodeHack` abstract class inside the `Hacks` directory that can be useful to develop new hacks, but that's provided just for convenience and it's not mandatory to use it (any class with a proper `hack` method will work).
 
## How it works under the hood

The Code Hacker is based on [the Bypass Finals project](https://github.com/dg/bypass-finals) by David Grudl.

The `CodeHacker` class is actually [a streamWrapper class](https://www.php.net/manual/en/class.streamwrapper.php) for the regular filesystem. Most of its methods are just short-circuited to the regular PHP filesystem handling functions, but the `stream_open` method contains some code that allows the magic to happen. What it does (for PHP files only) is to read the file contents and apply all the hacks to it, then if the code has been modified it is stored in a temporary file which is then the one that receives any further filesystem operations instead of the original file. That way, for all practical purposes the content of the file is the "hacked" content.   

The `CodeHackerTestHook` then uses reflection to find `before_` methods and `@hack` anotations, putting everything in place right before the tests are executed.

## Workaround for static mocking of already loaded code: the `StaticWrapper`

Before the test hooks that configure the code hacker run there's already a significant amount of code files already loaded, as a result of WooCommerce being loaded and initialized within the unit tests bootstrap files. These code file can't be hacked using the described approach, and therefore require to _hack the hack_.

A workaround for a particular case is provided. If you need to register a `StaticMockerHack` for a class that has been already loaded (you will notice because registering the hack the usual way does nothing), do the following instead:

1. Add the class name (NOT the file name) to the array returned by `tests/classes-that-need-static-wrapper.php`.

2. Configure the mock using `StaticWrapper::set_mock_class_for`.

```
class WC_Tests_Admin_Hello_Worlder extends WC_Unit_Test_Case {

	public function test_say_hello() {
		FunctionsMock::$_option_value = 'MSX world';

		StaticWrapper::set_mock_class_for('Logger', 'LoggerMock');

		$sut    = new WC_Admin_Hello_Worlder();
		$actual = $sut->say_hello( 'Nestor' );

		$this->assertEquals( 'Hello Nestor, welcome to MSX world!', $actual );
		$this->assertEquals( 'site_name', FunctionsMock::$_option_requested );
		$this->assertEquals( 'Nestor', LoggerMock::$_logged );
	}
}
```

Note that in this case you don't explicitly interact with the code hacker, neither directly nor by using `@hack` annotations.

Under the hood this is hacking all the classes in the list with a "clean" `StaticWrapper`-derived class; here "clean" means that all static methods are redirected to the original class via `__callStatic`. This hacking happens at the beginning of the bootstrapping process, when nothing from WooCommerce has been loaded yet. Later on `StaticWrapper::set_mock_class_for` can be used at any time to selectively mock any static method in the class.

Alternatively, you can configure mock functions instead of a mock class. See the source of `StaticWrapper` for more details.

## Note on `copy` in tests

For some reason tests using `copy` to copy files will fail if the code hacker is active. As a workaround, the new `file_copy` method defined in `WC_Unit_Test_Case` should be used instead (it temporarily disables the code hacker and then performs the copy operation). This is something to investigate.

## An important note

The code hacker is intended to be a **last resort** mechanism to test stuff that it's **really** difficult or impossible to test otherwise - the mechanisms already in place to help testing (e.g. the PHPUnit's mocks or the Woo helpers) should still be used whenever possible. And of course, the code hacker should not be an excuse to write code that's difficult to test.
