<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Integrations;

use Automattic\WooCommerce\Internal\Integrations\WPPostsImporter;
use WC_Unit_Test_Case;

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
	 * Taxonomies registered during a test that must be unregistered.
	 *
	 * @var string[]
	 */
	private $registered_taxonomies = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut                   = new WPPostsImporter();
		$this->registered_taxonomies = array();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->registered_taxonomies as $taxonomy ) {
			if ( taxonomy_exists( $taxonomy ) ) {
				unregister_taxonomy( $taxonomy );
			}
		}
		$this->registered_taxonomies = array();

		parent::tearDown();
	}

	/**
	 * @testdox Should attach register_product_attribute_taxonomies to wp_import_posts at priority 100.
	 */
	public function test_register_attaches_import_hook(): void {
		$this->assertFalse(
			has_action( 'wp_import_posts', array( $this->sut, 'register_product_attribute_taxonomies' ) ),
			'Hook should not be attached before register() is called'
		);

		$this->sut->register();

		$this->assertSame(
			100,
			has_action( 'wp_import_posts', array( $this->sut, 'register_product_attribute_taxonomies' ) ),
			'register() should attach the importer callback at priority 100'
		);
	}

	/**
	 * @testdox Should return non-array input unchanged.
	 */
	public function test_returns_non_array_input_unchanged(): void {
		$result = $this->sut->register_product_attribute_taxonomies( 'not-an-array' );

		$this->assertSame( 'not-an-array', $result, 'Non-array input should be returned unchanged' );
	}

	/**
	 * @testdox Should return an empty posts array unchanged.
	 */
	public function test_returns_empty_array_unchanged(): void {
		$result = $this->sut->register_product_attribute_taxonomies( array() );

		$this->assertSame( array(), $result, 'Empty posts array should be returned unchanged' );
	}

	/**
	 * @testdox Should not register a product-attribute taxonomy when the post is not a product.
	 */
	public function test_skips_non_product_posts(): void {
		$taxonomy = 'pa_wppinp';

		$this->assertFalse( taxonomy_exists( $taxonomy ), 'Taxonomy should not exist prior to invocation' );

		$posts = array(
			$this->make_import_post(
				'page',
				array(
					$this->make_import_term( $taxonomy ),
				)
			),
		);

		$result = $this->sut->register_product_attribute_taxonomies( $posts );

		$this->assertSame( $posts, $result, 'Posts payload should be returned unchanged' );
		$this->assertFalse( taxonomy_exists( $taxonomy ), 'Non-product posts should not register pa_ taxonomies' );
		$this->assertArrayNotHasKey( 'wppinp', wc_get_attribute_taxonomy_ids(), 'Non-product posts should not create a product attribute' );
	}

	/**
	 * @testdox Should skip products with no terms without aborting the rest of the import.
	 */
	public function test_skips_products_without_terms(): void {
		$later_taxonomy = 'pa_wppilater';

		$this->assertFalse( taxonomy_exists( $later_taxonomy ), 'Later taxonomy should not exist prior to invocation' );

		$posts = array(
			array(
				'post_type' => 'product',
				'terms'     => array(),
			),
			$this->make_import_post(
				'product',
				array(
					$this->make_import_term( $later_taxonomy ),
				)
			),
		);

		$result                        = $this->sut->register_product_attribute_taxonomies( $posts );
		$this->registered_taxonomies[] = $later_taxonomy;

		$this->assertSame( $posts, $result, 'Posts payload should be returned unchanged' );
		$this->assertTrue( taxonomy_exists( $later_taxonomy ), 'Empty-terms products should be skipped with continue, not return' );
	}

	/**
	 * @testdox Should not register a taxonomy when product terms are not product-attribute taxonomies.
	 */
	public function test_skips_terms_that_are_not_product_attributes(): void {
		$non_attribute_taxonomy = 'colorattr';
		$attribute_taxonomy     = 'pa_wppionly';

		$this->assertFalse( taxonomy_exists( $non_attribute_taxonomy ), 'Non-attribute taxonomy should not exist prior to invocation' );
		$this->assertFalse( taxonomy_exists( $attribute_taxonomy ), 'Attribute taxonomy should not exist prior to invocation' );

		$posts = array(
			$this->make_import_post(
				'product',
				array(
					$this->make_import_term( $non_attribute_taxonomy, 'blue' ),
					$this->make_import_term( $attribute_taxonomy ),
				)
			),
		);

		$result                        = $this->sut->register_product_attribute_taxonomies( $posts );
		$this->registered_taxonomies[] = $attribute_taxonomy;

		$this->assertSame( $posts, $result, 'Posts payload should be returned unchanged' );
		$this->assertFalse( taxonomy_exists( $non_attribute_taxonomy ), 'Terms without pa_ should not be registered as taxonomies' );
		$this->assertArrayNotHasKey( 'colorattr', wc_get_attribute_taxonomy_ids(), 'Terms without pa_ should not create a product attribute' );
		$this->assertTrue( taxonomy_exists( $attribute_taxonomy ), 'Sibling pa_ terms on the same product should still be registered' );
	}

	/**
	 * @testdox Should skip attribute creation when the product-attribute taxonomy is already registered.
	 */
	public function test_skips_when_taxonomy_already_exists(): void {
		$taxonomy = 'pa_wppiexist';
		$slug     = 'wppiexist';

		register_taxonomy( $taxonomy, array( 'product' ) );
		$this->registered_taxonomies[] = $taxonomy;

		$this->assertTrue( taxonomy_exists( $taxonomy ), 'Taxonomy should exist before the importer runs' );
		$this->assertArrayNotHasKey( $slug, wc_get_attribute_taxonomy_ids(), 'Attribute should be absent before the importer runs' );

		$posts = array(
			$this->make_import_post(
				'product',
				array(
					$this->make_import_term( $taxonomy ),
				)
			),
		);

		$result = $this->sut->register_product_attribute_taxonomies( $posts );

		$this->assertSame( $posts, $result, 'Posts payload should be returned unchanged' );
		$this->assertTrue( taxonomy_exists( $taxonomy ), 'Existing taxonomy should remain registered' );
		$this->assertArrayNotHasKey( $slug, wc_get_attribute_taxonomy_ids(), 'Existing taxonomies should not create a new product attribute' );
	}

	/**
	 * @testdox Should create the attribute and register the taxonomy for a new pa_ term on a product post.
	 */
	public function test_registers_taxonomy_and_creates_attribute_for_new_pa_term(): void {
		$taxonomy = 'pa_wppinew';
		$slug     = 'wppinew';

		$this->assertFalse( taxonomy_exists( $taxonomy ), 'Taxonomy should not exist prior to invocation' );
		$this->assertArrayNotHasKey( $slug, wc_get_attribute_taxonomy_ids(), 'Attribute should not exist prior to invocation' );

		$posts = array(
			$this->make_import_post(
				'product',
				array(
					$this->make_import_term( $taxonomy, 'blue' ),
				)
			),
		);

		$result = $this->sut->register_product_attribute_taxonomies( $posts );

		$this->registered_taxonomies[] = $taxonomy;

		$this->assertSame( $posts, $result, 'Posts payload should be returned unchanged' );
		$this->assertTrue( taxonomy_exists( $taxonomy ), 'New pa_ terms should register their taxonomy' );

		$attribute_ids = wc_get_attribute_taxonomy_ids();
		$this->assertArrayHasKey( $slug, $attribute_ids, 'New pa_ terms should create a product attribute' );

		$taxonomy_object = get_taxonomy( $taxonomy );
		$this->assertContains( 'product', $taxonomy_object->object_type, 'Registered taxonomy should apply to products' );
		$this->assertTrue( $taxonomy_object->hierarchical, 'Registered taxonomy should be hierarchical' );
		$this->assertFalse( $taxonomy_object->show_ui, 'Registered taxonomy should not show in the admin UI' );
		$this->assertSame( $taxonomy, $taxonomy_object->query_var, 'Registered taxonomy should enable query_var using the taxonomy name' );
		$this->assertFalse( $taxonomy_object->rewrite, 'Registered taxonomy should not enable rewrite rules' );
	}

	/**
	 * @testdox Should apply taxonomy object-type filters when registering a new product-attribute taxonomy.
	 */
	public function test_applies_taxonomy_object_type_filter(): void {
		$taxonomy = 'pa_wppifilt';
		$slug     = 'wppifilt';

		add_filter(
			'woocommerce_taxonomy_objects_' . $taxonomy,
			static function ( $object_types ) {
				$object_types[] = 'product_variation';
				return $object_types;
			}
		);

		$posts = array(
			$this->make_import_post(
				'product',
				array(
					$this->make_import_term( $taxonomy ),
				)
			),
		);

		$this->sut->register_product_attribute_taxonomies( $posts );
		$this->registered_taxonomies[] = $taxonomy;

		$taxonomy_object = get_taxonomy( $taxonomy );
		$this->assertNotFalse( $taxonomy_object, 'Filtered taxonomy should be registered' );
		$this->assertContains( 'product', $taxonomy_object->object_type, 'Default product object type should remain' );
		$this->assertContains( 'product_variation', $taxonomy_object->object_type, 'Filter should add product_variation to the object types' );
		$this->assertArrayHasKey( $slug, wc_get_attribute_taxonomy_ids(), 'Filtered taxonomy should still create the attribute' );
	}

	/**
	 * @testdox Should register pa_ taxonomies only for product posts when the import mix includes other post types.
	 */
	public function test_registers_only_product_attribute_taxonomies_from_product_posts(): void {
		$product_taxonomy = 'pa_wppiprod';
		$page_taxonomy    = 'pa_wppipage';

		$this->assertFalse( taxonomy_exists( $product_taxonomy ), 'Product taxonomy should not exist prior to invocation' );
		$this->assertFalse( taxonomy_exists( $page_taxonomy ), 'Page taxonomy should not exist prior to invocation' );

		$posts = array(
			$this->make_import_post(
				'page',
				array(
					$this->make_import_term( $page_taxonomy ),
				)
			),
			$this->make_import_post(
				'product',
				array(
					$this->make_import_term( $product_taxonomy ),
					$this->make_import_term( 'product_tag', 'featured' ),
				)
			),
		);

		$result                        = $this->sut->register_product_attribute_taxonomies( $posts );
		$this->registered_taxonomies[] = $product_taxonomy;

		$this->assertSame( $posts, $result, 'Posts payload should be returned unchanged' );
		$this->assertTrue( taxonomy_exists( $product_taxonomy ), 'Product pa_ terms should register their taxonomy' );
		$this->assertFalse( taxonomy_exists( $page_taxonomy ), 'Page pa_ terms should not register a taxonomy' );
		$this->assertArrayHasKey( 'wppiprod', wc_get_attribute_taxonomy_ids(), 'Product pa_ terms should create an attribute' );
		$this->assertArrayNotHasKey( 'wppipage', wc_get_attribute_taxonomy_ids(), 'Page pa_ terms should not create an attribute' );
	}

	/**
	 * Build a WXR-style post array for the importer.
	 *
	 * @param string $post_type Post type.
	 * @param array  $terms     Term rows.
	 * @return array
	 */
	private function make_import_post( string $post_type, array $terms ): array {
		return array(
			'post_type' => $post_type,
			'terms'     => $terms,
		);
	}

	/**
	 * Build a WXR-style term row.
	 *
	 * @param string $domain Taxonomy name.
	 * @param string $slug   Term slug.
	 * @return array
	 */
	private function make_import_term( string $domain, string $slug = 'blue' ): array {
		return array(
			'domain' => $domain,
			'slug'   => $slug,
			'name'   => $slug,
		);
	}
}
