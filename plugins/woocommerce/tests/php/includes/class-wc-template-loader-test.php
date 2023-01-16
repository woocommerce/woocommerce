<?php
/**
 * Unit tests for the WC_Template_Loader class.
 *
 * @package WooCommerce\Tests\WC_Template_Loader.
 */

use Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper;


/**
 * Class WC_Template_Loader
 * @group WC_Template_Loader
 */
class WC_Template_Loader_Test extends \WC_Unit_Test_Case {

	public function test_wc_template_loader_loads_default_file_without_blocks() {

		global $wp_taxonomies;

		$this->initialize_template_loader();

		// forcing has_block_template to be false
		add_filter( 'woocommerce_has_block_template', '__return_false', 10, 2 );

		// Check Single Product
		$this->load_product_in_query();
		$this->assertDefaultTemplateFileName( 'single-product' );

		// Check Woo Taxonomy Product
		$this->load_tax_in_query( 'product_cat' );
		$this->assertDefaultTemplateFileName( 'taxonomy-product-cat' );

		$this->load_tax_in_query( 'product_tag' );
		$this->assertDefaultTemplateFileName( 'taxonomy-product-tag' );

		// Check Woo Taxonomy Product Attribute.
		$this->load_product_attribute_tax_in_query();
		$this->assertDefaultTemplateFileName( 'taxonomy-product-attribute' );

		// Check Custom Product Taxonomies
		$wp_taxonomies['product_tax'] = new WP_Taxonomy( 'product_tax', 'product' );
		$this->load_tax_in_query( 'product_tax' );
		$this->assertDefaultTemplateFileName( 'archive-product' );

		// Check shop page
		$this->load_shop_page();
		$this->assertDefaultTemplateFileName( 'archive-product' );

	}

	public function test_wc_template_loader_loads_template_with_blocks() {

		global $wp_taxonomies;

		$this->initialize_template_loader();

		// forcing has_block_template to be false
		add_filter( 'woocommerce_has_block_template', '__return_true', 10, 2 );

		// Check Single Product
		$this->load_product_in_query();
		$this->assertDefaultTemplateFileName();

		// Check Woo Taxonomy Product
		$this->load_tax_in_query( 'product_cat' );
		$this->assertDefaultTemplateFileName();

		$this->load_tax_in_query( 'product_tag' );
		$this->assertDefaultTemplateFileName();

		// Check Woo Taxonomy Product Attribute.
		$this->load_product_attribute_tax_in_query();
		$this->assertDefaultTemplateFileName();

		// Check Custom Product Taxonomies
		$wp_taxonomies['product_tax'] = new WP_Taxonomy( 'product_tax', 'product' );
		$this->load_tax_in_query( 'product_tax' );
		$this->assertDefaultTemplateFileName();

		// Check shop page
		$this->load_shop_page();
		$this->assertDefaultTemplateFileName();

	}


	private function initialize_template_loader() {
		// be sure shop is always returning same id doesn't matter the test setup environment
		add_filter( 'woocommerce_get_shop_page_id', function ( $page ) {
			return 5;
		}, 10, 1 );

		if ( ! function_exists( 'wp_is_block_theme' ) ) {
			function wp_is_block_theme() {
				return true;
			}
		}

		WC_Template_Loader::init();
	}

	private function load_product_in_query() {
		global $wp_query;
		$wp_query->is_tax         = false;
		$wp_query->is_singular    = true;
		$wp_query->is_page        = false;
		$wp_query->queried_object = (object) array(
			'post_type' => 'product',
			'post_name' => 'test',
		);
	}

	private function load_shop_page() {
		global $wp_query;
		$wp_query->is_tax         = false;
		$wp_query->is_singular    = false;
		$wp_query->is_page        = true;
		$wp_query->queried_object = (object) array(
			'post_type'  => 'page',
			'post_name'  => 'shop',
			'post_title' => 'shop',
			'ID'         => 5,
		);
	}

	/**
	 * Loads a test taxonomy with the given name.
	 *
	 * @param string $taxonomy Taxonomy name.
	 *
	 * @return void
	 */
	private function load_tax_in_query( $taxonomy ) {
		global $wp_query;

		$wp_query->is_singular    = false;
		$wp_query->is_tax         = true;
		$wp_query->is_page        = false;
		$wp_query->queried_object = (object) array(
			'taxonomy' => $taxonomy,
			'slug'     => 'test',
		);
	}

	/**
	 * Loads a test product attribute taxonomy.
	 *
	 * @return void
	 */
	private function load_product_attribute_tax_in_query() {
		$attr = ProductHelper::create_attribute( 'color', array( 'red', 'blue' ) );
		$this->load_tax_in_query( $attr['attribute_taxonomy'] );
	}

	private function assertDefaultTemplateFileName( $expected = '' ) {

		$default_file = WC_Template_Loader::template_loader( 'test' );

		if ( ! $expected ) {
			$this->assertEquals( 'test', $default_file );
		} else {
			$this->assertEquals( WC()->plugin_path() . '/templates/' . $expected . '.php', $default_file );
		}
	}
}
