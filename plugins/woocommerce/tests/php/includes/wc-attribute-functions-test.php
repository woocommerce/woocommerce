<?php
/**
 * Attribute functions tests
 *
 * @package WooCommerce\Tests\Functions.
 */

use \PHPUnit\Framework\MockObject\Matcher\InvokedRecorder;

/**
 * Class WC_Formatting_Functions_Test
 */
class WC_Attribute_Functions_Test extends \WC_Unit_Test_Case {

	/**
	 * Mock object to spy on filter.
	 *
	 * @var InvokedRecorder
	 */
	protected $filter_recorder;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();

		// Tests will use this to verify the correct call count.
		$this->filter_recorder = $this->any();

		$filter_mock = $this->getMockBuilder( stdClass::class )
			->setMethods( [ '__invoke' ] )
			->getMock();
		$filter_mock->expects( $this->filter_recorder )
			->method( '__invoke' )
			->will( $this->returnArgument( 0 ) );

		add_filter( 'woocommerce_attribute_taxonomies', $filter_mock );
		add_filter( 'sanitize_taxonomy_name', $filter_mock );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_attribute_taxonomies' );
		remove_all_filters( 'sanitize_taxonomy_name' );

		parent::tearDown();
	}

	/**
	 * Test wc_get_attribute_taxonomy_ids() function.
	 * Even empty arrays should be cached.
	 */
	public function test_wc_get_attribute_taxonomy_ids() {
		$ids = wc_get_attribute_taxonomy_ids();
		$this->assertEquals( [], $ids );
		$this->assertEquals(
			1,
			$this->filter_recorder->getInvocationCount(),
			'Filter `woocommerce_attribute_taxonomies` should have been triggered once after fetching all attribute taxonomies.'
		);
		$ids = wc_get_attribute_taxonomy_ids();
		$this->assertEquals( [], $ids );
		$this->assertEquals(
			1,
			$this->filter_recorder->getInvocationCount(),
			'Filter `woocommerce_attribute_taxonomies` should not be triggered a second time because the results should be loaded from the cache.'
		);
	}

	/**
	 * Test wc_get_attribute_taxonomy_labels() function.
	 * Even empty arrays should be cached.
	 */
	public function test_wc_get_attribute_taxonomy_labels() {
		$labels = wc_get_attribute_taxonomy_labels();
		$this->assertEquals( [], $labels );
		$this->assertEquals(
			1,
			$this->filter_recorder->getInvocationCount(),
			'Filter `woocommerce_attribute_taxonomies` should have been triggered once after fetching all attribute taxonomies.'
		);
		$labels = wc_get_attribute_taxonomy_labels();
		$this->assertEquals( [], $labels );
		$this->assertEquals(
			1,
			$this->filter_recorder->getInvocationCount(),
			'Filter `woocommerce_attribute_taxonomies` should not be triggered a second time because the results should be loaded from the cache.'
		);
	}

	/**
	 * Test wc_attribute_taxonomy_slug() function.
	 * Even empty strings should be cached.
	 *
	 * @dataProvider get_attribute_names_and_slugs
	 */
	public function test_wc_get_attribute_taxonomy_slug( $name, $expected_slug ) {
		$slug = wc_attribute_taxonomy_slug( $name );
		$this->assertEquals( $expected_slug, $slug );
		$this->assertEquals(
			1,
			$this->filter_recorder->getInvocationCount(),
			'Filter `sanitize_taxonomy_name` should have been triggered once.'
		);
		$slug = wc_attribute_taxonomy_slug( $name );
		$this->assertEquals( $expected_slug, $slug );
		$this->assertEquals(
			1,
			$this->filter_recorder->getInvocationCount(),
			'Filter `sanitize_taxonomy_name` should not be triggered a second time because the slug should be loaded from the cache.'
		);
	}

	/**
	 * Test wc_create_attribute() function.
	 */
	public function test_wc_create_attribute() {
		$ids = array();

		$ids[] = wc_create_attribute( array( 'name' => 'Brand' ) );
		$this->assertIsInt(
			end( $ids ),
			'wc_create_attribute should return a numeric id on success.'
		);

		$ids[] = wc_create_attribute( array( 'name' => str_repeat( 'n', 28 ) ) );
		$this->assertIsInt(
			end( $ids ),
			'Attribute creation should succeed when its slug is 28 characters long.'
		);

		$err = wc_create_attribute( array() );
		$this->assertEquals(
			'missing_attribute_name',
			$err->get_error_code(),
			'Attributes should not be allowed to be created without specifying a name.'
		);

		$err = wc_create_attribute( array( 'name' => str_repeat( 'n', 29 ) ) );
		$this->assertEquals(
			'invalid_product_attribute_slug_too_long',
			$err->get_error_code(),
			'Attribute slugs should not be allowed to be over 28 characters long.'
		);

		$err = wc_create_attribute( array( 'name' => 'Cat' ) );
		$this->assertEquals(
			'invalid_product_attribute_slug_reserved_name',
			$err->get_error_code(),
			'Attributes should not be allowed to be created with reserved names.'
		);

		register_taxonomy( 'pa_brand', array( 'product' ), array( 'labels' => array( 'name' => 'Brand' ) ) );
		$err = wc_create_attribute( array( 'name' => 'Brand' ) );
		$this->assertEquals(
			'invalid_product_attribute_slug_already_exists',
			$err->get_error_code(),
			'Duplicate attribute slugs should not be allowed to exist.'
		);
		unregister_taxonomy( 'pa_brand' );

		foreach ( $ids as $id ) {
			wc_delete_attribute( $id );
		}
	}

	public function get_attribute_names_and_slugs() {
		return [
			[ 'Dash Me', 'dash-me' ],
			[ '', '' ],
			[ 'pa_SubStr', 'substr' ],
			[ 'ĂnîC°Dę', 'anicde' ],
		];
	}
}
