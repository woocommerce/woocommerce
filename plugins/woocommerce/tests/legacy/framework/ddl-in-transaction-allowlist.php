<?php
/**
 * Tests allowed to run schema-changing SQL inside their transaction.
 *
 * Each entry is 'Class::method', or 'Class::*' for a whole class, with the
 * reason. This list may only shrink: fix the test instead of adding to it.
 * See WC_DDL_In_Transaction_Guard for what the guard catches and why.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

return array(
	// Tests WC_Log_Handler_DB::flush(), which truncates the log table. Restore the table contents it depends on explicitly.
	'WC_Tests_Log_Handler_DB::test_flush',

	// Creates the HPOS tables per test through setup_cot(). Move to wpSetUpBeforeClass() + setup_cot_tables(), as #68930 did for WC_Tests_API_Reports_Sales.
	'WC_Tests_API_Reports_Sales::test_get_sales_report_with_hpos_enabled_and_sync_off',
	'WC_Tests_API_Reports_Sales::tearDown',

	// The subject under test is schema code. The DDL cannot move; the test must save and restore every option it touches.
	'WC_Tests_Install::test_uninstall_removes_experimental_user_meta_but_preserves_other_meta',

	// The subject under test is schema code. The DDL cannot move; the test must save and restore every option it touches.
	'WC_Admin_Tests_Install::test_create_tables',

	// The subject under test is schema code. The DDL cannot move; the test must save and restore every option it touches.
	'WC_Install_Test::test_verify_base_tables_stores_and_removes_missing_tables',
	'WC_Install_Test::test_verify_base_tables_fix_tables',
	'WC_Install_Test::test_create_tables_rekeys_the_order_tax_lookup_by_tax_order_item',
	'WC_Install_Test::test_create_tables_logs_a_failed_order_tax_lookup_rekey',

	// The subject under test is schema code. The DDL cannot move; the test must save and restore every option it touches.
	'WC_Update_Functions_Test::test_verify_wc_update_343_cleanup_foreign_keys_removes_foreign_keys',
	'WC_Update_Functions_Test::test_verify_wc_update_352_drop_download_log_fk_removes_foreign_keys',
	'WC_Update_Functions_Test::test_verify_wc_update_700_remove_download_log_fk_removes_foreign_keys',

	// Enabling the fulfillments feature creates its tables lazily on init. Create them once before the class instead.
	'Automattic\WooCommerce\Tests\Admin\API\Reports\Orders\Stats\DataStoreFulfillmentsTest::test_regenerate_order_fulfillment_status_updates_orders_with_fulfillments',

	// Creates the HPOS tables per test through setup_cot(). Move to wpSetUpBeforeClass() + setup_cot_tables(), as #68930 did for WC_Tests_API_Reports_Sales.
	'Automattic\WooCommerce\Tests\Internal\Abilities\AbilitiesLoaderTest::test_orders_query_returns_not_found_for_unknown_id',
	'Automattic\WooCommerce\Tests\Internal\Abilities\AbilitiesLoaderTest::test_orders_query_excludes_checkout_draft_orders_by_default',
	'Automattic\WooCommerce\Tests\Internal\Abilities\AbilitiesLoaderTest::test_orders_query_sorts_by_id_across_order_storage_engines',
	'Automattic\WooCommerce\Tests\Internal\Abilities\AbilitiesLoaderTest::test_orders_query_sorts_by_modified_date_across_order_storage_engines',
	'Automattic\WooCommerce\Tests\Internal\Abilities\AbilitiesLoaderTest::test_orders_query_filters_modified_before_with_time_precision',

	// The subject under test is schema code. The DDL cannot move; the test must save and restore every option it touches.
	'Automattic\WooCommerce\Tests\Internal\Admin\OrderTaxLookupMigratorTest::test_rebuild_waits_until_the_lookup_is_keyed_by_order_item',

	// Creates the HPOS tables per test through setup_cot(). Move to wpSetUpBeforeClass() + setup_cot_tables(), as #68930 did for WC_Tests_API_Reports_Sales.
	'Automattic\WooCommerce\Tests\Internal\Admin\Orders\EditLockTest::setup_cot',

	// Creates the HPOS tables per test through setup_cot(). Move to wpSetUpBeforeClass() + setup_cot_tables(), as #68930 did for WC_Tests_API_Reports_Sales.
	'Automattic\WooCommerce\Tests\Internal\Customers\SearchServiceTest::setup_cot',

	// Creates the HPOS tables per test through setup_cot(). Move to wpSetUpBeforeClass() + setup_cot_tables(), as #68930 did for WC_Tests_API_Reports_Sales.
	'Automattic\WooCommerce\Tests\Internal\DataStores\Orders\DataSynchronizerTests::test_tables_are_created_when_hpos_enabled',
	'Automattic\WooCommerce\Tests\Internal\DataStores\Orders\DataSynchronizerTests::test_hpos_option_is_disabled_but_sync_enabled_with_pending_orders',
	'Automattic\WooCommerce\Tests\Internal\DataStores\Orders\DataSynchronizerTests::setUp',

	// Creates the HPOS tables per test through setup_cot(). Move to wpSetUpBeforeClass() + setup_cot_tables(), as #68930 did for WC_Tests_API_Reports_Sales.
	'Automattic\WooCommerce\Tests\Internal\DataStores\Orders\LegacyDataCleanupTests::setup_cot',

	// Creates the HPOS tables per test through setup_cot(). Move to wpSetUpBeforeClass() + setup_cot_tables(), as #68930 did for WC_Tests_API_Reports_Sales.
	'Automattic\WooCommerce\Tests\Internal\DataStores\Orders\OrdersTableDataStoreCacheCrossBleedTest::setup_cot',

	// Creates the HPOS tables per test through setup_cot(). Move to wpSetUpBeforeClass() + setup_cot_tables(), as #68930 did for WC_Tests_API_Reports_Sales.
	'Automattic\WooCommerce\Tests\Internal\DataStores\Orders\OrdersTableDataStoreCachePrimingTest::setup_cot',

	// Exercises the attribute-lookup regenerator, which truncates and recreates its table. Needs an explicit option restore around the call.
	'Automattic\WooCommerce\Tests\Internal\ProductAttributesLookup\DataRegeneratorTest::test_initiate_regeneration_creates_lookup_table',
	'Automattic\WooCommerce\Tests\Internal\ProductAttributesLookup\DataRegeneratorTest::test_initiate_regeneration_initializes_temporary_options_and_enqueues_regeneration_step',
	'Automattic\WooCommerce\Tests\Internal\ProductAttributesLookup\DataRegeneratorTest::test_initiate_regeneration_does_not_enqueues_regeneration_step_when_no_products',
	'Automattic\WooCommerce\Tests\Internal\ProductAttributesLookup\DataRegeneratorTest::test_initiate_regeneration_correctly_processes_ids_and_enqueues_next_step',
	'Automattic\WooCommerce\Tests\Internal\ProductAttributesLookup\DataRegeneratorTest::test_regeneration_uses_the_woocommerce_attribute_lookup_regeneration_step_size_filter',
	'Automattic\WooCommerce\Tests\Internal\ProductAttributesLookup\DataRegeneratorTest::test_initiate_regeneration_finishes_when_no_more_products_available',
	'Automattic\WooCommerce\Tests\Internal\ProductAttributesLookup\DataRegeneratorTest::test_regenerate_tool_callback_runs_without_a_nonce',

	// The subject under test is schema code. The DDL cannot move; the test must save and restore every option it touches.
	'Automattic\WooCommerce\Tests\Internal\Utilities\DatabaseUtilTest::test_create_fts_index_order_address_table',
	'Automattic\WooCommerce\Tests\Internal\Utilities\DatabaseUtilTest::test_create_fts_index_order_item_table',
);
