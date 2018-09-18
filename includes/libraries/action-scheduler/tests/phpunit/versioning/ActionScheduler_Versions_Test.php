<?php

/**
 * Class ActionScheduler_Versions_Test
 */
class ActionScheduler_Versions_Test extends ActionScheduler_UnitTestCase {
	public function test_register_version() {
		$versions = new ActionScheduler_Versions();
		$versions->register('1.0-dev', 'callback_1_dot_0_dev');
		$versions->register('1.0', 'callback_1_dot_0');

		$registered = $versions->get_versions();

		$this->assertArrayHasKey( '1.0-dev', $registered );
		$this->assertArrayHasKey( '1.0', $registered );
		$this->assertCount( 2, $registered );

		$this->assertEquals( 'callback_1_dot_0_dev', $registered['1.0-dev'] );
	}

	public function test_duplicate_version() {
		$versions = new ActionScheduler_Versions();
		$versions->register('1.0', 'callback_1_dot_0_a');
		$versions->register('1.0', 'callback_1_dot_0_b');

		$registered = $versions->get_versions();

		$this->assertArrayHasKey( '1.0', $registered );
		$this->assertCount( 1, $registered );
	}

	public function test_latest_version() {
		$versions = new ActionScheduler_Versions();
		$this->assertEquals('__return_null', $versions->latest_version_callback() );
		$versions->register('1.2', 'callback_1_dot_2');
		$versions->register('1.3', 'callback_1_dot_3');
		$versions->register('1.0', 'callback_1_dot_0');

		$this->assertEquals( '1.3', $versions->latest_version() );
		$this->assertEquals( 'callback_1_dot_3', $versions->latest_version_callback() );
	}
}
 