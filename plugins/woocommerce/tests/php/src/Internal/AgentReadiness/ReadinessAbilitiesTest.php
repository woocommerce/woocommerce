<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\Abilities\Ability;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\ReportAbility;
use Automattic\WooCommerce\Internal\AgentReadiness\RunCheckAbility;
use Automattic\WooCommerce\Internal\AgentReadiness\ScheduledScan;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the agent readiness abilities.
 */
class ReadinessAbilitiesTest extends WC_Unit_Test_Case {

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
		$this->register();
	}

	/**
	 * Unregister the abilities and restore the action counts, which live outside the rollback.
	 */
	public function tearDown(): void {
		global $wp_actions;
		try {
			foreach ( array( ReportAbility::NAME, RunCheckAbility::NAME ) as $name ) {
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
		foreach ( array( ReportAbility::NAME, RunCheckAbility::NAME ) as $name ) {
			if ( wp_has_ability( $name ) ) {
				wp_unregister_ability( $name );
			}
		}
		$callback = array( AbilitiesLoader::class, 'register_abilities' );
		add_action( 'wp_abilities_api_init', $callback );
		do_action( 'wp_abilities_api_init' );
		remove_action( 'wp_abilities_api_init', $callback );
	}

	/**
	 * @testdox AbilitiesLoader registers the abilities with the Ability class, only while the feature is enabled.
	 */
	public function test_registered_only_with_feature(): void {
		$this->assertTrue( wp_has_ability( ReportAbility::NAME ) );
		$this->assertInstanceOf( Ability::class, wp_get_ability( ReportAbility::NAME ) );
		$this->assertInstanceOf( Ability::class, wp_get_ability( RunCheckAbility::NAME ) );
		$meta = wp_get_ability( ReportAbility::NAME )->get_meta();
		$this->assertTrue( $meta['mcp']['public'] );
		$this->assertTrue( $meta['annotations']['readonly'] );
		$this->assertFalse( wp_get_ability( RunCheckAbility::NAME )->get_meta()['annotations']['idempotent'] );

		update_option( 'woocommerce_feature_agent_readiness_enabled', 'no' );
		$this->register();
		$this->assertFalse( wp_has_ability( ReportAbility::NAME ) );
		$this->assertFalse( wp_has_ability( RunCheckAbility::NAME ) );
	}

	/**
	 * @testdox Only users who can manage WooCommerce can read the report or run a check.
	 */
	public function test_permission(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
		$this->assertTrue( wp_get_ability( ReportAbility::NAME )->check_permissions() );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );
		$this->assertFalse( wp_get_ability( ReportAbility::NAME )->check_permissions() );
		$this->assertInstanceOf( \WP_Error::class, wp_get_ability( ReportAbility::NAME )->execute() );
		$this->assertFalse( wp_get_ability( RunCheckAbility::NAME )->check_permissions( array() ) );
		$this->assertInstanceOf( \WP_Error::class, wp_get_ability( RunCheckAbility::NAME )->execute() );
	}

	/**
	 * @testdox Before the first scan the report is null; after one it matches the output schema, with store checks read now.
	 */
	public function test_report_matches_output_schema(): void {
		delete_option( AgentReadiness::REPORT_OPTION );
		$ability = wp_get_ability( ReportAbility::NAME );
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
		$this->assertFalse( $output['scan_stalled'] );
	}

	/**
	 * @testdox A queued check that has stalled is reported as scan_stalled, so an agent stops waiting for it.
	 */
	public function test_report_says_when_the_scan_stalled(): void {
		global $wpdb;
		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}actionscheduler_actions SET last_attempt_gmt = %s WHERE status = 'complete'", gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ) ) );
		as_schedule_single_action( time() - ScheduledScan::STALLED_AFTER - 60, ScheduledScan::RUN_NOW_HOOK, array(), ScheduledScan::GROUP );

		$output = wp_get_ability( ReportAbility::NAME )->execute();

		$this->assertTrue( $output['scan_pending'] );
		$this->assertTrue( $output['scan_stalled'] );
		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );
	}

	/**
	 * @testdox Checking a product after a fix returns its new result; a variation is checked as its parent.
	 */
	public function test_run_check_one_product(): void {
		$ability = wp_get_ability( RunCheckAbility::NAME );
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
	 * @testdox A product the check leaves out, or an id that is not a product, is an error.
	 */
	public function test_run_check_product_errors(): void {
		$ability  = wp_get_ability( RunCheckAbility::NAME );
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
		$ability = wp_get_ability( RunCheckAbility::NAME );

		$output = $ability->execute();

		$this->assertTrue( rest_validate_value_from_schema( $output, $ability->get_output_schema() ) );
		$this->assertTrue( $output['scan_pending'] );
		$this->assertFalse( $output['scan_stalled'] );
		$this->assertNull( $output['report_generated_at'] );
		$this->assertNull( get_option( AgentReadiness::REPORT_OPTION, null ), 'Nothing ran in the request' );
	}

	/**
	 * @testdox Without a product, a queued check that has stalled stays queued and is reported as scan_stalled.
	 */
	public function test_run_check_says_when_the_scan_stalled(): void {
		global $wpdb;
		delete_option( AgentReadiness::REPORT_OPTION );
		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}actionscheduler_actions SET last_attempt_gmt = %s WHERE status = 'complete'", gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ) ) );
		as_schedule_single_action( time() - ScheduledScan::STALLED_AFTER - 60, ScheduledScan::RUN_NOW_HOOK, array(), ScheduledScan::GROUP );

		$output = wp_get_ability( RunCheckAbility::NAME )->execute();

		$this->assertTrue( $output['scan_pending'] );
		$this->assertTrue( $output['scan_stalled'] );
		$this->assertNull( get_option( AgentReadiness::REPORT_OPTION, null ), 'The stalled check must not run in the request' );
		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );
	}
}
