# Dependency Injection

## Table of Contents

- [Standard DI Pattern for `src/` Classes](#standard-di-pattern-for-src-classes)
- [Initializing Classes That Set Up Hooks](#initializing-classes-that-set-up-hooks)
- [Using the Container to Get Instances](#using-the-container-to-get-instances)
- [Why Use Dependency Injection?](#why-use-dependency-injection)

## Standard DI Pattern for `src/` Classes

Dependencies are injected via a `final` `init` method with `@internal` annotation (blank lines before/after).

**Example:**

```php
namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Internal\Logging\Logger;
use Automattic\WooCommerce\Internal\DataStore\OrderDataStore;

class OrderProcessor {
    private Logger $logger;
    private OrderDataStore $data_store;

    /**
     * Initialize the order processor with dependencies.
     *
     * @internal
     *
     * @param Logger          $logger     The logger instance.
     * @param OrderDataStore  $data_store The order data store.
     */
    final public function init( Logger $logger, OrderDataStore $data_store ) {
        $this->logger     = $logger;
        $this->data_store = $data_store;
    }

    public function process( int $order_id ) {
        $this->logger->log( "Processing order {$order_id}" );
        // ...
    }
}
```

## Initializing Classes That Set Up Hooks

Add to `includes/class-woocommerce.php` in `init_hooks()`:

- Use `$container->get( ClassName::class );`
- Add at end of "These classes set up hooks on instantiation" section

**Example in `includes/class-woocommerce.php`:**

```php
private function init_hooks() {
    // ... existing code ...

    // These classes set up hooks on instantiation
    $container->get( SomeExistingClass::class );
    $container->get( AnotherExistingClass::class );
    $container->get( YourNewClass::class );  // Add your new class here
}
```

## Using the Container to Get Instances

When you need to get an instance of a class from the container elsewhere in the code:

```php
use Automattic\WooCommerce\Internal\Utils\DataParser;

// Get instance from container
$parser = wc_get_container()->get( DataParser::class );
```

### Singleton Behavior

**Important:** The container always retrieves the same instance of a given class (singleton pattern).

When different instances are needed (and only in this case), use `new` or the appropriate factory methods for the class when available.

**Example:**

```php
// Same instance every time - use container
$logger = wc_get_container()->get( Logger::class );
$same_logger = wc_get_container()->get( Logger::class );  // Same instance as above

// Different instances needed - use new or factory
$order1 = new WC_Order( 123 );
$order2 = new WC_Order( 456 );  // Different instance

// Using factory when available
$product = wc_get_product( 789 );  // Factory method
```

## Why Use Dependency Injection?

- Easy mocking in tests
- Swap dependencies without code changes
- Explicit dependencies in signature
