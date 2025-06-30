# WooCommerce `src` files

## Table of contents

* [A note on @internal annotations](#a-note-on-internal-annotations)
* [Installing Composer](#installing-composer)
    * [Updating the autoloader class maps](#updating-the-autoloader-class-maps)
* [Installing packages](#installing-packages)
* [The container](#the-container)
    * [Retrieving classes](#retrieving-classes)
        * [From other classes in the `src` directory](#1-other-classes-in-the-src-directory)
        * [From code in the `includes` directory](#2-code-in-the-includes-directory)
        * [A note on legacy classes](#a-note-on-legacy-classes)
    * [Historical note](#historical-note)
* [The `Internal` namespace](#the-internal-namespace)
* [Interacting with legacy code](#interacting-with-legacy-code)
    * [The `LegacyProxy` class](#the-legacyproxy-class)
    * [Using the legacy proxy](#using-the-legacy-proxy)
    * [Using the mockable proxy in tests](#using-the-mockable-proxy-in-tests)
    * [But how does `get_instance_of` work?](#but-how-does-get_instance_of-work)
    * [Creating specialized proxies](#creating-specialized-proxies)
* [Defining new actions and filters](#defining-new-actions-and-filters)
* [Writing unit tests](#writing-unit-tests)
    * [Mocking dependencies](#mocking-dependencies)
    * [Additional tools for writing unit tests](#additional-tools-for-writing-unit-tests)

This directory is home to new WooCommerce class files under the `Automattic\WooCommerce` namespace using [PSR-4](https://www.php-fig.org/psr/psr-4/) file naming. This is to take full advantage of autoloading.

Ideally, all the new code for WooCommerce should consist of classes following the PSR-4 naming and living in this directory, and the code in [the `includes` directory](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/includes/README.md) should receive the minimum amount of changes required for bug fixing. This will not always be possible but that should be the rule of thumb.

A [PSR-11](https://www.php-fig.org/psr/psr-11/) container is in place for registering and resolving the classes in this directory by using the [dependency injection](https://en.wikipedia.org/wiki/Dependency_injection) pattern. There are tools in place to interact with legacy code (and code outside the `src` directory in general) in a way that makes it easy to write unit tests.

## A note on `@internal` annotations

Some classes and methods in this folder have an `@internal` annotation in their documentation comment block. This means that the code entity is intended for internal usage of WooCommerce core only, and **must not** be used in extensions. Backwards compatibility for these code entities is not guaranteed: they could be renamed, modified (in behavior, return value or arguments accepted) or deleted.

See also [the README file for the `Internal` folder](Internal/README.md).

## Installing Composer

Composer is used to generate autoload class-maps for the files here. The stable release of WooCommerce comes with the autoloader, however, if you're running a development version you'll need to use Composer.

If you don't have Composer installed, go and check how to [install Composer](https://github.com/woocommerce/woocommerce/wiki/How-to-set-up-WooCommerce-development-environment) and then continue here.

### Updating the autoloader class maps

If you add a class to WooCommerce you need to run the following to ensure it's included in the autoloader class-maps:

```bash
composer dump-autoload
```

## Installing packages

To install the packages WooCommerce requires, from the main directory run:

```bash
composer install
```

To update packages run:

```bash
composer update
```

## The container

WooCommerce uses a [PSR-11](https://www.php-fig.org/psr/psr-11/) compatible container for retrieving instances of all the classes in this directory by using the [dependency injection](https://en.wikipedia.org/wiki/Dependency_injection) pattern. This container is very simple: explicit class registration is not needed, any class in the `Automattic\Woocommerce` namespace can be retrieved and reflection is used to locate and instantiate classes and their dependencies.

The container assumes that all the retrieved classes are single-instance classes: the class will be instantiated the first time it's requested, and further requests will serve a cached version of the created instance. Thus for data-only classes for which multiple instances may be needed, instances should be created directly with `new` instead.

The term "resolve a class" used in this document means instantiating and caching the class if needed, then returning the cached instance of the class.

See [Writing unit tests](#writing-unit-tests) for information on how to mock/replace class dependencies in unit tests.

### Retrieving classes

There are two ways to retrieve classes, depending on how they need to be resolved:

#### 1. Other classes in the `src` directory

When a class in the `src` directory depends on other one classes that are also in `src`, it should use method injection. This means specifying these dependencies as arguments in a `init` method with appropriate type hints, and storing these in private variables, ready to be used when needed:

```php
use TheService1Namespace\Service1;
use TheService2Namespace\Service2;

class TheClassWithDependencies {
    private $service1;

    private $service2;

    public function init( Service1Class $service1, Service2Class $service2 ) {
        $this->$service1 = $service1;
        $this->$service2 = $service2;
    }

    public function method_that_needs_service_1() {
        $this->service1->do_something();
    }
}
```

Whenever the container is about to resolve `TheClassWithDependencies` it will also resolve `Service1Class` and `Service2Class` and pass them as method arguments to the requested class. If these service classes have method arguments too then those will also be appropriately resolved recursively.

A "lazy" approach is also possible if needed: you can specify the container itself as a method argument (using `\Psr\Container\ContainerInterface` as type hint), and use its `get` method to obtain the required instance at the appropriate time:

```php
use TheService1Namespace\Service1;

class TheClassWithDependencies {
    private $container;

    public function init( \Psr\Container\ContainerInterface $container ) {
        $this->$container = $container;
    }

    public function method_that_needs_service_1() {
        $this->container->get( Service1::class )->do_something();
    }
}
```

In general, however, method injection is strongly preferred and the lazy approach should be used only when really necessary.

#### 2. Code in the `includes` directory

When you need to use classes defined in the `src` directory from within legacy code in `includes`, use the `wc_get_container` function to get the instance of the container, then retrieve the required class with `get`:

```php
use TheService1Namespace\Service1;

function wc_function_that_needs_service_1() {
    $service = wc_get_container()->get( Service1::class );
    $service->do_something();
}
```

This is also the recommended approach when moving code from `includes` to `src` while keeping the existing entry points for the old code in place for compatibility.

Worth noting: the container will throw a `ContainerException` when receiving a request for retrieving a class that doesn't exist or is not in the `Automattic\Woocommerce` namespace, or when there's an error preventing the instantiation (for example the class doesn't have a public constructor). You can use the container `has` method to verify that a given class can actually be retrieved, but this method will only verify that the class exists and is in the correct namespace.

#### A note on legacy classes

The container is intended for retrieving **only** classes in the `src` folder, or put another way, classes in the `Automattic\Woocommerce` namespace. An attempt to retrieve a class that is not in that namespace will throw an exception.

This implies that classes outside `src` can't be dependency-injected, and thus must not be used as type hints in `init` arguments. There are mechanisms in place to interact with "outside" code (including code from the `includes` folder and third-party code) in a way that makes it easy to write unit tests - see [The `LegacyProxy` class](#the-legacyproxy-class).

### Historical note

In previous versions WooCommerce used [the PHP League's Container package](https://container.thephpleague.com/) as the underlying dependency injection engine, requiring explicit registration for all the classes intended to be retrieved using it. The new simplified container [was introduced in WooCommerce 9.5](https://developer.woocommerce.com/2024/11/15/developer-advisory-changes-to-the-dependency-injection-container-in-woocommerce/), while the old container package and all the related infrastructure code was removed in WooCommerce 10.0.

## The `Internal` namespace

While it's up to the developer to choose the appropriate namespaces for any newly created classes, and those namespaces should make sense from a semantic point of view, there's one namespace that has a special meaning: `Automattic\WooCommerce\Internal`.

Classes in `Automattic\WooCommerce\Internal` are meant to be WooCommerce infrastructure code that might change in future releases. In other words, for code inside that namespace, **backwards compatibility  of the public surface is not guaranteed**: future releases might include breaking changes including renaming or renaming classes, renaming or removing public methods, or changing the signature of public methods. The code in this namespace is considered "internal", whereas all the other code in `src` is considered "public".

What this implies for you as developer depends on what type of contribution are you making:

* **If you are working on WooCommerce core:** When you need to add a new class please think carefully if the class could be useful for plugins. If you really think so, add it to the appropriate namespace rooted at `Automattic\WooCommerce`. If not, add it to the appropriate namespace but rooted at `Automattic\WooCommerce\Internal`.
    * When in doubt, always make the code internal. If an internal class is later deemed to be worth being made public, the change can be made easily (by just changing the class namespace) and nothing will break. Turning a public class into an internal class, on the other hand, is impossible since it could break existing plugins.

* **If you are a plugin developer:** You should **never** use code from the `Automattic\WooCommerce\Internal` namespace in your plugins. Doing so might cause your plugin to break in future versions of WooCommerce.

## Interacting with legacy code

Here by "legacy code" we refer mainly to the old WooCommerce code in the `includes` directory, but the mechanisms described in this section are useful for dealing with any code outside the `src` directory.

The code in the `src` directory can for sure interact directly with legacy code. A function needs to be called? Call it. You need an instance of an object? Instantiate it. The problem is that this makes the code difficult to test: it's not easy to mock functions (unless you use [hacks](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/Tools/CodeHacking/README.md), or objects that are instantiated directly with `new` or whose instance is retrieved via a `TheClass::instance()` method).

But we want the WooCommerce code base (and especially the code in `src`) to be well covered by unit tests, and so there are mechanisms in place to interact with legacy code while keeping the code testable.

### The `LegacyProxy` class

`LegacyProxy` is a class that contains three public methods intended to allow interaction with legacy code:

* `get_instance_of`: Retrieves an instance of a legacy (non-`src`) class.
* `call_function`: Calls a standalone function.
* `call_static`: Calls a static method in a class.
* `get_global`: Gets the value of a global variable by name.

Whenever a `src` class needs to get an instance of a legacy class, or call a function, or call a static method from another class, or use a global variable, and that would make the code difficult to test, it should use the `LegacyProxy` methods instead.

But how does using `LegacyProxy` help in making the code testable? The trick is that when tests run what is registered instead of `LegacyProxy` is an instance of `MockableLegacyProxy`, a class with the same public surface but with additional methods that allow to easily mock legacy classes, functions, static methods and global variables.

### Using the legacy proxy

`LegacyProxy` is a class that is registered in the container as any other class, so an instance can be obtained by using dependency-injection:

```php
use Automattic\WooCommerce\Proxies\LegacyProxy;

class TheClass {
    private $legacy_proxy;

    public function init( LegacyProxy $legacy_proxy ) {
        $this->legacy_proxy = $legacy_proxy;            
    }

    public function do_something_using_some_function() {
        $this->legacy_proxy->call_function( 'the_function_name', 'param1', 'param2' );
    }
}
```

However, the recommended way (especially when no other dependencies need to be dependency-injected) is to use the equivalent methods in the `WooCommerce` class via the `WC()` helper, like this:

```php
class TheClass {
    public function do_something_using_some_function() {
        WC()->call_function( 'the_function_name', 'param1', 'param2' );
    }
}
```

Both ways are completely equivalent since the helper methods are just doing `wc_get_container()->get( LegacyProxy::class )->...` under the hood.

### Using the mockable proxy in tests

When unit tests run the container will return an instance of `MockableLegacyProxy` when `LegacyProxy` is retrieved. This class has the same public methods as `LegacyProxy` but also the following ones:

* `register_class_mocks`: defines mocks for classes that are retrieved via `get_instance_of`.
* `register_function_mocks`: defines mocks for functions that are invoked via `call_function`.
* `register_static_mocks`: defines mocks for functions that are invoked via `call_static`.
* `register_global_mocks`: defines mocks for global variables that are retrieved via `get_global`.

These methods could be accessed via `wc_get_container()->get( LegacyProxy::class )->register...` directly from the tests, but the preferred way is to use the equivalent helper methods offered by the `WC_Unit_Test_Case` class,: `register_legacy_proxy_class_mocks`, `register_legacy_proxy_function_mocks`, `register_legacy_proxy_static_mocks` and `register_global_mock`.

Here's an example of how function mocks are defined:

```php
// In this context '$this' is a class that extends WC_Unit_Test_Case

$this->register_legacy_proxy_function_mocks(
 array(
  'the_function_name' => function( $param1, $param2 ) {
   return "I'm the mock of the_function_name and I was invoked with $param1 and $param2.";
  },
 )
);
```

Of course, for the cases where no mocks are defined `MockableLegacyProxy` works the same way as `LegacyProxy`.

Please see [the code of the MockableLegacyProxy class](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/Tools/DependencyManagement/MockableLegacyProxy.php) and [its unit tests](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/php/src/Proxies/MockableLegacyProxyTest.php) for more detailed usage instructions and examples.

### But how does `get_instance_of` work?

We use a container to retrieve instances of classes in the `src` directory, but how does the legacy proxy's `get_instance_of` know how to resolve legacy classes?

This is a mostly ad-hoc process. When a class has a special way to be instantiated or retrieved (e.g. a static `instance` method), then that is used; otherwise the method fallbacks to simply creating a new instance of the class using `new`.

This means that the `get_instance_of` method will most likely need to evolve over time to cover additional special cases. Take a look at the method code in [LegacyProxy](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Proxies/LegacyProxy.php) for details on how to properly make changes to the method.

### Creating specialized proxies

While helpful to make the code testable, using the legacy proxy can make the code somewhat more difficult to read or maintain, so it should be used judiciously and only when really needed to make the code properly testable.

That said, an alternative middle ground would be to create more specialized cases for frequently used pieces of legacy code, for example:

```php
class ActionsProxy {
 public function did_action( $tag ) {
  return did_action( $tag );
 }

 public function apply_filters( $tag, $value, ...$parameters ) {
  return apply_filters( $tag, $value, ...$parameters );
 }
}
```

Note however that such a class would have to be explicitly dependency-injected (unless additional helper methods are defined in the `WooCommerce` class), and that you would need to create a pairing mock class (e.g. `MockableActionsProxy`) and replace the original registration using `wc_get_container()->replace( ActionsProxy::class, MockableActionsProxy::class )`.

## Defining new actions and filters

WordPress' hooks (actions and filters) are a very powerful extensibility mechanism and it's the core tool that allows WooCommerce extensions to be developer. However it has been often (ab)used in the WooCommerce core codebase to drive internal logic, e.g. an action is triggered from within one class or function with the assumption that somewhere there's some other class or function that will handle it and continue whatever processing is supposed to happen.

In order to keep the code as easy as reasonably possible to read and maintain, **hooks shouldn't be used to drive WooCommerce's internal logic and processes**. If you need the services of a given class or function, please call these directly (by using dependency-injection or the legacy proxy as appropriate to get access to the desired service). **New hooks should be introduced only if they provide a valuable extension point for plugins**.

As usual, there might be reasonable exceptions to this; but please keep this rule in mind whenever you consider creating a new hook.

## Writing unit tests

Unit tests are a fundamental tool to keep the code reliable and reasonably safe from regression errors. To that end, any new code added to the WooCommerce codebase, but especially to the `src` directory, should be reasonably covered by such tests.

**If you are a WooCommerce core team member or a contributor from other team at Automattic:** Please write unit tests to cover any code addition or modification that you make to the `src` directory (and ideally the same for the `includes` directory, by the way). There are always reasonable exceptions, but the rule of thumb is that all code should be covered by tests.

**If you are an external contributor:** When adding or changing code on the WooCommerce codebase, and especially in the `src` directory, adding unit tests is recommended but not mandatory: no contributions will be rejected solely for lacking unit tests. However, please try to at least make the code easily testable by honoring the container and dependency-injection mechanism, and by using the legacy proxy to interact with legacy code when needed. If you do so, the WooCommerce team or other contributors will be able to add the missing tests.

### Mocking dependencies

Since all the dependencies for classes in this directory are dependency-injected or retrieved lazily by directly accessing the container, it's easy to mock them by either manually creating a mock class with the same public surface or by using [PHPUnit's test doubles](https://phpunit.readthedocs.io/en/9.5/test-doubles.html):

```php
$dependency_mock = somehow_create_mock();
$sut = new TheClassToTest( $dependency_mock ); //sut = System Under Test
$result = $sut->do_something();
$this->assertEquals( $result, 'the expected result' );
```

However, while this works well for simple scenarios, in the real world dependencies will often have other dependencies in turn, so instantiating all the required intermediate objects will be complex. To make things easier, while tests run the `Container` class is replaced with an `ExtendedContainer` class that has a couple of additional methods:

* `replace`: allows defining a new replacement concrete for a given class registration.
* `reset_all_resolved`: discards all the cached resolutions. You may need when mocking classes that have been defined as shared.

It's worth noting that at unit testing session bootstrap time `reset_all_resolved` is called once to reset any cached resolutions made during WC install, and `replace` is used to swap the `LegacyProxy` with a `MockableLegacyProxy`.

The same example using `replace`:

```php
$dependency_mock = somehow_create_mock();
$container = wc_get_container();
$container->reset_all_resolved(); //if either the SUT or the dependency are shared
$container->replace( TheDependencyClass::class, $dependency_mock );
$sut = $container->get( TheClassToTest::class );
$result = $sut->do_something();
$this->assertEquals( $result, 'the expected result' );
```

Note: of course all of this applies to dependencies from the `src` directory, for mocking legacy dependencies [the `MockableLegacyProxy`](#using-the-mockable-proxy-in-tests) should be used instead.

### Additional tools for writing unit tests

[The `tests/Tools` directory](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/Tools) contains additional tools that can help in the task of writing unit tests, notably:

* [The code hacker](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/tests/Tools/CodeHacking#readme), which allows modifying the code before it's tested in order to mock functions, static methods and `final` classes. This is a last resort mechanism when using other mechanisms like [the `LegacyProxy` class](#the-legacyproxy-class) is not an option.
* [The DynamicDecorator class](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/tests/Tools/DynamicDecorator.php), which wraps an arbitrary object and allows to define replacements its for methods and properties; the decorator is then used in lieu of the original object. This can be useful when extending the class of the original object isn't possible or is too complicated. See [the unis tests](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/tests/php/src/Proxies/DynamicDecoratorTest.php) for examples of how it's used.

