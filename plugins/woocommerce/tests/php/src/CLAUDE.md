# PHP Testing - Claude Code Documentation

**Scope**: PHPUnit test patterns for WooCommerce plugin tests
**Parent**: `plugins/woocommerce/CLAUDE.md`

## Quick Reference: Resilient Test Patterns

### Rule: Use targeted assertions, not full equality checks

| Pattern | Brittle (Wrong) | Resilient (Correct) |
|---------|-----------------|---------------------|
| Array key + value | `assertSame(['key' => null], $arr)` | `assertArrayHasKey('key', $arr)` + `assertNull($arr['key'])` |
| Single value | `assertSame(['a' => 1, 'b' => 2], $r)` | `assertArrayHasKey('a', $r)` + `assertSame(1, $r['a'])` |
| Nested | `assertSame(['m' => ['e' => null]], $r)` | `assertArrayHasKey('e', $r['m'])` + `assertNull($r['m']['e'])` |

Why: Full equality breaks when new keys are added.

**Example**: `WooPaymentsServiceTest.php:510-511`

```php
// WRONG - Breaks if new keys added to messages array
$this->assertSame( array( 'not_supported' => null ), $result['messages'] );

// CORRECT - Tests only what matters
$this->assertArrayHasKey( 'not_supported', $result['messages'] );
$this->assertNull( $result['messages']['not_supported'] );
```

## PHPUnit Assertions

**Priority order**: Most specific → Structure → General

| Assertion | Use | Example |
|-----------|-----|---------|
| `assertSame()` | Strict === | `assertSame(5, $count)` |
| `assertEquals()` | Loose == | `assertEquals('5', $count)` |
| `assertNull()` | Null check | `assertNull($error)` |
| `assertTrue()` | Boolean | `assertTrue($flag)` |
| `assertArrayHasKey()` | Key exists | `assertArrayHasKey('id', $data)` |
| `assertIsArray()` | Type check | `assertIsArray($result)` |
| `assertCount()` | Array size | `assertCount(3, $items)` |

## Mock External Classes

**Pattern for external classes (WooPayments, etc.):**

```php
if ( ! class_exists( 'WC_Payments_Utils' ) ) {
    /**
     * Mock for testing.
     *
     * phpcs:disable Squiz.Classes.ClassFileName.NoMatch
     * phpcs:disable Suin.Classes.PSR4.IncorrectClassName
     * phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
     */
    class WC_Payments_Utils {
        public static function supported_countries(): array {
            return array( 'US', 'GB' );
        }
    }
    // phpcs:enable
}
```

**Why ignores needed:**

- `ClassFileName.NoMatch` - Mock doesn't match file name
- `PSR4.IncorrectClassName` - External class not PSR-4
- `ValidClassName.NotCamelCaps` - Uses underscores

## Unused Closure Parameters

**PHPCS requires**: Use `unset()` for required but unused parameters

```php
// WRONG - PHPCS error
'callback' => function ( string $url ) {
    return array( 'success' => true );
},

// CORRECT
'callback' => function ( string $url ) {
    unset( $url ); // Avoid parameter not used PHPCS errors.
    return array( 'success' => true );
},

// Multiple unused
'callback' => function ( $a, $b, $c ) {
    unset( $a, $b ); // Avoid parameter not used PHPCS errors.
    return process( $c );
},
```

**Scenarios**: Mock callbacks, array_map/filter, interface implementations

## Test Structure

**Arrange-Act-Assert pattern:**

```php
public function test_feature_name(): void {
    // Arrange - Set up test data and mocks.
    $mock = $this->createMock( SomeClass::class );
    $mock->method( 'get_data' )->willReturn( 'value' );

    // Act - Execute the code being tested.
    $result = $this->service->process( $mock );

    // Assert - Verify expected behavior.
    $this->assertIsArray( $result );
    $this->assertArrayHasKey( 'status', $result );
    $this->assertSame( 'success', $result['status'] );
}
```

## Data Providers

**For testing multiple scenarios:**

```php
/**
 * @return array<string, array<mixed>>
 */
public function provider_scenarios(): array {
    return array(
        'US merchant'   => array( 'US', 'expected' ),
        'UK merchant'   => array( 'GB', 'expected' ),
        'unsupported'   => array( 'XX', null ),
    );
}

/**
 * @dataProvider provider_scenarios
 */
public function test_behavior( string $country, $expected ): void {
    $result = $this->service->get_data( $country );
    $this->assertSame( $expected, $result );
}
```

## Setup and Teardown

```php
public function setUp(): void {
    parent::setUp();
    $this->admin_id = $this->factory->user->create(
        array( 'role' => 'administrator' )
    );
    $this->service = new ServiceClass();
}

public function tearDown(): void {
    wp_delete_user( $this->admin_id );
    unset( $GLOBALS['some_global'] );
    parent::tearDown();
}
```

## Integration Tests

**REST API endpoints:**

```php
public function test_endpoint_returns_data(): void {
    wp_set_current_user( $this->admin_id );
    $request  = new \WP_REST_Request( 'GET', '/wc/v3/settings/payments' );
    $response = rest_do_request( $request );

    $this->assertSame( 200, $response->get_status() );
    $this->assertIsArray( $response->get_data() );
}
```

**WordPress hooks:**

```php
public function test_hook_fires(): void {
    $fired = false;
    add_filter( 'woocommerce_payment_gateways',
        function ( $gateways ) use ( &$fired ) {
            $fired = true;
            return $gateways;
        }
    );

    $result = $this->service->get_gateways();

    $this->assertTrue( $fired );
}
```

## Testing Private Methods

**Use sparingly - prefer testing public interfaces:**

```php
public function test_private_method(): void {
    $reflection = new \ReflectionClass( $this->service );
    $method     = $reflection->getMethod( 'private_method' );
    $method->setAccessible( true );

    $result = $method->invoke( $this->service, 'arg' );

    $this->assertSame( 'expected', $result );
}
```

## Running Tests

```bash
# Class
pnpm test:php:env -- --filter WooPaymentsServiceTest

# Method
pnpm test:php:env -- --filter ClassName::test_method_name

# Pattern
pnpm test:php:env -- --filter "test_.*_not_supported"

# Verbose (use --testdox for readable output)
pnpm test:php:env -- --testdox --filter WooPaymentsServiceTest
```

## Debugging Failures

**Common issues:**

| Error | Cause | Fix |
|-------|-------|-----|
| `Undefined array key` | Missing key | Check code returns key |
| `Arrays not identical` | Extra/missing keys | Use targeted assertions |
| `Mock not called` | Code path skipped | Check test setup |
| `Unexpected call` | Mock too strict | Use `$this->any()` |

**Read diff output:**

```text
Failed asserting that two arrays are identical.
--- Expected
+++ Actual
@@ @@
 Array (
-    'key' => 'expected'
+    'key' => 'actual'
 )
```

## Critical Rules

1. **One purpose per test** - Multiple assertions OK if testing same behavior
2. **Test behavior, not implementation** - Avoid testing internals
3. **Resilient assertions** - Won't break when adjacent code changes
4. **Mock externals** - No real API calls or external plugins
5. **Clean up** - Use tearDown() to reset state

## File Organization

```text
tests/php/src/
├── Internal/
│   └── Admin/
│       ├── Settings/
│       │   ├── PaymentsProviders/WooPayments/
│       │   │   ├── WooPaymentsServiceTest.php
│       │   │   └── WooPaymentsRestControllerIntegrationTest.php
│       │   └── PaymentsRestControllerIntegrationTest.php
│       └── Suggestions/
│           └── PaymentsExtensionSuggestionsTest.php
└── CLAUDE.md
```

**Naming:**

- Unit tests: `{ClassName}Test.php`
- Integration: `{ClassName}IntegrationTest.php`
- Methods: `test_{feature}_{scenario}()` or
  `test_{feature}_{scenario}_{outcome}()`

## Related Docs

- `plugins/woocommerce/CLAUDE.md` - Test commands, linting, workflow
- `src/Internal/Admin/Settings/CLAUDE.md` - Settings backend patterns
- PHPUnit: <https://phpunit.de/manual/9.6/en/index.html>
