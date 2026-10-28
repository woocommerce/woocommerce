<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
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
	 * @testdox The tab asks for a first run when no report is cached.
	 */
	public function test_render_without_report(): void {
		delete_transient( AgentReadiness::REPORT_TRANSIENT );

		ob_start();
		wc_get_container()->get( StatusTab::class )->render();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'No check has run yet.', $html );
		$this->assertStringContainsString( 'name="wc_agent_readiness_run"', $html );
	}
}
