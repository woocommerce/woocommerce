<?php
/**
 * Base test case for engine integration tests.
 *
 * Schema is installed once in the bootstrap; WP_UnitTestCase wraps each test in
 * a transaction and rolls it back, so test rows do not leak between tests.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

/**
 * Engine integration test case.
 */
abstract class EngineIntegrationTestCase extends WP_UnitTestCase {
}
