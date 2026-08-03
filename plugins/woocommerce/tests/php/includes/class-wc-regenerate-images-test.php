<?php
declare( strict_types = 1 );

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

		$property                       = $this->get_regenerate_size_property();
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
	 * @testdox resize_and_return_image() should preserve pre-existing size entries when the requested size fails to regenerate.
	 */
	public function test_resize_and_return_image_preserves_pre_existing_sizes_on_regen_failure() {
		$attachment_id = $this->factory->attachment->create_upload_object(
			DIR_TESTDATA . '/images/canola.jpg'
		);

		$initial_meta = wp_get_attachment_metadata( $attachment_id );
		$this->assertNotEmpty( $initial_meta, 'Initial attachment metadata must exist.' );
		$this->assertIsArray( $initial_meta['sizes'], 'Initial _wp_attachment_metadata must carry sizes.' );
		$this->assertNotEmpty( $initial_meta['sizes'], 'Pre-existing sizes must not be empty before the test runs.' );
		$initial_size_keys = array_keys( $initial_meta['sizes'] );

		// Simulate a scenario where the requested size is removed from the sizes list
		// (e.g. _wp_make_subsizes() hits an editor error and produces nothing).
		// Run with the lowest-possible priority so the callback runs *after* the
		// resize_and_return_image callbacks added at default priority and overrides
		// them to an empty array.
		add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array', PHP_INT_MAX );

		$regenerate_size = $this->get_regenerate_size_property();
		$regenerate_size->setValue( null, 'woocommerce_thumbnail' );

		$method = ( new \ReflectionClass( WC_Regenerate_Images::class ) )
			->getMethod( 'resize_and_return_image' );
		$method->setAccessible( true );

		$image_data = array(
			wp_get_attachment_url( $attachment_id ),
			800,
			600,
		);

		$method->invoke( null, $attachment_id, $image_data, 'woocommerce_thumbnail', false );

		remove_filter( 'intermediate_image_sizes_advanced', '__return_empty_array', PHP_INT_MAX );

		$stored_meta = wp_get_attachment_metadata( $attachment_id );
		$this->assertIsArray( $stored_meta, 'Stored metadata must survive a failed regen.' );
		$this->assertNotEmpty( $stored_meta['sizes'], 'sizes must not be empty after a failed regen — the fix must restore pre-existing sizes.' );

		foreach ( $initial_size_keys as $size_key ) {
			$this->assertArrayHasKey(
				$size_key,
				$stored_meta['sizes'],
				"Size '{$size_key}' must be preserved after a regen failure."
			);
		}
	}
}
