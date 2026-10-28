<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\Checks\DuplicateGtinCheck;
use Automattic\WooCommerce\Internal\AgentReadiness\Checks\ImageTooSmallCheck;
use Automattic\WooCommerce\Internal\AgentReadiness\Checks\InvalidGtinCheck;
use Automattic\WooCommerce\Internal\AgentReadiness\Checks\MissingBrandCheck;
use Automattic\WooCommerce\Internal\AgentReadiness\Checks\MissingGtinCheck;
use Automattic\WooCommerce\Internal\AgentReadiness\Checks\MissingRegularPriceCheck;
use Automattic\WooCommerce\Internal\AgentReadiness\FeedChannels;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductFields;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductScan;
use Automattic\WooCommerce\Internal\AgentReadiness\ReadinessRule;
use Automattic\WooCommerce\Internal\AgentReadiness\StoreConditions;
use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonHelper;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the agent readiness checks.
 */
class ReadinessChecksTest extends WC_Unit_Test_Case {

	/**
	 * Shared scan for the checks.
	 *
	 * @var ProductScan
	 */
	private ProductScan $scan;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->scan = new ProductScan();
	}

	/**
	 * @testdox A physical product with no brand fails; a brand term or a virtual product passes.
	 */
	public function test_missing_brand(): void {
		$sut     = new MissingBrandCheck();
		$product = WC_Helper_Product::create_simple_product();

		$this->assertTrue( $sut->fails( $product, $this->scan ), 'A product with no brand should fail' );

		wp_set_object_terms( $product->get_id(), 'Acme', 'product_brand' );
		$this->assertFalse( $sut->fails( wc_get_product( $product->get_id() ), $this->scan ), 'A product with a brand term should pass' );

		$virtual = WC_Helper_Product::create_simple_product( true, array( 'virtual' => true ) );
		$this->assertFalse( $sut->fails( $virtual, $this->scan ), 'A virtual product is not asked for a brand' );
	}

	/**
	 * @testdox A brand set only in Google for WooCommerce still fails, but no longer counts against Google.
	 */
	public function test_missing_brand_with_google_feed_brand(): void {
		$sut     = new MissingBrandCheck();
		$product = WC_Helper_Product::create_simple_product();
		$product->update_meta_data( '_wc_gla_brand', 'Acme' );
		$product->save();

		$this->assertTrue( $sut->fails( $product, $this->scan ), 'ChatGPT and Copilot do not read the Google for WooCommerce brand' );
		$channels = $sut->channels_for( $product, $sut->channels() );
		$this->assertArrayNotHasKey( FeedChannels::GOOGLE, $channels );
		$this->assertArrayHasKey( FeedChannels::CHATGPT, $channels );
	}

	/**
	 * @testdox A product with no GTIN but a brand and an MPN no longer counts against Google.
	 */
	public function test_missing_gtin_with_brand_and_mpn(): void {
		$sut     = new MissingGtinCheck();
		$product = WC_Helper_Product::create_simple_product();
		$this->assertArrayHasKey( FeedChannels::GOOGLE, $sut->channels_for( $product, $sut->channels() ), 'No brand and no MPN' );

		$product->update_meta_data( '_wc_gla_mpn', 'AC-100' );
		$product->save();
		$this->assertArrayHasKey( FeedChannels::GOOGLE, $sut->channels_for( $product, $sut->channels() ), 'An MPN without a brand is not enough' );

		$product->update_meta_data( '_wc_gla_brand', 'Acme' );
		$product->save();
		$this->assertTrue( $sut->fails( $product, $this->scan ) );
		$channels = $sut->channels_for( $product, $sut->channels() );
		$this->assertArrayNotHasKey( FeedChannels::GOOGLE, $channels );
		$this->assertArrayHasKey( FeedChannels::COPILOT, $channels );

		$branded = WC_Helper_Product::create_simple_product();
		wp_set_object_terms( $branded->get_id(), 'Acme', 'product_brand' );
		$branded = wc_get_product( $branded->get_id() );
		$branded->update_meta_data( '_wc_gla_mpn', 'AC-200' );
		$branded->save();
		$this->assertArrayNotHasKey( FeedChannels::GOOGLE, $sut->channels_for( $branded, $sut->channels() ), 'A WooCommerce Brands brand counts too' );
	}

	/**
	 * @testdox A variable product with some unpriced variations fails as a gap; with none priced it can't be listed.
	 */
	public function test_missing_price_on_partly_priced_variable_product(): void {
		$sut      = new MissingRegularPriceCheck();
		$product  = WC_Helper_Product::create_variation_product();
		$children = $product->get_children();

		$this->assertFalse( $sut->fails( wc_get_product( $product->get_id() ), $this->scan ), 'A fully priced variable product should pass' );

		$this->unprice( (int) $children[0] );
		$partly = wc_get_product( $product->get_id() );

		$this->assertTrue( $sut->fails( $partly, $this->scan ), 'A partly priced variable product should fail' );
		$effects = array_unique( array_column( $sut->channels_for( $partly, $sut->channels() ), 'effect' ) );
		$this->assertSame( array( ReadinessRule::EFFECT_GAPS ), array_values( $effects ), 'Priced variations still list, so the effect is a gap' );

		foreach ( $children as $child_id ) {
			$this->unprice( (int) $child_id );
		}
		$unpriced = wc_get_product( $product->get_id() );

		$this->assertTrue( $sut->fails( $unpriced, $this->scan ), 'A variable product with no priced variation should fail' );
		$effects = array_unique( array_column( $sut->channels_for( $unpriced, $sut->channels() ), 'effect' ) );
		$this->assertSame( array( ReadinessRule::EFFECT_NOT_LISTABLE ), array_values( $effects ), 'With no price at all the product can\'t be listed' );
	}

	/**
	 * @testdox GTIN validation checks length and check digit and keeps leading zeros.
	 *
	 * @testWith ["036000291452", true]
	 *           ["0036000291452", true]
	 *           ["00036000291452", true]
	 *           ["036000291453", false]
	 *           ["96385074", true]
	 *           ["4006381333931", true]
	 *           ["4006381333932", false]
	 *           ["0306406152", false]
	 *           ["12345", false]
	 *           ["ABC000291452", false]
	 *
	 * @param string $gtin     GTIN as stored.
	 * @param bool   $expected Whether it is valid.
	 */
	public function test_is_valid_gtin( string $gtin, bool $expected ): void {
		$this->assertSame( $expected, ProductFields::is_valid_gtin( ProductFields::normalise_gtin( $gtin ) ) );
	}

	/**
	 * @testdox A GTIN with hyphens and spaces is normalised before validation.
	 */
	public function test_invalid_gtin_check_normalises_separators(): void {
		$sut     = new InvalidGtinCheck();
		$valid   = WC_Helper_Product::create_simple_product( true, array( 'global_unique_id' => '4006-3813 33931' ) );
		$invalid = WC_Helper_Product::create_simple_product( true, array( 'global_unique_id' => '4006381333932' ) );

		$this->assertFalse( $sut->fails( $valid, $this->scan ), 'A valid GTIN with separators should pass' );
		$this->assertTrue( $sut->fails( $invalid, $this->scan ), 'A GTIN with a wrong check digit should fail' );
	}

	/**
	 * @testdox GTINs that differ only by a narrow no-break space or a byte order mark count as duplicates.
	 */
	public function test_duplicate_gtin_strips_the_same_separators_as_validation(): void {
		$sut    = new DuplicateGtinCheck();
		$plain  = WC_Helper_Product::create_simple_product();
		$narrow = WC_Helper_Product::create_simple_product();
		$bom    = WC_Helper_Product::create_simple_product();
		$other  = WC_Helper_Product::create_simple_product();
		// Written to meta directly, as imports do, past core's uniqueness check.
		update_post_meta( $plain->get_id(), '_global_unique_id', '4006381333931' );
		update_post_meta( $narrow->get_id(), '_global_unique_id', "4006381\u{202F}333931" );
		update_post_meta( $bom->get_id(), '_global_unique_id', "\u{FEFF}036000291452" );
		update_post_meta( $other->get_id(), '_global_unique_id', '036000291452' );

		foreach ( array( $plain, $narrow, $bom, $other ) as $product ) {
			$this->assertTrue( $sut->fails( wc_get_product( $product->get_id() ), $this->scan ), 'Product ' . $product->get_id() . ' shares its GTIN' );
		}
	}

	/**
	 * @testdox A small main image is a gap on Google before January 31, 2027 and required from then.
	 *
	 * @testWith ["2027-01-30", "gaps"]
	 *           ["2027-01-31", "not_listable"]
	 *
	 * @param string $date     Date to judge at.
	 * @param string $expected Expected Google effect.
	 */
	public function test_image_too_small_effect_switches_on_date( string $date, string $expected ): void {
		$sut = new ImageTooSmallCheck( (int) strtotime( $date . ' 12:00:00 UTC' ) );

		$this->assertSame( $expected, $sut->channels()[ FeedChannels::GOOGLE ]['effect'] );
	}

	/**
	 * @testdox An image under 500 pixels on either side fails; one without recorded size does not.
	 */
	public function test_image_too_small_reads_attachment_size(): void {
		$sut        = new ImageTooSmallCheck();
		$product    = WC_Helper_Product::create_simple_product();
		$attachment = self::factory()->attachment->create( array( 'post_mime_type' => 'image/jpeg' ) );
		$product->set_image_id( $attachment );
		$product->save();

		$this->assertFalse( $sut->fails( $product, $this->scan ), 'An image with no recorded size should not be flagged' );

		wp_update_attachment_metadata(
			$attachment,
			array(
				'width'  => 800,
				'height' => 400,
			)
		);
		$this->assertTrue( $sut->fails( $product, $this->scan ), 'An 800 x 400 image should fail' );

		wp_update_attachment_metadata(
			$attachment,
			array(
				'width'  => 500,
				'height' => 500,
			)
		);
		$this->assertFalse( $sut->fails( $product, $this->scan ), 'A 500 x 500 image should pass' );
	}

	/**
	 * @testdox Store conditions report coming soon mode, discouraged search engines, UCP, permalinks and guest checkout.
	 */
	public function test_store_conditions(): void {
		global $wp_rewrite;
		$permalinks = (string) get_option( 'permalink_structure' );
		$sut        = new StoreConditions();
		$sut->init( new ComingSoonHelper() );

		update_option( 'woocommerce_coming_soon', 'no' );
		update_option( 'blog_public', '1' );
		update_option( 'woocommerce_feature_ucp_enabled', 'yes' );
		update_option( 'woocommerce_enable_guest_checkout', 'yes' );
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		$this->assertSame(
			array(
				'launched'          => true,
				'search_visible'    => true,
				'ucp_on'            => true,
				'pretty_permalinks' => true,
				'guest_checkout'    => true,
			),
			array_column( $sut->all(), 'ok', 'id' )
		);

		update_option( 'woocommerce_coming_soon', 'yes' );
		update_option( 'woocommerce_store_pages_only', 'yes' );
		update_option( 'blog_public', '0' );
		update_option( 'woocommerce_feature_ucp_enabled', 'no' );
		update_option( 'woocommerce_enable_guest_checkout', 'no' );
		$wp_rewrite->set_permalink_structure( '' );
		$this->assertSame(
			array(
				'launched'          => false,
				'search_visible'    => false,
				'ucp_on'            => false,
				'pretty_permalinks' => false,
				'guest_checkout'    => false,
			),
			array_column( $sut->all(), 'ok', 'id' )
		);

		update_option( 'woocommerce_enable_guest_checkout', 'yes' );
		add_filter( 'woocommerce_checkout_registration_required', '__return_true' );
		$guest_checkout = array_column( $sut->all(), 'ok', 'id' )['guest_checkout'];
		remove_filter( 'woocommerce_checkout_registration_required', '__return_true' );
		$this->assertFalse( $guest_checkout, 'An extension that requires registration should fail the check' );

		$this->assertSame(
			array(
				'launched'          => 'listing',
				'search_visible'    => 'listing',
				'ucp_on'            => 'ucp',
				'pretty_permalinks' => 'ucp',
				'guest_checkout'    => 'checkout',
			),
			array_column( $sut->all(), 'blocks', 'id' )
		);
		$wp_rewrite->set_permalink_structure( $permalinks );
	}

	/**
	 * Clear the prices on a variation.
	 *
	 * @param int $variation_id Variation id.
	 */
	private function unprice( int $variation_id ): void {
		$variation = wc_get_product( $variation_id );
		$variation->set_regular_price( '' );
		$variation->set_sale_price( '' );
		$variation->set_price( '' );
		$variation->save();
	}
}
