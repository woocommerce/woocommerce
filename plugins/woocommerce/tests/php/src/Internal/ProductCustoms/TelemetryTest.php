<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductCustoms;

use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Internal\ProductCustoms\Telemetry;

/**
 * Customs adoption snapshot tests.
 */
class TelemetryTest extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Telemetry
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new Telemetry();
	}

	/**
	 * @testdox Counts published products and variations once per populated field, keeping other tracker data.
	 */
	public function test_counts_published_products_and_variations(): void {
		$before  = $this->get_snapshot();
		$product = \WC_Helper_Product::create_simple_product();
		add_post_meta( $product->get_id(), '_customs_commodity_code', '090121' );
		add_post_meta( $product->get_id(), '_customs_commodity_code', '090121' );
		add_post_meta( $product->get_id(), '_customs_country_of_origin', 'BR' );
		add_post_meta( $product->get_id(), '_customs_description', 'Roasted coffee' );
		$origin_only = \WC_Helper_Product::create_simple_product();
		add_post_meta( $origin_only->get_id(), '_customs_country_of_origin', 'US' );
		add_post_meta( $origin_only->get_id(), '_customs_commodity_code', '' );

		foreach ( array( ProductStatus::DRAFT, ProductStatus::PRIVATE, ProductStatus::TRASH ) as $status ) {
			$excluded = \WC_Helper_Product::create_simple_product();
			$excluded->set_status( $status );
			$excluded->save();
			add_post_meta( $excluded->get_id(), '_customs_commodity_code', '090121' );
			add_post_meta( $excluded->get_id(), '_customs_country_of_origin', 'BR' );
			add_post_meta( $excluded->get_id(), '_customs_description', 'Coffee' );
		}

		$parent = new \WC_Product_Variable();
		$parent->save();
		$variation = new \WC_Product_Variation();
		$variation->set_parent_id( $parent->get_id() );
		$variation->save();
		add_post_meta( $variation->get_id(), '_customs_commodity_code', '090121' );
		add_post_meta( $variation->get_id(), '_customs_country_of_origin', 'BR' );
		add_post_meta( $variation->get_id(), '_customs_description', 'Coffee' );
		$private_variation = new \WC_Product_Variation();
		$private_variation->set_parent_id( $parent->get_id() );
		$private_variation->set_status( ProductStatus::PRIVATE );
		$private_variation->save();
		add_post_meta( $private_variation->get_id(), '_customs_commodity_code', '090121' );
		add_post_meta( $private_variation->get_id(), '_customs_country_of_origin', 'BR' );

		$data     = $this->sut->handle_woocommerce_tracker_data( array( 'existing' => 'value' ) );
		$snapshot = $data['product_customs'];

		$this->assertSame( 'value', $data['existing'], 'Existing tracker data must be preserved.' );
		$this->assertSame(
			array(
				'products_with_commodity_code'      => $before['products_with_commodity_code'] + 1,
				'products_with_country_of_origin'   => $before['products_with_country_of_origin'] + 2,
				'products_with_customs_description' => $before['products_with_customs_description'] + 1,
				'variations_with_commodity_code'    => $before['variations_with_commodity_code'] + 1,
				'variations_with_country_of_origin' => $before['variations_with_country_of_origin'] + 1,
			),
			$snapshot,
			'Only published products and variations with non-empty values should be counted, once per field.'
		);
	}

	/**
	 * @testdox Leaves malformed upstream tracker data untouched without querying products.
	 * @testWith [null]
	 *           ["invalid"]
	 * @param mixed $data Upstream filter result.
	 */
	public function test_invalid_filter_input( $data ): void {
		global $wpdb;
		$queries = $wpdb->num_queries;

		$this->assertSame( $data, $this->sut->handle_woocommerce_tracker_data( $data ), 'Non-array tracker data must be returned unchanged.' );
		$this->assertSame( $queries, $wpdb->num_queries, 'No query should run for non-array tracker data.' );
	}

	/**
	 * Returns the current customs snapshot.
	 *
	 * @return array<string, int>
	 */
	private function get_snapshot(): array {
		return $this->sut->handle_woocommerce_tracker_data( array() )['product_customs'];
	}
}
