<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\ReadinessAbilities;
use Automattic\WooCommerce\Internal\AgentReadiness\ScheduledScan;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the agent readiness abilities.
 */
class ReadinessAbilitiesTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ReadinessAbilities
	 */
	private $sut;

	/**
	 * Action counts to restore.
	 *
	 * @var array<string, int|null>
	 */
	private array $action_counts = array();

	/**
	 * Register the abilities with the feature on, as an administrator.
	 */
	public function setUp(): void {
		global $wp_actions;
		parent::setUp();
		StoreOutputChecksTest::block_requests();
		foreach ( array( 'init', 'wp_abilities_api_init', 'wp_abilities_api_categories_init' ) as $action ) {
			$this->action_counts[ $action ] = $wp_actions[ $action ] ?? null;
		}
		// The Abilities API registry needs init to have fired.
		$wp_actions['init'] = max( 1, (int) ( $wp_actions['init'] ?? 0 ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		if ( ! wp_has_ability_category( 'woocommerce' ) ) {
			$category = static fn() => wp_register_ability_category(
				'woocommerce',
				array(
					'label'       => 'WooCommerce',
					'description' => 'WooCommerce.',
				)
			);
			add_action( 'wp_abilities_api_categories_init', $category );
			do_action( 'wp_abilities_api_categories_init' );
			remove_action( 'wp_abilities_api_categories_init', $category );
		}
		update_option( 'woocommerce_feature_agent_readiness_enabled', 'yes' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->sut = wc_get_container()->get( ReadinessAbilities::class );
		$this->register();
	}

	/**
	 * Unregister the abilities and restore the action counts, which live outside the rollback.
	 */
	public function tearDown(): void {
		global $wp_actions;
		try {
			foreach ( array( ReadinessAbilities::GET_REPORT, ReadinessAbilities::RUN_CHECK ) as $name ) {
				if ( wp_has_ability( $name ) ) {
					wp_unregister_ability( $name );
				}
			}
			foreach ( $this->action_counts as $action => $count ) {
				if ( null === $count ) {
					unset( $wp_actions[ $action ] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				} else {
					$wp_actions[ $action ] = $count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				}
			}
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Register this class's abilities again. Checking for one loads the registry
	 * first, which runs the hooked registration once already.
	 */
	private function register(): void {
		foreach ( array( ReadinessAbilities::GET_REPORT, ReadinessAbilities::RUN_CHECK ) as $name ) {
			if ( wp_has_ability( $name ) ) {
				wp_unregister_ability( $name );
			}
		}
		$callback = array( $this->sut, 'register_abilities' );
		add_action( 'wp_abilities_api_init', $callback );
		do_action( 'wp_abilities_api_init' );
		remove_action( 'wp_abilities_api_init', $callback );
	}

	/**
	 * @testdox The abilities are registered only while the feature is enabled.
	 */
	public function test_registered_only_with_feature(): void {
		$this->assertTrue( wp_has_ability( ReadinessAbilities::GET_REPORT ) );
		$meta = wp_get_ability( ReadinessAbilities::GET_REPORT )->get_meta();
		$this->assertTrue( $meta['mcp']['public'] );
		$this->assertTrue( $meta['annotations']['readonly'] );

		update_option( 'woocommerce_feature_agent_readiness_enabled', 'no' );
		$this->register();
		$this->assertFalse( wp_has_ability( ReadinessAbilities::GET_REPORT ) );
		$this->assertFalse( wp_has_ability( ReadinessAbilities::RUN_CHECK ) );
	}

	/**
	 * @testdox Only users who can manage WooCommerce can read the report or run a check.
	 */
	public function test_permission(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
		$this->assertTrue( wp_get_ability( ReadinessAbilities::GET_REPORT )->check_permissions() );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );
		$this->assertFalse( wp_get_ability( ReadinessAbilities::GET_REPORT )->check_permissions() );
		$this->assertInstanceOf( \WP_Error::class, wp_get_ability( ReadinessAbilities::GET_REPORT )->execute() );
		$this->assertFalse( wp_get_ability( ReadinessAbilities::RUN_CHECK )->check_permissions( array() ) );
		$this->assertInstanceOf( \WP_Error::class, wp_get_ability( ReadinessAbilities::RUN_CHECK )->execute() );
	}

	/**
	 * @testdox Before the first scan the report is null; after one it matches the output schema, with store checks read now.
	 */
	public function test_report_matches_output_schema(): void {
		delete_option( AgentReadiness::REPORT_OPTION );
		$ability = wp_get_ability( ReadinessAbilities::GET_REPORT );
		$this->assertNull( $ability->execute()['report'] );

		WC_Helper_Product::create_simple_product();
		wc_get_container()->get( AgentReadiness::class )->refresh_report();
		update_option( 'woocommerce_enable_guest_checkout', 'no' );

		$output = $ability->execute();

		$this->assertTrue( rest_validate_value_from_schema( $output, $ability->get_output_schema() ) );
		$store_checks = array_column( $output['report']['store_checks'], 'ok', 'id' );
		$this->assertFalse( $store_checks['guest_checkout'], 'A setting changed after the scan shows its current state' );
		$this->assertArrayHasKey( 'sitemap_lists_products', $store_checks, 'Store output checks come from the stored report' );
		$this->assertContains( 'missing_brand', array_column( $output['report']['checks'], 'id' ) );
		$this->assertFalse( $output['scan_pending'] );
	}

	/**
	 * @testdox Checking a product after a fix returns its new result; a variation is checked as its parent.
	 */
	public function test_run_check_one_product(): void {
		$ability = wp_get_ability( ReadinessAbilities::RUN_CHECK );
		$product = WC_Helper_Product::create_variation_product();

		$before = $ability->execute( array( 'product_id' => $product->get_children()[0] ) );
		$this->assertTrue( rest_validate_value_from_schema( $before, $ability->get_output_schema() ) );
		$this->assertSame( $product->get_id(), $before['product']['product_id'] );
		$this->assertContains( 'missing_brand', array_column( $before['product']['checks'], 'id' ) );
		$this->assertSame( 'not_listable', $before['product']['statuses']['chatgpt'] );

		wp_set_object_terms( $product->get_id(), 'Acme', 'product_brand' );
		$after = $ability->execute( array( 'product_id' => $product->get_id() ) );
		$this->assertNotContains( 'missing_brand', array_column( $after['product']['checks'], 'id' ) );
	}

	/**
	 * @testdox A product the check leaves out, or no product, is an error.
	 */
	public function test_run_check_product_errors(): void {
		$ability  = wp_get_ability( ReadinessAbilities::RUN_CHECK );
		$external = WC_Helper_Product::create_external_product();

		$this->assertSame( 'woocommerce_agent_readiness_product_not_checked', $ability->execute( array( 'product_id' => $external->get_id() ) )->get_error_code() );
		$this->assertSame( 'woocommerce_agent_readiness_product_not_found', $ability->execute( array( 'product_id' => 999999 ) )->get_error_code() );
	}

	/**
	 * @testdox Without a product the whole store is queued in the background, not checked in the request.
	 */
	public function test_run_check_queues_scan(): void {
		delete_option( AgentReadiness::REPORT_OPTION );
		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK, array(), ScheduledScan::GROUP );
		$ability = wp_get_ability( ReadinessAbilities::RUN_CHECK );

		$output = $ability->execute();

		$this->assertTrue( rest_validate_value_from_schema( $output, $ability->get_output_schema() ) );
		$this->assertTrue( $output['scan_pending'] );
		$this->assertNull( $output['report_generated_at'] );
		$this->assertNull( get_option( AgentReadiness::REPORT_OPTION, null ), 'Nothing ran in the request' );
	}
}
