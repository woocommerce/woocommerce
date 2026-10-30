<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\ReadinessRule;
use Automattic\WooCommerce\Internal\AgentReadiness\RemoteRules;
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
		$this->sut->init( $store_conditions, new RemoteRules() );
	}

	/**
	 * @testdox The report has the shared shape with store checks, channel totals and checks.
	 */
	public function test_report_shape(): void {
		$product = WC_Helper_Product::create_simple_product();

		$report = $this->sut->get_report();

		$this->assertSame( array( 'generated_at', 'products', 'hidden_from_catalog', 'unsupported_types', 'store_checks', 'channels', 'checks', 'truncated' ), array_keys( $report ) );
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
				$rules['uncategorised']                      = array(
					'bing' => array(
						'field'  => 'category',
						'effect' => 'gaps',
					),
				);
				return $rules;
			}
		);

		$report = $this->sut->get_report();
		$checks = array_column( $report['checks'], null, 'id' );

		$this->assertArrayNotHasKey( 'missing_brand', $checks );
		$this->assertSame( ReadinessRule::EFFECT_NOT_LISTABLE, $checks['missing_gtin']['channels']['google']['effect'] );
		$this->assertArrayNotHasKey( 'bing', $checks['missing_gtin']['channels'] );
		$this->assertArrayNotHasKey( 'google', $checks['title_too_long']['channels'] );
		$this->assertArrayNotHasKey( 'uncategorised', $checks, 'A check with no valid channel left does not run' );
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
	 * @testdox get_failing_check_ids returns the ids of the checks a product fails.
	 */
	public function test_get_failing_check_ids(): void {
		$product = WC_Helper_Product::create_simple_product( true, array( 'description' => 'A sturdy mug.' ) );

		$issues = $this->sut->get_failing_check_ids( $product );

		$this->assertContains( 'missing_brand', $issues );
		$this->assertContains( 'missing_images', $issues );
		$this->assertNotContains( 'missing_product_text', $issues );
		$this->assertNotContains( 'missing_regular_price', $issues );
	}

	/**
	 * @testdox get_failing_check_ids judges a variation by its parent and skips products the scan leaves out.
	 */
	public function test_get_failing_check_ids_follows_scan_coverage(): void {
		$variable  = WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $variable->get_children()[0] );
		$grouped   = WC_Helper_Product::create_grouped_product();
		$draft     = WC_Helper_Product::create_simple_product( true, array( 'status' => 'draft' ) );

		$this->assertSame( $this->sut->get_failing_check_ids( $variable ), $this->sut->get_failing_check_ids( $variation ), 'A variation should get its parent\'s issues' );
		$this->assertContains( 'missing_brand', $this->sut->get_failing_check_ids( $variation ) );
		$this->assertSame( array(), $this->sut->get_failing_check_ids( $grouped ), 'Grouped products are not scanned' );
		$this->assertSame( array(), $this->sut->get_failing_check_ids( $draft ), 'Unpublished products are not scanned' );

		$hidden = WC_Helper_Product::create_variation_product();
		$hidden->set_catalog_visibility( 'hidden' );
		$hidden->save();
		$this->assertSame( array(), $this->sut->get_failing_check_ids( wc_get_product( $hidden->get_children()[0] ) ), 'Nor a hidden product\'s variations' );
	}

	/**
	 * @testdox check_product gives each failing check's channel map for that product and its status per channel.
	 */
	public function test_check_product(): void {
		$product = WC_Helper_Product::create_simple_product( true, array( 'description' => 'A sturdy mug.' ) );
		$product->update_meta_data( '_wc_gla_brand', 'Acme' );
		$product->save();

		$result = $this->sut->check_product( $product );

		$this->assertSame( $product->get_id(), $result['product_id'] );
		$this->assertArrayHasKey( 'missing_brand', $result['checks'] );
		$this->assertArrayNotHasKey( 'google', $result['checks']['missing_brand'], 'The per-product map, not the check\'s default one' );
		$this->assertArrayNotHasKey( 'missing_product_text', $result['checks'] );
		$this->assertSame( 'not_listable', $result['statuses']['chatgpt'], 'No image keeps it off every channel' );
		$this->assertSame( array_keys( $result['checks'] ), $this->sut->get_failing_check_ids( $product ) );
	}

	/**
	 * @testdox check_product judges a variation by its parent and returns null for products the scan leaves out.
	 */
	public function test_check_product_coverage(): void {
		$variable  = WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $variable->get_children()[0] );

		$this->assertSame( $variable->get_id(), $this->sut->check_product( $variation )['product_id'] );
		$orphan = new \WC_Product_Variation();
		$orphan->set_parent_id( $variable->get_id() + 1000 );
		$this->assertNull( $this->sut->check_product( $orphan ), 'A variation without a parent is left out' );
		$this->assertNull( $this->sut->check_product( WC_Helper_Product::create_grouped_product() ) );
		$this->assertNull( $this->sut->check_product( WC_Helper_Product::create_simple_product( true, array( 'status' => 'draft' ) ) ) );
		$this->assertNull( $this->sut->check_product( WC_Helper_Product::create_simple_product( true, array( 'catalog_visibility' => 'hidden' ) ) ) );
	}

	/**
	 * @testdox A report rereads shared GTINs, so check_product sees a duplicate fixed since.
	 */
	public function test_check_product_after_a_report_sees_gtin_changes(): void {
		$first = WC_Helper_Product::create_simple_product();
		$first->set_global_unique_id( '4006381333931' );
		$first->save();
		// The product setter rejects a duplicate, as imports and direct writes do not.
		$second_id = WC_Helper_Product::create_simple_product()->get_id();
		update_post_meta( $second_id, '_global_unique_id', '4006381333931' );
		$this->assertArrayHasKey( 'duplicate_gtin', $this->sut->check_product( $first )['checks'] );

		delete_post_meta( $second_id, '_global_unique_id' );
		$this->sut->get_report();

		$this->assertArrayNotHasKey( 'duplicate_gtin', $this->sut->check_product( $first )['checks'] );
	}

	/**
	 * @testdox Products hidden from the shop catalog and unsupported types are counted, not checked.
	 */
	public function test_scan_counts_hidden_and_unsupported_products(): void {
		WC_Helper_Product::create_simple_product();
		$hidden_variable = WC_Helper_Product::create_variation_product();
		$hidden_variable->set_catalog_visibility( 'hidden' );
		$hidden_variable->save();
		$search_only = WC_Helper_Product::create_simple_product();
		$search_only->set_catalog_visibility( 'search' );
		$search_only->save();
		$shop_only = WC_Helper_Product::create_simple_product();
		$shop_only->set_catalog_visibility( 'catalog' );
		$shop_only->save();
		WC_Helper_Product::create_external_product();
		// A subscription whose plugin is inactive loads as simple but keeps its stored type.
		$subscription = WC_Helper_Product::create_simple_product();
		wp_set_object_terms( $subscription->get_id(), 'subscription', 'product_type' );
		\WC_Cache_Helper::invalidate_cache_group( 'product_' . $subscription->get_id() );
		$subscription = wc_get_product( $subscription->get_id() );
		WC_Helper_Product::create_simple_product( true, array( 'status' => 'draft' ) );

		$report = $this->sut->get_report();

		$this->assertSame( 2, $report['products'], 'Only the shown simple product and the shop-only one are checked' );
		$this->assertSame( 2, $report['hidden_from_catalog'], 'Hidden and search-only products are counted apart' );
		$this->assertSame( 2, $report['unsupported_types'], 'External and the subscription are counted apart' );
		$this->assertSame( 2, $report['channels']['chatgpt']['listable'] + $report['channels']['chatgpt']['not_listable'] );
		$this->assertNotContains( 'hidden_from_catalog', array_column( $report['checks'], 'id' ) );
		$this->assertSame( array(), $this->sut->get_failing_check_ids( $hidden_variable ), 'A hidden product has no issues' );
		$this->assertSame( array(), $this->sut->get_failing_check_ids( wc_get_product( $hidden_variable->get_children()[0] ) ), 'Nor do its variations' );
		$this->assertSame( array(), $this->sut->get_failing_check_ids( $subscription ), 'An unsupported type has no issues' );
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
