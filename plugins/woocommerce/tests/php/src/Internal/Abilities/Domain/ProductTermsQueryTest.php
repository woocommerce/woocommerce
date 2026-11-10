<?php
/**
 * Product term query ability tests.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductTermsQuery;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductsQuery;
use Automattic\WooCommerce\Internal\Abilities\Ability;
use Automattic\WooCommerce\Abilities\ExtensibleAbilities;

/**
 * Tests product term reads through the registered ability.
 */
class ProductTermsQueryTest extends \WC_Unit_Test_Case {

	/**
	 * Process state restored after each test.
	 *
	 * @var array
	 */
	private $saved_state = array();

	/**
	 * Set up ability registration.
	 */
	public function setUp(): void {
		parent::setUp();
		foreach ( array( 'resources', 'fields' ) as $name ) {
			$property = new \ReflectionProperty( ExtensibleAbilities::class, $name );
			$property->setAccessible( true );
			$this->saved_state[ $name ] = $property->getValue();
			$property->setValue( null, array() );
		}
		update_option( 'woocommerce_feature_extensible_abilities_enabled', 'no' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Restore extension state.
	 */
	public function tearDown(): void {
		foreach ( $this->saved_state as $name => $value ) {
			$property = new \ReflectionProperty( ExtensibleAbilities::class, $name );
			$property->setAccessible( true );
			$property->setValue( null, $value );
		}
		parent::tearDown();
	}

	/**
	 * @testdox Should preserve the data returned by each existing term read, using include for individual terms.
	 * @testWith ["product_cat", "WC_REST_Product_Categories_Controller"]
	 *           ["product_tag", "WC_REST_Product_Tags_Controller"]
	 *           ["product_brand", "WC_REST_Product_Brands_Controller"]
	 * @param string $taxonomy Taxonomy.
	 * @param string $controller_class Existing read controller.
	 */
	public function test_output_parity( string $taxonomy, string $controller_class ): void {
		$id = self::factory()->term->create(
			array(
				'taxonomy'    => $taxonomy,
				'name'        => 'Summer',
				'description' => 'Seasonal products',
			)
		);
		update_term_meta( $id, 'display_type', 'both' );
		update_term_meta( $id, 'order', 7 );
		$controller = new $controller_class();
		$expected   = $controller->prepare_item_for_response( get_term( $id, $taxonomy ), new \WP_REST_Request() )->get_data();
		unset( $expected['image'] );

		$ability = new \WP_Ability( ProductTermsQuery::get_name(), ProductTermsQuery::get_registration_args() );
		$result  = $ability->execute(
			array(
				'taxonomy' => $taxonomy,
				'include'  => array( $id ),
			)
		);
		$this->assertIsArray( $result );
		$this->assertCount( 1, $result['terms'] );
		foreach ( $expected as $key => $value ) {
			$this->assertSame( $value, $result['terms'][0][ $key ], $key );
		}
		$this->assertArrayNotHasKey( 'image', $result['terms'][0] );
	}

	/**
	 * @testdox Should return term names with HTML entities decoded.
	 */
	public function test_decodes_term_names(): void {
		$id = self::factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Tools & Hardware',
			)
		);

		$ability = new \WP_Ability( ProductTermsQuery::get_name(), ProductTermsQuery::get_registration_args() );
		$result  = $ability->execute(
			array(
				'taxonomy' => 'product_cat',
				'include'  => array( $id ),
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( 'Tools & Hardware', $result['terms'][0]['name'] );
	}

	/**
	 * @testdox Should find a term whose name holds an entity when searching for "$search".
	 * @testWith ["Tools & Hardware"]
	 *           ["Tools &amp; Hardware"]
	 *           ["& Hard"]
	 *           ["Hardware"]
	 * @param string $search Search input.
	 */
	public function test_search_matches_names_with_entities( string $search ): void {
		$id = self::factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Tools & Hardware',
			)
		);

		$this->assertSame( array( $id ), array_column( $this->search_categories( $search ), 'id' ) );
	}

	/**
	 * @testdox Should not encode an already encoded search twice.
	 */
	public function test_search_does_not_double_encode(): void {
		self::factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Tools & Hardware',
			)
		);

		$this->assertSame( array(), $this->search_categories( 'Tools &amp;amp; Hardware' ) );
	}

	/**
	 * @testdox Should return the same term name as products-query.
	 */
	public function test_term_name_matches_products_query(): void {
		$id      = self::factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Tools & Hardware',
			)
		);
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_category_ids( array( $id ) );
		$product->save();

		$products_query = new \WP_Ability( ProductsQuery::get_name(), ProductsQuery::get_registration_args() );
		$products       = $products_query->execute( array( 'id' => $product->get_id() ) );

		$this->assertIsArray( $products );
		$this->assertSame(
			$products['products'][0]['categories'][0]['name'],
			$this->search_categories( 'Hardware' )[0]['name']
		);
	}

	/**
	 * @testdox Should filter, order and paginate product terms without leaking other taxonomies.
	 */
	public function test_filters_and_pagination(): void {
		$parent = self::factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Parent',
			)
		);
		$first  = self::factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Match A',
				'parent'   => $parent,
			)
		);
		$second = self::factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Match B',
				'parent'   => $parent,
			)
		);
		$tag    = self::factory()->term->create(
			array(
				'taxonomy' => 'product_tag',
				'name'     => 'Match C',
			)
		);
		$input  = array(
			'taxonomy' => 'product_cat',
			'search'   => 'Match',
			'parent'   => $parent,
			'per_page' => 1,
			'page'     => 2,
			'orderby'  => 'name',
		);
		$result = ProductTermsQuery::execute( $input );
		$this->assertSame( $second, $result['terms'][0]['id'] );
		$this->assertSame( 2, $result['total_pages'] );
		$this->assertSame( 2, $result['total'] );
		$this->assertSame( 2, $result['page'] );

		$result = ProductTermsQuery::execute(
			array(
				'taxonomy' => 'product_cat',
				'include'  => array( $second, $first, $tag ),
				'orderby'  => 'include',
			)
		);
		$this->assertSame( array( $second, $first ), array_column( $result['terms'], 'id' ) );
		$result = ProductTermsQuery::execute(
			array(
				'taxonomy'   => 'product_cat',
				'include'    => array( $first ),
				'hide_empty' => true,
			)
		);
		$this->assertCount( 0, $result['terms'] );
	}

	/**
	 * @testdox Should count and paginate non-empty terms together with their empty parents.
	 * @testWith ["product_cat"]
	 *           ["product_brand"]
	 * @param string $taxonomy Taxonomy.
	 */
	public function test_hide_empty_hierarchical_totals( string $taxonomy ): void {
		$parent  = self::factory()->term->create(
			array(
				'taxonomy' => $taxonomy,
				'name'     => 'Zulu parent',
			)
		);
		$first   = self::factory()->term->create(
			array(
				'taxonomy' => $taxonomy,
				'name'     => 'Alpha child',
				'parent'   => $parent,
			)
		);
		$second  = self::factory()->term->create(
			array(
				'taxonomy' => $taxonomy,
				'name'     => 'Bravo child',
				'parent'   => $parent,
			)
		);
		$hidden  = self::factory()->term->create(
			array(
				'taxonomy' => $taxonomy,
				'name'     => 'Charlie hidden',
			)
		);
		$product = \WC_Helper_Product::create_simple_product();
		wp_set_object_terms( $product->get_id(), array( $first, $second ), $taxonomy );
		$hidden_product = \WC_Helper_Product::create_simple_product();
		$hidden_product->set_catalog_visibility( 'hidden' );
		$hidden_product->save();
		wp_set_object_terms( $hidden_product->get_id(), array( $hidden ), $taxonomy );

		$input  = array(
			'taxonomy'   => $taxonomy,
			'include'    => array( $parent, $first, $second, $hidden ),
			'hide_empty' => true,
		);
		$result = ProductTermsQuery::execute( $input );
		$this->assertCount( 4, $result['terms'] );
		$this->assertSame( 4, $result['total'] );
		$this->assertSame( 1, $result['total_pages'] );
		$this->assertSame( 0, $result['terms'][2]['count'] );

		$seen = array();
		for ( $page = 1; $page <= 4; ++$page ) {
			$result = ProductTermsQuery::execute(
				array_merge(
					$input,
					array(
						'page'     => $page,
						'per_page' => 1,
					)
				)
			);
			$this->assertSame( 4, $result['total'] );
			$this->assertSame( 4, $result['total_pages'] );
			$this->assertCount( 1, $result['terms'] );
			$seen[] = $result['terms'][0]['id'];
		}
		$this->assertSame( array( $first, $second, $hidden, $parent ), $seen );
	}

	/**
	 * @testdox Should read a boolean string on hide_empty as a boolean.
	 * @testWith [false, 1]
	 *           ["false", 1]
	 *           [true, 0]
	 *           ["true", 0]
	 * @param bool|string $hide_empty Boolean input.
	 * @param int         $expected_count Expected number of empty terms.
	 */
	public function test_hide_empty_boolean_input( $hide_empty, int $expected_count ): void {
		$id      = self::factory()->term->create( array( 'taxonomy' => 'product_tag' ) );
		$ability = new Ability( ProductTermsQuery::get_name(), ProductTermsQuery::get_registration_args() );
		$result  = $ability->execute(
			array(
				'taxonomy'   => 'product_tag',
				'include'    => array( $id ),
				'hide_empty' => $hide_empty,
			)
		);
		$this->assertCount( $expected_count, $result['terms'] );
		$this->assertSame( $expected_count, $result['total'] );
	}

	/**
	 * @testdox Should publish the slug filter as a list, so the input schema has one type.
	 */
	public function test_slug_filter_is_a_list(): void {
		$ability = new \WP_Ability( ProductTermsQuery::get_name(), ProductTermsQuery::get_registration_args() );

		$this->assertSame( 'array', $ability->get_input_schema()['properties']['slug']['type'] );
		$this->assertTrue(
			$ability->validate_input(
				array(
					'taxonomy' => 'product_tag',
					'slug'     => array( 'beta' ),
				)
			)
		);
	}

	/**
	 * @testdox Should support product, slug, exclude and offset filters.
	 */
	public function test_remaining_filters(): void {
		$first   = self::factory()->term->create(
			array(
				'taxonomy' => 'product_tag',
				'name'     => 'Alpha',
				'slug'     => 'alpha',
			)
		);
		$second  = self::factory()->term->create(
			array(
				'taxonomy' => 'product_tag',
				'name'     => 'Beta',
				'slug'     => 'beta',
			)
		);
		$product = \WC_Helper_Product::create_simple_product();
		wp_set_object_terms( $product->get_id(), array( $first, $second ), 'product_tag' );
		$result = ProductTermsQuery::execute(
			array(
				'taxonomy' => 'product_tag',
				'product'  => $product->get_id(),
				'slug'     => array( 'beta' ),
			)
		);
		$this->assertSame( array( $second ), array_column( $result['terms'], 'id' ) );
		$result = ProductTermsQuery::execute(
			array(
				'taxonomy' => 'product_tag',
				'product'  => $product->get_id(),
				'slug'     => array(),
			)
		);
		$this->assertSame( array( $first, $second ), array_column( $result['terms'], 'id' ) );
		$result = ProductTermsQuery::execute(
			array(
				'taxonomy' => 'product_tag',
				'slug'     => array( 'alpha', 'beta' ),
				'exclude'  => array( $first ),
			)
		);
		$this->assertSame( array( $second ), array_column( $result['terms'], 'id' ) );
		$result = ProductTermsQuery::execute(
			array(
				'taxonomy' => 'product_tag',
				'slug'     => 'alpha,beta',
			)
		);
		$this->assertSame( array( $first, $second ), array_column( $result['terms'], 'id' ) );
		$result = ProductTermsQuery::execute(
			array(
				'taxonomy' => 'product_tag',
				'include'  => array( $first, $second ),
				'offset'   => 1,
				'order'    => 'desc',
			)
		);
		$this->assertSame( array( $first ), array_column( $result['terms'], 'id' ) );
	}

	/**
	 * @testdox Should return image metadata with valid dates and omit missing images.
	 * @testWith ["product_cat"]
	 *           ["product_brand"]
	 * @param string $taxonomy Taxonomy.
	 */
	public function test_image_metadata( string $taxonomy ): void {
		update_option( 'timezone_string', 'Europe/Madrid' );
		$id       = self::factory()->term->create( array( 'taxonomy' => $taxonomy ) );
		$image_id = self::factory()->post->create(
			array(
				'post_type'      => 'attachment',
				'post_title'     => 'Summer image',
				'post_mime_type' => 'image/jpeg',
				'guid'           => 'https://example.test/summer.jpg',
			)
		);
		update_post_meta( $image_id, '_wp_attachment_image_alt', 'Summer products' );
		update_term_meta( $id, 'thumbnail_id', $image_id );
		$ability = new \WP_Ability( ProductTermsQuery::get_name(), ProductTermsQuery::get_registration_args() );
		$result  = $ability->execute(
			array(
				'taxonomy' => $taxonomy,
				'include'  => array( $id ),
			)
		);
		$this->assertIsArray( $result );
		$image = $result['terms'][0]['image'];
		$this->assertStringEndsWith( '+00:00', $image['date_created_gmt'] );
		$this->assertStringEndsWith( '+00:00', $image['date_modified_gmt'] );
		$this->assertSame( $image_id, $image['id'] );
		$this->assertSame( 'Summer image', $image['name'] );
		$this->assertSame( 'Summer products', $image['alt'] );
		$this->assertSame( wp_get_attachment_url( $image_id ), $image['src'] );
		foreach ( array( 'date_created', 'date_created_gmt', 'date_modified', 'date_modified_gmt' ) as $field ) {
			$this->assertInstanceOf( \DateTimeImmutable::class, \DateTimeImmutable::createFromFormat( DATE_RFC3339, $image[ $field ] ) );
		}
		add_filter( 'wp_get_attachment_url', '__return_false' );
		$result = $ability->execute(
			array(
				'taxonomy' => $taxonomy,
				'include'  => array( $id ),
			)
		);
		remove_filter( 'wp_get_attachment_url', '__return_false' );
		$this->assertIsArray( $result );
		$this->assertArrayNotHasKey( 'image', $result['terms'][0] );
		update_term_meta( $id, 'thumbnail_id', 99999999 );
		$result = $ability->execute(
			array(
				'taxonomy' => $taxonomy,
				'include'  => array( $id ),
			)
		);
		$this->assertArrayNotHasKey( 'image', $result['terms'][0] );
	}

	/**
	 * @testdox Should return the image title as stored, without texturized quotes and dashes.
	 */
	public function test_returns_plain_image_name(): void {
		$id       = self::factory()->term->create( array( 'taxonomy' => 'product_cat' ) );
		$image_id = self::factory()->post->create(
			array(
				'post_type'      => 'attachment',
				'post_title'     => 'Bob\'s mug -- "x"',
				'post_mime_type' => 'image/jpeg',
				'guid'           => 'https://example.test/mug.jpg',
			)
		);
		update_term_meta( $id, 'thumbnail_id', $image_id );

		$ability = new \WP_Ability( ProductTermsQuery::get_name(), ProductTermsQuery::get_registration_args() );
		$result  = $ability->execute(
			array(
				'taxonomy' => 'product_cat',
				'include'  => array( $id ),
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( 'Bob\'s mug -- "x"', $result['terms'][0]['image']['name'] );
	}

	/**
	 * @testdox Should enforce read permissions and validate taxonomy and pagination through the ability.
	 */
	public function test_permissions_and_validation(): void {
		$ability = new \WP_Ability( ProductTermsQuery::get_name(), ProductTermsQuery::get_registration_args() );
		$this->assertWPError( $ability->execute( array( 'taxonomy' => 'category' ) ) );
		$this->assertWPError(
			$ability->execute(
				array(
					'taxonomy' => 'product_cat',
					'per_page' => 0,
				)
			)
		);
		$this->assertWPError( $ability->execute( array() ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );
		$this->assertWPError( $ability->execute( array( 'taxonomy' => 'product_cat' ) ) );
		wp_set_current_user( 0 );
		$this->assertWPError( $ability->execute( array( 'taxonomy' => 'product_cat' ) ) );
	}

	/**
	 * @testdox Should reject an empty include list instead of returning every term.
	 */
	public function test_empty_include_is_rejected(): void {
		self::factory()->term->create( array( 'taxonomy' => 'product_tag' ) );
		$ability = new \WP_Ability( ProductTermsQuery::get_name(), ProductTermsQuery::get_registration_args() );
		$this->assertWPError(
			$ability->execute(
				array(
					'taxonomy' => 'product_tag',
					'include'  => array(),
				)
			)
		);
	}

	/**
	 * @testdox Should expose extension schema and WP_Term fields only while the feature is enabled.
	 * @testWith ["product_cat"]
	 *           ["product_tag"]
	 *           ["product_brand"]
	 * @param string $taxonomy Taxonomy.
	 */
	public function test_extension_fields( string $taxonomy ): void {
		$id       = self::factory()->term->create( array( 'taxonomy' => $taxonomy ) );
		$other_id = self::factory()->term->create( array( 'taxonomy' => $taxonomy ) );
		$input    = array(
			'taxonomy' => $taxonomy,
			'include'  => array( $id, $other_id ),
		);
		$baseline = ProductTermsQuery::execute( $input );
		$schema   = ProductTermsQuery::get_registration_args()['output_schema'];
		update_option( 'woocommerce_feature_extensible_abilities_enabled', 'yes' );
		$this->assertSame( $baseline, ProductTermsQuery::execute( $input ) );
		$this->assertSame( $schema, ProductTermsQuery::get_registration_args()['output_schema'] );
		add_action( 'woocommerce_extensible_abilities_resources_init', array( AbilitiesLoader::class, 'register_core_resources' ), 0 );
		do_action( 'woocommerce_extensible_abilities_resources_init' );
		add_action(
			'woocommerce_extensible_abilities_init',
			static function () {
				ExtensibleAbilities::register_field(
					array(
						'resource'      => 'product_term',
						'namespace'     => 'test-ext',
						'field'         => 'taxonomy',
						'output_schema' => array(
							'type'  => 'string',
							'title' => 'Taxonomy',
						),
						'get_callback'  => static function ( \WP_Term $term ) {
								return $term->taxonomy;
						},
					)
				);
			}
		);
		do_action( 'woocommerce_extensible_abilities_init' );
		$ability = new \WP_Ability( ProductTermsQuery::get_name(), ProductTermsQuery::get_registration_args() );
		$result  = $ability->execute( $input );
		$this->assertIsArray( $result );
		$this->assertCount( 2, $result['terms'] );
		foreach ( $result['terms'] as $term ) {
			$this->assertSame( $taxonomy, $term['extensions']['test-ext']['taxonomy'] );
		}
		$this->assertSame( 'string', $ability->get_output_schema()['properties']['terms']['items']['properties']['extensions']['properties']['test-ext']['properties']['taxonomy']['type'] );
		update_option( 'woocommerce_feature_extensible_abilities_enabled', 'no' );
		$this->assertSame( $baseline, ProductTermsQuery::execute( $input ) );
		$this->assertSame( $schema, ProductTermsQuery::get_registration_args()['output_schema'] );
	}

	/**
	 * Product categories the ability returns for a search.
	 *
	 * @param string $search Search input.
	 * @return array
	 */
	private function search_categories( string $search ): array {
		$ability = new \WP_Ability( ProductTermsQuery::get_name(), ProductTermsQuery::get_registration_args() );
		$result  = $ability->execute(
			array(
				'taxonomy' => 'product_cat',
				'search'   => $search,
			)
		);
		$this->assertIsArray( $result );

		return $result['terms'];
	}

	/**
	 * @testdox Should return the terms of a global attribute with their menu order and no category fields.
	 */
	public function test_queries_global_attribute_terms(): void {
		$attribute = \WC_Helper_Product::create_attribute( 'Color', array( 'Red' ) );
		update_term_meta( $attribute['term_ids'][0], 'order', 3 );
		$ability = new Ability( ProductTermsQuery::get_name(), ProductTermsQuery::get_registration_args() );

		$result = $ability->execute( array( 'taxonomy' => 'pa_color' ) );

		$this->assertIsArray( $result );
		$this->assertSame( 1, $result['total'] );
		$this->assertSame( 'Red', $result['terms'][0]['name'] );
		$this->assertSame( 3, $result['terms'][0]['menu_order'] );
		$this->assertArrayNotHasKey( 'parent', $result['terms'][0] );
		$this->assertArrayNotHasKey( 'display', $result['terms'][0] );
	}

	/**
	 * @testdox Should page through global attribute terms in the order set on the attribute, as product-attributes-query lists them.
	 */
	public function test_pages_global_attribute_terms_in_attribute_order(): void {
		$attribute = \WC_Helper_Product::create_attribute( 'Test Shade', array( 'Amber', 'Bronze', 'Coral' ) );
		foreach ( $attribute['term_ids'] as $index => $term_id ) {
			update_term_meta( $term_id, 'order', 3 - $index );
		}
		$ability = new Ability( ProductTermsQuery::get_name(), ProductTermsQuery::get_registration_args() );

		$first  = $ability->execute(
			array(
				'taxonomy' => 'pa_test-shade',
				'per_page' => 2,
			)
		);
		$second = $ability->execute(
			array(
				'taxonomy' => 'pa_test-shade',
				'per_page' => 2,
				'page'     => 2,
			)
		);
		$named  = $ability->execute(
			array(
				'taxonomy' => 'pa_test-shade',
				'orderby'  => 'name',
			)
		);

		$this->assertSame( array( 'Coral', 'Bronze' ), wp_list_pluck( $first['terms'], 'name' ) );
		$this->assertSame( array( 'Amber' ), wp_list_pluck( $second['terms'], 'name' ) );
		$this->assertSame( array( 'Amber', 'Bronze', 'Coral' ), wp_list_pluck( $named['terms'], 'name' ), 'An explicit orderby still applies.' );
	}

	/**
	 * @testdox Should tell a store manager that a global attribute taxonomy does not exist.
	 */
	public function test_refuses_unknown_global_attribute_taxonomy(): void {
		$ability = new Ability( ProductTermsQuery::get_name(), ProductTermsQuery::get_registration_args() );

		$result = $ability->execute( array( 'taxonomy' => 'pa_missing' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_product_term_taxonomy_unknown', $result->get_error_code() );
	}

	/**
	 * @testdox Should refuse a taxonomy that is neither a product taxonomy nor a global attribute.
	 */
	public function test_refuses_other_taxonomy(): void {
		$ability = new Ability( ProductTermsQuery::get_name(), ProductTermsQuery::get_registration_args() );

		$result = $ability->execute( array( 'taxonomy' => 'category' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}

	/**
	 * @testdox Should return each term exactly once across pages when terms share a name, paging by $paging in $order order.
	 * @testWith ["page", "asc"]
	 *           ["page", "desc"]
	 *           ["offset", "asc"]
	 * @param string $paging Paging input.
	 * @param string $order  Sort direction.
	 */
	public function test_pages_terms_with_the_same_name_once_each( string $paging, string $order ): void {
		$ids = array();
		for ( $i = 0; $i < 5; $i++ ) {
			$parent = self::factory()->term->create(
				array(
					'taxonomy' => 'product_cat',
					'name'     => 'Parent ' . $i,
				)
			);
			$ids[]  = self::factory()->term->create(
				array(
					'taxonomy' => 'product_cat',
					'name'     => 'Twin',
					'parent'   => $parent,
				)
			);
		}

		$paged = array();
		for ( $page = 1; $page <= 3; $page++ ) {
			$input  = array(
				'taxonomy' => 'product_cat',
				'search'   => 'Twin',
				'order'    => $order,
				'per_page' => 2,
			);
			$input += 'page' === $paging ? array( 'page' => $page ) : array( 'offset' => ( $page - 1 ) * 2 );
			$result = ProductTermsQuery::execute( $input );
			$paged  = array_merge( $paged, array_column( $result['terms'], 'id' ) );
		}

		if ( 'desc' === $order ) {
			rsort( $ids );
		}
		$this->assertSame( $ids, $paged, 'Terms with the same name should be sorted by ID in the requested direction.' );
	}
}
