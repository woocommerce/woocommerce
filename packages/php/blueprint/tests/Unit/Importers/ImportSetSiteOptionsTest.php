<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\Importers;

use Automattic\WooCommerce\Blueprint\Importers\ImportSetSiteOptions;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test the ImportSetSiteOptions class.
 *
 * @package Automattic\WooCommerce\Blueprint\Tests\Unit\Importers
 */
class ImportSetSiteOptionsTest extends TestCase {
	/**
	 * Tear down the test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Test successful update of site options.
	 *
	 * @return void
	 */
	public function test_process_updates_options_successfully() {
		$schema          = Mockery::mock();
		$schema->options = array(
			'site_name'                   => 'My New Site',
			'woocommerce_default_country' => 'JP',
		);

		$import_set_site_options = Mockery::mock( ImportSetSiteOptions::class )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		// Mock `wp_update_option` to return true for successful updates.
		$import_set_site_options->shouldReceive( 'wp_update_option' )
			->with( 'site_name', 'My New Site' )
			->andReturn( true );
		$import_set_site_options->shouldReceive( 'wp_update_option' )
			->with( 'woocommerce_default_country', 'JP' )
			->andReturn( true );

		$result = $import_set_site_options->process( $schema );

		$this->assertInstanceOf( StepProcessorResult::class, $result );
		$this->assertTrue( $result->is_success() );

		$messages = $result->get_messages( 'info' );
		$this->assertCount( 2, $messages );
		$this->assertEquals( 'site_name has been updated.', $messages[0]['message'] );
		$this->assertEquals( 'woocommerce_default_country has been updated.', $messages[1]['message'] );
	}

	/**
	 * Test when option value is already up to date.
	 *
	 * @return void
	 */
	public function test_process_option_already_up_to_date() {
		$schema          = Mockery::mock();
		$schema->options = array(
			'site_name' => 'Existing Site',
		);

		$import_set_site_options = Mockery::mock( ImportSetSiteOptions::class )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		// Mock `wp_update_option` to return false.
		$import_set_site_options->shouldReceive( 'wp_update_option' )
			->with( 'site_name', 'Existing Site' )
			->andReturn( false );

		// Mock `wp_get_option` to return the same value.
		$import_set_site_options->shouldReceive( 'wp_get_option' )
			->with( 'site_name' )
			->andReturn( 'Existing Site' );

		$result = $import_set_site_options->process( $schema );

		$this->assertInstanceOf( StepProcessorResult::class, $result );
		$this->assertTrue( $result->is_success() );

		$messages = $result->get_messages( 'info' );
		$this->assertCount( 1, $messages );
		$this->assertEquals( 'site_name has not been updated because the current value is already up to date.', $messages[0]['message'] );
	}

	/**
	 * Test when restricted options are attempted to be updated.
	 *
	 * @return void
	 */
	public function test_process_restricted_options() {
		$schema                  = Mockery::mock();
		$schema->options         = array(
			'admin_email'    => 'danger@example.com',
			'active_plugins' => array( 'fake-plugin/fake-plugin.php' ),
		);
		$import_set_site_options = Mockery::mock( ImportSetSiteOptions::class )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$result = $import_set_site_options->process( $schema );

		$this->assertInstanceOf( StepProcessorResult::class, $result );
		$this->assertTrue( $result->is_success() );

		$messages = $result->get_messages( 'warn' );
		$this->assertCount( 2, $messages );
		$this->assertEquals( "Cannot modify 'admin_email' option: Modifying is restricted for this key.", $messages[0]['message'] );
		$this->assertEquals( "Cannot modify 'active_plugins' option: Modifying is restricted for this key.", $messages[1]['message'] );
		$this->assertNotEquals( get_option( 'admin_email' ), 'danger@example.com' );
		$this->assertNotEquals( get_option( 'active_plugins' ), array( 'fake-plugin/fake-plugin.php' ) );
	}

	/**
	 * Test getting the step class.
	 *
	 * @return void
	 */
	public function test_get_step_class() {
		$import_set_site_options = new ImportSetSiteOptions();

		$this->assertEquals( SetSiteOptions::class, $import_set_site_options->get_step_class() );
	}
}
