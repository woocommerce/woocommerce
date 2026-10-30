<?php
/**
 * Product term query ability tests.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductTermsQuery;
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
				'slug'     => 'beta',
			)
		);
		$this->assertSame( array( $second ), array_column( $result['terms'], 'id' ) );
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
			$this->assertMatchesRegularExpression( '/(?:Z|[+-]\\d{2}:\\d{2})$/', $image[ $field ] );
		}
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
						'resource'     => 'product_term',
						'namespace'    => 'test-ext',
						'field'        => 'taxonomy',
						'schema'       => array(
							'type'  => 'string',
							'title' => 'Taxonomy',
						),
						'get_callback' => static function ( \WP_Term $term ) {
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
}
