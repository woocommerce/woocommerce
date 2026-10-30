<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\ScheduledScan;
use Automattic\WooCommerce\Internal\AgentReadiness\StatusTab;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the Agent readiness status tab.
 */
class StatusTabTest extends WC_Unit_Test_Case {

	/**
	 * @testdox The tab renders store-wide problems, channel tags and product links from the cached report.
	 */
	public function test_render_from_cached_report(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$product = WC_Helper_Product::create_simple_product();
		update_option( 'blog_public', '0' );
		StoreOutputChecksTest::block_requests();
		wc_get_container()->get( AgentReadiness::class )->refresh_report();

		ob_start();
		wc_get_container()->get( StatusTab::class )->render();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'Store is hidden from search engines', $html );
		$this->assertStringContainsString( 'Required by ChatGPT', $html );
		$this->assertStringContainsString( 'Harder to find on Google', $html );
		$this->assertStringContainsString( 'post=' . $product->get_id(), $html );
	}

	/**
	 * @testdox The tab lists the products it counts but doesn't check.
	 */
	public function test_render_not_checked_counts(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$hidden = WC_Helper_Product::create_simple_product();
		$hidden->set_catalog_visibility( 'hidden' );
		$hidden->save();
		WC_Helper_Product::create_external_product();
		StoreOutputChecksTest::block_requests();
		wc_get_container()->get( AgentReadiness::class )->refresh_report();

		ob_start();
		wc_get_container()->get( StatusTab::class )->render();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'Hidden from your catalog: 1', $html );
		$this->assertStringContainsString( 'Product type not supported yet: 1', $html );
	}

	/**
	 * @testdox The tab asks for a first run when no report is cached.
	 */
	public function test_render_without_report(): void {
		delete_option( AgentReadiness::REPORT_OPTION );

		ob_start();
		wc_get_container()->get( StatusTab::class )->render();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'No check has run yet.', $html );
		$this->assertStringContainsString( 'name="wc_agent_readiness_run"', $html );
	}

	/**
	 * @testdox While a scan waits, the button is disabled and the tab says so; once it stalls, the button comes back with a warning.
	 */
	public function test_render_pending_and_stalled(): void {
		delete_option( AgentReadiness::REPORT_OPTION );
		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );
		wc_get_container()->get( ScheduledScan::class )->queue();

		$html = $this->render();
		$this->assertStringContainsString( 'Checking your products in the background.', $html );
		$this->assertStringNotContainsString( 'No check has run yet.', $html, 'A first scan is already on its way' );
		$this->assertMatchesRegularExpression( '/name="wc_agent_readiness_run"[^>]*disabled/', $html );

		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );
		$this->age_finished_actions();
		as_schedule_single_action( time() - ScheduledScan::STALLED_AFTER - 60, ScheduledScan::RUN_NOW_HOOK, array(), ScheduledScan::GROUP );

		$html = $this->render();
		$this->assertStringContainsString( 'The background check has not started', $html );
		$this->assertDoesNotMatchRegularExpression( '/name="wc_agent_readiness_run"[^>]*disabled/', $html );
		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );
	}

	/**
	 * The tab's HTML.
	 */
	private function render(): string {
		ob_start();
		wc_get_container()->get( StatusTab::class )->render();
		return (string) ob_get_clean();
	}

	/**
	 * Date every finished action an hour back, so only what the test does next
	 * counts as recent queue activity.
	 */
	private function age_finished_actions(): void {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}actionscheduler_actions SET last_attempt_gmt = %s WHERE status = 'complete'", gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ) ) );
	}
}
