<?php
/**
 * Attribute functions tests
 *
 * @package WooCommerce\Tests\Functions.
 */

use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class WC_Formatting_Functions_Test
 */
class WC_Attribute_Functions_Test extends \WC_Unit_Test_Case {

	/**
	 * Mock object to spy on filter.
	 *
	 * @var MockObject
	 */
	protected $attribute_taxonomies_spy;

	/**
	 * Mock object to spy on filter.
	 *
	 * @var MockObject
	 */
	protected $sanitize_taxonomy_spy;

	/**
	 * Set up.
	 */
	public function setUp() {
		parent::setUp();
		$this->attribute_taxonomies_spy = $this->getMockBuilder( stdClass::class )
			->setMethods( [ '__invoke' ] )
			->getMock();
		$this->sanitize_taxonomy_spy = $this->getMockBuilder( stdClass::class )
			->setMethods( [ '__invoke' ] )
			->getMock();

		add_filter( 'woocommerce_attribute_taxonomies', $this->attribute_taxonomies_spy );
		add_filter( 'sanitize_taxonomy_name', $this->sanitize_taxonomy_spy );
	}

	/**
	 * Tear down.
	 */
	public function tearDown() {
		remove_all_filters( 'woocommerce_attribute_taxonomies' );

		parent::tearDown();
	}

	/**
	 * Test wc_get_attribute_taxonomy_ids() function.
	 * Even empty arrays should be cached.
	 */
	public function test_wc_get_attribute_taxonomy_ids() {
		$this->attribute_taxonomies_spy->expects( $this->once() )
			->method( '__invoke' )
			->will( $this->returnArgument( 0 ) );

		$ids = wc_get_attribute_taxonomy_ids();
		$this->assertEquals( [], $ids );
		$ids = wc_get_attribute_taxonomy_ids();
		$this->assertEquals( [], $ids );
	}

	/**
	 * Test wc_get_attribute_taxonomy_labels() function.
	 * Even empty arrays should be cached.
	 */
	public function test_wc_get_attribute_taxonomy_labels() {
		$this->attribute_taxonomies_spy->expects( $this->once() )
			->method( '__invoke' )
			->will( $this->returnArgument( 0 ) );

		$labels = wc_get_attribute_taxonomy_labels();
		$this->assertEquals( [], $labels );
		$labels = wc_get_attribute_taxonomy_labels();
		$this->assertEquals( [], $labels );
	}

	/**
	 * Test wc_attribute_taxonomy_slug() function.
	 * Even empty strings should be cached.
	 *
	 * @dataProvider get_attribute_names_and_slugs
	 */
	public function test_wc_get_attribute_taxonomy_slug( $name, $expected_slug ) {
		$this->sanitize_taxonomy_spy->expects( $this->once() )
			->method( '__invoke' )
			->will( $this->returnArgument( 0 ) );

		$slug = wc_attribute_taxonomy_slug( $name );
		$this->assertEquals( $expected_slug, $slug );
		$slug = wc_attribute_taxonomy_slug( $name );
		$this->assertEquals( $expected_slug, $slug );
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
