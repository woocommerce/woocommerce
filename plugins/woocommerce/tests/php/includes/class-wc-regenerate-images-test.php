<?php
/**
 * Class WC_Regenerate_Images_Test file.
 *
 * @package WooCommerce\Tests\WC_Regenerate_Images.
 */

/**
 * Class WC_Regenerate_Images_Test.
 */
class WC_Regenerate_Images_Test extends \WC_Unit_Test_Case {

	/**
	 * Original value of WC_Regenerate_Images::$regenerate_size, restored in tearDown().
	 *
	 * @var string
	 */
	private $original_regenerate_size;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$property = $this->get_regenerate_size_property();
		$this->original_regenerate_size = $property->getValue();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->get_regenerate_size_property()->setValue( null, $this->original_regenerate_size );

		parent::tearDown();
	}

	/**
	 * Get accessible reflection access to the private static WC_Regenerate_Images::$regenerate_size property.
	 *
	 * @return ReflectionProperty
	 */
	private function get_regenerate_size_property() {
		$property = ( new ReflectionClass( WC_Regenerate_Images::class ) )->getProperty( 'regenerate_size' );
		$property->setAccessible( true );

		return $property;
	}

	/**
	 * @testdox adjust_intermediate_image_sizes_advanced() should return only the size being regenerated.
	 */
	public function test_adjust_intermediate_image_sizes_advanced_keeps_only_requested_size() {
		$regenerate_size = $this->get_regenerate_size_property();
		$regenerate_size->setValue( null, 'woocommerce_thumbnail' );

		$registered_sizes = array(
			'thumbnail'             => array(
				'width'  => 150,
				'height' => 150,
				'crop'   => true,
			),
			'woocommerce_thumbnail' => array(
				'width'  => 300,
				'height' => 300,
				'crop'   => true,
			),
			'woocommerce_single'    => array(
				'width'  => 600,
				'height' => 600,
				'crop'   => false,
			),
		);

		$result = WC_Regenerate_Images::adjust_intermediate_image_sizes_advanced( $registered_sizes );

		$this->assertSame(
			array( 'woocommerce_thumbnail' => $registered_sizes['woocommerce_thumbnail'] ),
			$result,
			'Only the size being regenerated should remain, so core does not rebuild every registered size.'
		);
	}

	/**
	 * @testdox adjust_intermediate_image_sizes_advanced() should return no sizes when the requested size is not registered.
	 */
	public function test_adjust_intermediate_image_sizes_advanced_handles_unregistered_size() {
		$regenerate_size = $this->get_regenerate_size_property();
		$regenerate_size->setValue( null, 'woocommerce_gallery_thumbnail' );

		$registered_sizes = array(
			'thumbnail' => array(
				'width'  => 150,
				'height' => 150,
				'crop'   => true,
			),
		);

		$result = WC_Regenerate_Images::adjust_intermediate_image_sizes_advanced( $registered_sizes );

		$this->assertSame( array(), $result, 'No sizes should be regenerated when the requested size is not registered.' );
	}

	/**
	 * @testdox The intermediate_image_sizes_advanced filter should stop wp_generate_attachment_metadata() from regenerating every registered size.
	 */
	public function test_advanced_filter_restricts_wp_generate_attachment_metadata_to_one_size() {
		$attachment_id = $this->factory->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$fullsizepath  = get_attached_file( $attachment_id );

		$regenerate_size = $this->get_regenerate_size_property();
		$regenerate_size->setValue( null, 'woocommerce_thumbnail' );

		add_filter( 'intermediate_image_sizes_advanced', array( 'WC_Regenerate_Images', 'adjust_intermediate_image_sizes_advanced' ) );
		$metadata = wp_generate_attachment_metadata( $attachment_id, $fullsizepath );
		remove_filter( 'intermediate_image_sizes_advanced', array( 'WC_Regenerate_Images', 'adjust_intermediate_image_sizes_advanced' ) );

		$this->assertSame(
			array( 'woocommerce_thumbnail' ),
			array_keys( $metadata['sizes'] ),
			'Without this filter, core regenerates every registered size (thumbnail, medium, large, ...) instead of just the one requested.'
		);
	}
}
