<?php

/**
 * Class WC_Tracks_Test.
 */
class WC_Tracks_Test extends \WC_Unit_Test_Case {

	/**
	 * Set up test
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		include_once WC_ABSPATH . 'includes/tracks/class-wc-tracks.php';
		include_once WC_ABSPATH . 'includes/tracks/class-wc-tracks-client.php';
	}

	/**
	 * Test that custom event properties are returned when passed.
	 */
	public function test_get_properties() {
		$properties = \WC_Tracks::get_properties(
			'test_event',
			array(
				'test_property' => 5,
			)
		);
		$this->assertContains( 'test_property', array_keys( $properties ) );
		$this->assertEquals( 5, $properties['test_property'] );
	}


	/**
	 * Test that identity properties are added to the properties.
	 */
	public function test_addition_of_identity() {
		$properties = \WC_Tracks::get_properties(
			'test_event',
			array(
				'test_property' => 5,
			)
		);
		$this->assertContains( '_ui', array_keys( $properties ) );
		$this->assertContains( '_ut', array_keys( $properties ) );
	}

	/**
	 * Test that custom identity properties cannot be added.
	 */
	public function test_invalid_identity() {
		$properties = \WC_Tracks::get_properties(
			'test_event',
			array(
				'_ui' => 'bad',
				'_ut' => 'bad',
			)
		);
		$this->assertNotEquals( 'bad', $properties['_ui'] );
		$this->assertNotEquals( 'bad', $properties['_ut'] );
	}

}
