<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Integrations;

use Automattic\WooCommerce\Internal\Integrations\WPPostsImporter;
use WC_Unit_Test_Case;
use WP_Taxonomy;

/**
 * Tests for the WPPostsImporter class.
 */
class WPPostsImporterTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WPPostsImporter
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new WPPostsImporter();
	}

	/**
	 * @testdox Should create a missing attribute and register its taxonomy the same way it is registered on init.
	 */
	public function test_registers_missing_attribute_taxonomy_like_init(): void {
		$posts = array(
			array(
				'post_type' => 'product',
				'terms'     => array( array( 'domain' => 'pa_wxr-import-size' ) ),
			),
		);

		try {
			$this->assertSame( $posts, $this->sut->register_product_attribute_taxonomies( $posts ), 'The posts should be returned unchanged.' );

			$taxonomy = get_taxonomy( 'pa_wxr-import-size' );
			$this->assertInstanceOf( WP_Taxonomy::class, $taxonomy, 'The attribute taxonomy should be registered in the same request.' );
			$this->assertNotSame( 0, wc_attribute_taxonomy_id_by_name( 'wxr-import-size' ), 'The attribute should be created.' );
			$this->assertFalse( $taxonomy->hierarchical, 'Attribute taxonomies are not hierarchical.' );
			$this->assertSame( 'manage_product_terms', $taxonomy->cap->manage_terms, 'The taxonomy should use the product term capabilities.' );
			$this->assertTrue( taxonomy_is_product_attribute( 'pa_wxr-import-size' ), 'The taxonomy should be known as a product attribute.' );
			$this->assertIsArray( wp_insert_term( 'Large', 'pa_wxr-import-size' ), 'Terms should be insertable into the new taxonomy.' );
		} finally {
			wc_delete_attribute( wc_attribute_taxonomy_id_by_name( 'wxr-import-size' ) );
		}
	}
}
