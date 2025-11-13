# Unit Testing Conventions

## Table of Contents

- [Test File Naming and Location](#test-file-naming-and-location)
- [System Under Test Variable](#system-under-test-variable)
- [Test Method Documentation](#test-method-documentation)
- [Comments in Tests](#comments-in-tests)
- [Test Configuration](#test-configuration)
- [Example: Payment Extension Suggestions Tests](#example-payment-extension-suggestions-tests)
- [General Testing Best Practices](#general-testing-best-practices)

## Test File Naming and Location

| Source | Test | Pattern |
|--------|------|---------|
| `includes/` classes | `tests/php/includes/{path}/class-wc-{name}-test.php` | Add `-test` suffix |
| `src/` classes | `tests/php/src/{path}/{name}Test.php` | Append `Test` (no hyphen) |

Test class: Same name as source class + `_Test` or `Test` suffix, extends `WC_Unit_Test_Case`

## System Under Test Variable

Use `$sut` with docblock "The System Under Test."

```php
/**
 * The System Under Test.
 *
 * @var OrderProcessor
 */
private $sut;
```

## Test Method Documentation

When adding or modifying a unit test method, the part of the docblock that describes the test must be prepended with `@testdox`. End the comment with `.` for compliance with linting rules.

**Example:**

```php
/**
 * @testdox Should return true when order is valid.
 */
public function test_returns_true_for_valid_order() {
    // ...
}

/**
 * @testdox Should throw exception when order ID is negative.
 */
public function test_throws_exception_for_negative_order_id() {
    // ...
}
```

## Comments in Tests

**Avoid over-commenting tests.** Test names and assertion messages should explain intent.

**Good - Self-explanatory:**

```php
/**
 * @testdox Should return true when order status is draft.
 */
public function test_returns_true_for_draft_orders() {
    $order = $this->create_draft_order();

    $result = $this->sut->can_delete( $order );

    $this->assertTrue( $result, 'Draft orders should be deletable' );
}
```

**Avoid - Over-commented:**

```php
/**
 * @testdox Should return true when order status is draft.
 */
public function test_returns_true_for_draft_orders() {
    // Create a draft order
    $order = $this->create_draft_order();

    // Call the method we're testing
    $result = $this->sut->can_delete( $order );

    // Verify the result is true
    $this->assertTrue( $result, 'Draft orders should be deletable' );
}
```

**Avoid - Arrange/Act/Assert comments:**

```php
// Don't add these structural comments
// Arrange
$order = $this->create_draft_order();

// Act
$result = $this->sut->can_delete( $order );

// Assert
$this->assertTrue( $result );
```

Use blank lines for visual separation instead. The test structure should be self-evident.

**When comments ARE useful in tests:**

- Explaining complex test setup: `// Simulate race condition by...`
- Documenting known issues: `// Workaround for WordPress core bug #12345`
- Clarifying business rules: `// Payment processor requires 24h hold`

## Test Configuration

Test configuration file: `phpunit.xml`

## Example: Payment Extension Suggestions Tests

The `PaymentsExtensionSuggestionsTest` class demonstrates good testing practices for country-specific functionality.

### Key Patterns Used

1. **Data-driven tests** using PHPUnit data providers
2. **Extension count verification** for different merchant types
3. **Clear test organization** by merchant type (online/offline)

### Example Test Structure

```php
class PaymentsExtensionSuggestionsTest extends WC_Unit_Test_Case {
    /**
     * The System Under Test.
     *
     * @var PaymentsExtensionSuggestions
     */
    private $sut;

    public function setUp(): void {
        parent::setUp();
        $this->sut = new PaymentsExtensionSuggestions();
    }

    /**
     * @testdox Should return correct extension count for online merchants by country
     * @dataProvider online_merchant_country_data
     */
    public function test_get_country_extensions_count_for_online_merchants(
        string $country_code,
        int $expected_count
    ) {
        $merchant = array(
            'country'       => $country_code,
            'selling_venues' => 'online',
        );

        $result = $this->sut->get_country_extensions_count( $merchant );

        $this->assertSame(
            $expected_count,
            $result,
            "Expected {$expected_count} extensions for online merchant in {$country_code}"
        );
    }

    /**
     * Data provider for online merchant tests.
     *
     * @return array
     */
    public function online_merchant_country_data() {
        return array(
            'United States'    => array( 'US', 5 ),
            'United Kingdom'   => array( 'GB', 4 ),
            'Canada'           => array( 'CA', 3 ),
            'Australia'        => array( 'AU', 3 ),
            // ... more countries
        );
    }

    /**
     * @testdox Should return correct extension count for offline merchants by country
     * @dataProvider offline_merchant_country_data
     */
    public function test_get_country_extensions_count_for_offline_merchants(
        string $country_code,
        int $expected_count
    ) {
        $merchant = array(
            'country'        => $country_code,
            'selling_venues' => 'offline',
        );

        $result = $this->sut->get_country_extensions_count( $merchant );

        $this->assertSame(
            $expected_count,
            $result,
            "Expected {$expected_count} extensions for offline merchant in {$country_code}"
        );
    }

    /**
     * Data provider for offline merchant tests.
     *
     * @return array
     */
    public function offline_merchant_country_data() {
        return array(
            'United States'  => array( 'US', 2 ),
            'United Kingdom' => array( 'GB', 1 ),
            'Canada'         => array( 'CA', 1 ),
            // ... more countries
        );
    }
}
```

### Important Notes for Payment Extension Tests

When working with payment extension suggestions:

1. **Extension counts must match the implementation** in `src/Internal/Admin/Suggestions/PaymentsExtensionSuggestions.php`
2. **When adding new countries** to the implementation, update both data providers in the test file
3. **Tests are separated by merchant type** (online vs offline) as they have different extension counts
4. **Data providers use descriptive keys** (country names) for better test output

## General Testing Best Practices

1. **Always run tests after making changes** to verify functionality
2. **Use specific test filters** during development (see running-tests.md in the woocommerce-dev-cycle skill)
3. **Write descriptive test names** that explain what is being tested
4. **Use data providers** for testing multiple scenarios with the same logic
5. **Include helpful assertion messages** for debugging when tests fail
6. **Test both success and failure cases**
7. **Mock external dependencies** (database, API calls, etc.)
