<?php
/**
 * Product attributes query ability tests.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Internal\Abilities\Ability;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductAttributesQuery;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests global attribute reads through the registered ability.
 */
class ProductAttributesQueryTest extends WC_Unit_Test_Case {

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
	private static $user_id;

	/**
	 * Create the fixtures that no test changes beyond its own transaction.
	 *
	 * @param \WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$user_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Set up the ability and an administrator.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new Ability( ProductAttributesQuery::get_name(), ProductAttributesQuery::get_registration_args() );
		wp_set_current_user( self::$user_id );
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
