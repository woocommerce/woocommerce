<?php
/**
 * Product attributes query ability tests.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Internal\Abilities\Ability;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductAttributesQuery;
use WC_Helper_Product;
use WC_Unit_Test_Case;
use Automattic\WooCommerce\Tests\Internal\Abilities\ExtensibleAbilitiesTestTrait;

/**
 * Tests global attribute reads through the registered ability.
 */
class ProductAttributesQueryTest extends WC_Unit_Test_Case {

	use ExtensibleAbilitiesTestTrait;

	/**
	 * The System Under Test.
	 *
	 * @var Ability
	 */
	private $sut;


	/**
	 * An administrator that every test acts as.
	 *
	 * @var int
	 */
	private static $administrator_id;

	/**
	 * Create the fixtures that no test changes beyond its own transaction.
	 *
	 * @param \WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$administrator_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Set up the ability and an administrator.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->isolate_extensible_abilities();
		update_option( 'woocommerce_feature_extensible_abilities_enabled', 'no' );
		$this->sut = new Ability( ProductAttributesQuery::get_name(), ProductAttributesQuery::get_registration_args() );
		wp_set_current_user( self::$administrator_id );
	}

	/**
	 * @testdox Should return the fields of the wc/v3 attribute read, with the terms and their count.
	 */
	public function test_output_parity_and_terms(): void {
		$attribute = WC_Helper_Product::create_attribute( 'Test Color', array( 'Red', 'Blue' ) );
		update_term_meta( $attribute['term_ids'][0], 'order', 2 );
		update_term_meta( $attribute['term_ids'][1], 'order', 1 );
		$expected = ( new \WC_REST_Product_Attributes_Controller() )
			->prepare_item_for_response( wc_get_attribute_taxonomies()[ 'id:' . $attribute['attribute_id'] ], new \WP_REST_Request() )
			->get_data();

		$result = $this->sut->execute( array( 'include' => array( $attribute['attribute_id'] ) ) );

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result['attributes'] );
		$output = $result['attributes'][0];
		foreach ( $expected as $key => $value ) {
			$this->assertSame( $value, $output[ $key ], $key );
		}
		$this->assertSame( 2, $output['term_count'] );
		$this->assertSame( array( 'Blue', 'Red' ), wp_list_pluck( $output['terms'], 'name' ), 'Terms follow the menu_order of the attribute.' );
		$this->assertSame( 1, $output['terms'][0]['menu_order'] );
		$this->assertSame( 'blue', $output['terms'][0]['slug'] );
	}

	/**
	 * @testdox Should cap the terms of each attribute while still counting all of them.
	 */
	public function test_caps_terms_per_attribute(): void {
		$attribute = WC_Helper_Product::create_attribute( 'Test Size', array( 'S', 'M', 'L' ) );

		$capped = $this->sut->execute(
			array(
				'include'             => array( $attribute['attribute_id'] ),
				'terms_per_attribute' => 2,
			)
		);
		$none   = $this->sut->execute(
			array(
				'include'             => array( $attribute['attribute_id'] ),
				'terms_per_attribute' => 0,
			)
		);

		$this->assertCount( 2, $capped['attributes'][0]['terms'] );
		$this->assertSame( 3, $capped['attributes'][0]['term_count'] );
		$this->assertSame( array(), $none['attributes'][0]['terms'] );
		$this->assertSame( 3, $none['attributes'][0]['term_count'] );
	}

	/**
	 * @testdox Should return the terms as a list when a get_terms filter drops one.
	 */
	public function test_terms_stay_a_list_after_a_filter_drops_one(): void {
		$attribute = WC_Helper_Product::create_attribute( 'Test Fabric', array( 'Wool', 'Silk' ) );
		add_filter(
			'get_terms',
			static function ( $terms ) {
				return is_array( $terms ) ? array_slice( $terms, 1, null, true ) : $terms;
			}
		);

		$result = $this->sut->execute( array( 'include' => array( $attribute['attribute_id'] ) ) );

		$this->assertSame( array( 0 ), array_keys( $result['attributes'][0]['terms'] ), 'The remaining term keeps key 0, so the terms serialize as a JSON list.' );
	}

	/**
	 * @testdox Should filter by search and page through the attributes with a total.
	 */
	public function test_search_and_pagination(): void {
		$ids = array();
		foreach ( array( 'Test Fabric', 'Test Finish', 'Test Pattern' ) as $name ) {
			$ids[] = WC_Helper_Product::create_attribute( $name, array() )['attribute_id'];
		}

		$search  = $this->sut->execute( array( 'search' => 'test fin' ) );
		$by_slug = $this->sut->execute( array( 'search' => 'pa_test-fin' ) );
		$page    = $this->sut->execute(
			array(
				'include'  => $ids,
				'per_page' => 2,
				'page'     => 2,
			)
		);

		$this->assertSame( array( 'Test Finish' ), wp_list_pluck( $search['attributes'], 'name' ) );
		$this->assertSame( 1, $search['total'] );
		$this->assertSame( array( 'Test Finish' ), wp_list_pluck( $by_slug['attributes'], 'name' ), 'The search matches the slug as the output returns it.' );
		$this->assertSame( 3, $page['total'] );
		$this->assertSame( 2, $page['total_pages'] );
		$this->assertSame( array( 'Test Pattern' ), wp_list_pluck( $page['attributes'], 'name' ), 'Attributes are sorted by slug.' );
	}

	/**
	 * @testdox Should return the product_term extension fields on each term, as product-terms-query does, and prime the terms of all attributes once.
	 */
	public function test_term_extension_fields(): void {
		$attribute = WC_Helper_Product::create_attribute( 'Test Swatch', array( 'Red', 'Blue' ) );
		$second    = WC_Helper_Product::create_attribute( 'Test Finish', array( 'Matte' ) );
		$input     = array( 'include' => array( $attribute['attribute_id'], $second['attribute_id'] ) );
		$primed    = array();
		update_option( 'woocommerce_feature_extensible_abilities_enabled', 'yes' );
		$this->enable_extensible_abilities(
			static function () use ( &$primed ) {
				ExtensibleAbilities::register_prime(
					array(
						'resource'  => 'product_term',
						'namespace' => 'test-ext',
						'callback'  => static function ( array $terms ) use ( &$primed ) {
							$primed[] = count( $terms );
						},
					)
				);
				ExtensibleAbilities::register_field(
					array(
						'resource'      => 'product_term',
						'namespace'     => 'test-ext',
						'field'         => 'color',
						'output_schema' => array(
							'type'  => 'string',
							'title' => 'Color',
						),
						'get_callback'  => static function ( \WP_Term $term ) {
							return 'hex-' . $term->slug;
						},
					)
				);
			}
		);
		$ability = new \WP_Ability( ProductAttributesQuery::get_name(), ProductAttributesQuery::get_registration_args() );

		$result = $ability->execute( $input );

		$this->assertIsArray( $result );
		$this->assertSame( array( 3 ), $primed, 'The prime callbacks run once, with the terms of both attributes.' );
		$terms = array_merge( ...array_column( $result['attributes'], 'terms' ) );
		$this->assertCount( 3, $terms );
		foreach ( $terms as $term ) {
			$this->assertSame( 'hex-' . $term['slug'], $term['extensions']['test-ext']['color'] );
		}
		$this->assertSame( 'string', $ability->get_output_schema()['properties']['attributes']['items']['properties']['terms']['items']['properties']['extensions']['properties']['test-ext']['properties']['color']['type'] );
		update_option( 'woocommerce_feature_extensible_abilities_enabled', 'no' );
		$this->assertArrayNotHasKey( 'extensions', $this->sut->execute( $input )['attributes'][0]['terms'][0] );
	}

	/**
	 * @testdox Should accept a call without input.
	 */
	public function test_accepts_no_input(): void {
		$result = $this->sut->execute();

		$this->assertIsArray( $result );
		$this->assertSame( 1, $result['page'] );
	}

	/**
	 * @testdox Should refuse a customer.
	 */
	public function test_refuses_customer(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );

		$result = $this->sut->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
	}
}
