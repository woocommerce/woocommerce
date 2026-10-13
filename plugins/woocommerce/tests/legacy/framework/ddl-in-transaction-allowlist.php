<?php
/**
 * Tests that still run schema-changing SQL inside their transaction and should not.
 *
 * Each entry is 'Class::method', or 'Class::*' when the DDL runs in setUp(), with
 * the fix. This list may only shrink: fix the test instead of adding to it. Tests
 * whose subject is schema code declare it with @ddlInTransaction instead.
 * See WC_DDL_In_Transaction_Guard for what the guard catches and why.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

return array(
	// Creates the HPOS tables per test through setup_cot(). Move to wpSetUpBeforeClass() + setup_cot_tables(), as #68930 did.
	'WC_Tests_API_Reports_Sales::test_get_sales_report_with_hpos_enabled_and_sync_off',
	'Automattic\WooCommerce\Tests\Internal\Abilities\AbilitiesLoaderTest::test_orders_query_returns_not_found_for_unknown_id',
	'Automattic\WooCommerce\Tests\Internal\Abilities\AbilitiesLoaderTest::test_orders_query_excludes_checkout_draft_orders_by_default',
	'Automattic\WooCommerce\Tests\Internal\Abilities\AbilitiesLoaderTest::test_orders_query_sorts_by_id_across_order_storage_engines',
	'Automattic\WooCommerce\Tests\Internal\Abilities\AbilitiesLoaderTest::test_orders_query_sorts_by_modified_date_across_order_storage_engines',
	'Automattic\WooCommerce\Tests\Internal\Abilities\AbilitiesLoaderTest::test_orders_query_filters_modified_before_with_time_precision',

	// Creates the HPOS tables in setUp() through setup_cot(). Move to wpSetUpBeforeClass() + setup_cot_tables(), as #68930 did.
	'Automattic\WooCommerce\Tests\Internal\Admin\Orders\EditLockTest::*',
	'Automattic\WooCommerce\Tests\Internal\Customers\SearchServiceTest::*',
	'Automattic\WooCommerce\Tests\Internal\DataStores\Orders\LegacyDataCleanupTests::*',
	'Automattic\WooCommerce\Tests\Internal\DataStores\Orders\OrdersTableDataStoreCacheCrossBleedTest::*',
	'Automattic\WooCommerce\Tests\Internal\DataStores\Orders\OrdersTableDataStoreCachePrimingTest::*',

	// setUp() recreates the HPOS tables whenever an earlier test dropped them, so which test trips depends on run order. Create them once before the class instead.
	'Automattic\WooCommerce\Tests\Internal\DataStores\Orders\DataSynchronizerTests::*',

	// Enabling the fulfillments feature runs its schema changes (an ALTER) lazily on init. Enable it once before the class instead.
	'Automattic\WooCommerce\Tests\Admin\API\Reports\Orders\Stats\DataStoreFulfillmentsTest::test_regenerate_order_fulfillment_status_updates_orders_with_fulfillments',
);
