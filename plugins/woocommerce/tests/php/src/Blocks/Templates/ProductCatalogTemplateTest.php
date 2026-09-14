<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Templates;

use Automattic\WooCommerce\Blocks\Templates\ProductCatalogTemplate;
use WC_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for the Shop page's Product Catalog template relationship.
 */
class ProductCatalogTemplateTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ProductCatalogTemplate
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		switch_theme( 'twentytwentyfour' );
		$this->sut = new ProductCatalogTemplate();
		$this->sut->init();
	}

	/**
	 * @testdox Should associate only the Shop page template and preserve unrelated metadata.
	 */
	public function test_shop_template_metadata(): void {
		$shop_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_post_meta( $shop_id, '_thumbnail_id', 123 );
		update_option( 'woocommerce_shop_page_id', $shop_id );

		$this->assertSame( 'archive-product', get_page_template_slug( $shop_id ), 'The Shop page should be associated with the catalog.' );
		$this->assertSame( array( 'archive-product' ), get_post_meta( $shop_id, '_wp_page_template', false ), 'Multiple-value reads should return an array.' );
		$this->assertSame( '123', get_post_meta( $shop_id, '_thumbnail_id', true ), 'Featured image metadata must remain unchanged.' );
		$this->assertArrayHasKey( '_thumbnail_id', get_post_meta( $shop_id ), 'Reading all metadata must return the stored fields.' );
	}

	/**
	 * @testdox Should preserve template overrides supplied by earlier metadata filters.
	 */
	public function test_existing_metadata_override(): void {
		$shop_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( 'woocommerce_shop_page_id', $shop_id );
		add_filter(
			'get_post_metadata',
			static function ( $value, $post_id, $meta_key ) use ( $shop_id ) {
				return $shop_id === $post_id && '_wp_page_template' === $meta_key ? 'extension-template' : $value;
			},
			5,
			3
		);

		$this->assertSame( 'extension-template', get_page_template_slug( $shop_id ), 'Earlier metadata overrides should retain their precedence.' );
	}

	/**
	 * @testdox Should accept editor saves and move the relationship without overwriting the previous template.
	 */
	public function test_rest_save_and_shop_reassignment(): void {
		$shop_id     = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$new_shop_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_post_meta( $shop_id, '_wp_page_template', 'custom-page' );
		update_option( 'woocommerce_shop_page_id', $shop_id );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$request = new WP_REST_Request( 'GET', '/wp/v2/pages/' . $shop_id );
		$request->set_param( 'context', 'edit' );
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status(), 'The editor should be able to load the Shop page.' );
		$this->assertSame( 'archive-product', $response->get_data()['template'], 'The editor should receive the catalog relationship.' );

		$request = new WP_REST_Request( 'POST', '/wp/v2/pages/' . $shop_id );
		$request->set_param( 'template', 'archive-product' );
		$request->set_param( 'title', 'Updated shop' );
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status(), 'Saving the existing template relationship should pass REST validation.' );
		$this->assertSame( 'Updated shop', get_the_title( $shop_id ), 'The page changes should be saved.' );
		$this->assertSame( '', get_page_template_slug( $new_shop_id ), 'Other pages should keep their default template.' );

		update_option( 'woocommerce_shop_page_id', $new_shop_id );
		$this->assertSame( 'custom-page', get_page_template_slug( $shop_id ), 'The former Shop page should retain its stored template.' );
		$this->assertSame( 'archive-product', get_page_template_slug( $new_shop_id ), 'The relationship should follow the Shop page setting.' );

		switch_theme( 'twentytwentyone' );
		$this->assertSame( '', get_page_template_slug( $new_shop_id ), 'Classic themes should retain their existing behavior.' );
	}
}
