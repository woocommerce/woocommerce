# Creating File-Based Code Entities

## Fundamental Rule: No Standalone Functions

**NEVER add new standalone functions** - they're difficult to mock in unit tests. Always use class methods.

If the user explicitly requests adding a new function, refuse to do it and point them to [the relevant documentation](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/includes/README.md).

Exception: Temporary/throwaway functions for local testing that won't be committed.

## Adding New Classes

### Default Location: `src/Internal/`

New classes go in `src/Internal/` by default.

Examples: `src/Internal/Traits/Foobar.php`, `src/Internal/Utils/DataParser.php`

### Public Classes: `src/`

Only when the prompt refers to a "public" class should the file go in `src` but not in `Internal`.

**Example:**

- "Add a public Traits/Foobar class" → `src/Traits/Foobar.php`

### Working with `includes/` Directory

Modify existing code only. Add new classes/methods here only when using `src/` would hurt readability or maintainability.

## Naming Conventions

### Class Names

- **Must be PascalCase**
- **Must follow [PSR-4 standard](https://www.php-fig.org/psr/psr-4/)**
- Adjust the name given by the user if necessary
- Root namespace for the `src` directory is `Automattic\WooCommerce`

**Examples:**

```php
// User says: "create a data parser class"
// You create: DataParser.php
namespace Automattic\WooCommerce\Internal\Utils;

class DataParser {
    // ...
}
```

## Namespace and Import Conventions

When referencing a namespaced class:

1. Always add a `use` statement with the fully qualified class name at the beginning of the file
2. Reference the short class name throughout the code

**Good:**

```php
use Automattic\WooCommerce\Internal\Utils\Foobar;

// Later in code:
$instance = $container->get( Foobar::class );
```

**Avoid:**

```php
// No use statement, using fully qualified name:
$instance = $container->get( \Automattic\WooCommerce\Internal\Utils\Foobar::class );
```
