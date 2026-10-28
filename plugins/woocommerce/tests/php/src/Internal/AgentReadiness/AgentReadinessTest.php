<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\ReadinessFeedValidator;
use Automattic\WooCommerce\Internal\AgentReadiness\ReadinessRule;
use Automattic\WooCommerce\Internal\AgentReadiness\StoreConditions;
use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonHelper;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the agent readiness report.
 */
class AgentReadinessTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var AgentReadiness
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$store_conditions = new StoreConditions();
		$store_conditions->init( new ComingSoonHelper() );
		$this->sut = new AgentReadiness();
		$this->sut->init( $store_conditions );
	}

	/**
	 * @testdox The report has the shared shape with store checks, channel totals and checks.
	 */
	public function test_report_shape(): void {
		$product = WC_Helper_Product::create_simple_product();

		$report = $this->sut->get_report();

		$this->assertSame( array( 'generated_at', 'products', 'products_with_issues', 'store_checks', 'channels', 'checks', 'truncated' ), array_keys( $report ) );
		$this->assertFalse( $report['truncated'] );
		$this->assertSame( array( 'id', 'ok', 'blocks', 'label', 'why', 'fix' ), array_keys( $report['store_checks'][0] ) );
		$this->assertSame( array( 'chatgpt', 'google', 'copilot' ), array_keys( $report['channels'] ) );
		$this->assertSame(
			array( 'name', 'spec', 'url', 'listable', 'not_listable', 'with_gaps', 'complete' ),
			array_keys( $report['channels']['chatgpt'] )
		);

		$checks = array_column( $report['checks'], null, 'id' );
		$this->assertSame(
			array( 'id', 'label', 'why', 'fix', 'channels', 'count', 'product_ids', 'fix_url' ),
			array_keys( $checks['missing_brand'] )
		);
		$this->assertSame( 1, $checks['missing_brand']['count'] );
		$this->assertSame( array( $product->get_id() ), $checks['missing_brand']['product_ids'] );
		$this->assertSame( ReadinessRule::EFFECT_NOT_LISTABLE, $checks['missing_brand']['channels']['chatgpt']['effect'] );

		$this->assertSame( 1, $report['products'] );
		$this->assertSame( 1, $report['products_with_issues'] );
		$this->assertSame( 1, $report['channels']['chatgpt']['not_listable'], 'No brand and no image keep the product off ChatGPT' );
		$this->assertSame( 0, $report['channels']['chatgpt']['listable'] );
	}

	/**
	 * @testdox The rules filter can drop a check and change an effect; invalid entries are ignored.
	 */
	public function test_rules_filter(): void {
		WC_Helper_Product::create_simple_product();
		add_filter(
			'woocommerce_agent_readiness_rules',
			static function ( $rules ) {
				unset( $rules['missing_brand'] );
				$rules['missing_gtin']['google']['effect']   = 'not_listable';
				$rules['missing_gtin']['bing']               = array(
					'field'  => 'gtin',
					'effect' => 'gaps',
				);
				$rules['title_too_long']['google']['effect'] = 'rejected';
				return $rules;
			}
		);

		$report = $this->sut->get_report();
		$checks = array_column( $report['checks'], null, 'id' );

		$this->assertArrayNotHasKey( 'missing_brand', $checks );
		$this->assertSame( ReadinessRule::EFFECT_NOT_LISTABLE, $checks['missing_gtin']['channels']['google']['effect'] );
		$this->assertArrayNotHasKey( 'bing', $checks['missing_gtin']['channels'] );
		$this->assertArrayNotHasKey( 'google', $checks['title_too_long']['channels'] );
	}

	/**
	 * @testdox A product complete on every channel is not counted as having issues.
	 */
	public function test_products_with_issues_skips_complete_products(): void {
		WC_Helper_Product::create_simple_product();
		add_filter( 'woocommerce_agent_readiness_rules', '__return_empty_array' );

		$report = $this->sut->get_report();

		$this->assertSame( 1, $report['products'] );
		$this->assertSame( 0, $report['products_with_issues'] );
	}

	/**
	 * @testdox The cached report is stored by refresh_report.
	 */
	public function test_refresh_report_caches(): void {
		$this->assertNull( $this->sut->get_cached_report() );

		$report = $this->sut->refresh_report();

		$this->assertSame( $report, $this->sut->get_cached_report() );
	}

	/**
	 * @testdox The feed validator returns the ids of the checks a product fails.
	 */
	public function test_feed_validator_returns_failing_check_ids(): void {
		$product   = WC_Helper_Product::create_simple_product( true, array( 'description' => 'A sturdy mug.' ) );
		$validator = new ReadinessFeedValidator();
		$validator->init( $this->sut );

		$issues = $validator->validate_entry( array(), $product );

		$this->assertContains( 'missing_brand', $issues );
		$this->assertContains( 'missing_images', $issues );
		$this->assertNotContains( 'missing_product_text', $issues );
		$this->assertNotContains( 'missing_regular_price', $issues );
	}

	/**
	 * @testdox The feed validator judges a variation by its parent and skips products the scan leaves out.
	 */
	public function test_feed_validator_follows_scan_coverage(): void {
		$variable  = WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $variable->get_children()[0] );
		$grouped   = WC_Helper_Product::create_grouped_product();
		$draft     = WC_Helper_Product::create_simple_product( true, array( 'status' => 'draft' ) );

		$this->assertSame( $this->sut->get_failing_check_ids( $variable ), $this->sut->get_failing_check_ids( $variation ), 'A variation should get its parent\'s issues' );
		$this->assertContains( 'missing_brand', $this->sut->get_failing_check_ids( $variation ) );
		$this->assertSame( array(), $this->sut->get_failing_check_ids( $grouped ), 'Grouped products are not scanned' );
		$this->assertSame( array(), $this->sut->get_failing_check_ids( $draft ), 'Unpublished products are not scanned' );
	}

	/**
	 * @testdox The rules filter runs once per instance, not once per product.
	 */
	public function test_rules_are_built_once(): void {
		$calls = 0;
		add_filter(
			'woocommerce_agent_readiness_rules',
			static function ( $rules ) use ( &$calls ) {
				++$calls;
				return $rules;
			}
		);

		$this->sut->get_failing_check_ids( WC_Helper_Product::create_simple_product() );
		$this->sut->get_failing_check_ids( WC_Helper_Product::create_simple_product() );

		$this->assertSame( 1, $calls );
	}
}
