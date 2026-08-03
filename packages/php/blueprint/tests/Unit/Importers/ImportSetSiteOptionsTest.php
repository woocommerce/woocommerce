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
	 * Test that a warning is added when the stored option value differs from the intended value, possibly due to a hook override.
	 *
	 * @return void
	 */
	public function test_process_adds_warn() {
		$schema          = Mockery::mock();
		$schema->options = array(
			'site_name' => 'New Site',
		);

		$import_set_site_options = Mockery::mock( ImportSetSiteOptions::class )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		// Simulate successful update attempt.
		$import_set_site_options->shouldReceive( 'wp_update_option' )
			->with( 'site_name', 'New Site' )
			->andReturn( true );

		// Simulate hook override - return a different value than expected.
		$import_set_site_options->shouldReceive( 'wp_get_option' )
			->with( 'site_name' )
			->andReturn( 'Something Else' );

		$result = $import_set_site_options->process( $schema );

		$this->assertInstanceOf( StepProcessorResult::class, $result );
		$this->assertTrue( $result->is_success() );

		$messages = $result->get_messages( 'warn' );
		$this->assertCount( 1, $messages );
		$this->assertEquals( 'site_name was intended to be set, but the stored value may have been overridden by a hook.', $messages[0]['message'] );
	}

	/**
	 * Test successful update of site options.
	 *
	 * @return void
	 */
	public function test_process_updates_options_successfully() {
		$schema          = Mockery::mock();
		$schema->options = array(
			'site_name' => 'My New Site',
		);

		$import_set_site_options = Mockery::mock( ImportSetSiteOptions::class )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		// Mock `wp_update_option` to return true for successful updates.
		$import_set_site_options->shouldReceive( 'wp_update_option' )
			->with( 'site_name', 'My New Site' )
			->andReturn( true );
		$import_set_site_options->shouldReceive( 'wp_get_option' )
			->with( 'site_name' )
			->andReturn( 'My New Site' );

		$result = $import_set_site_options->process( $schema );

		$this->assertInstanceOf( StepProcessorResult::class, $result );
		$this->assertTrue( $result->is_success() );

		$messages = $result->get_messages( 'info' );
		$this->assertCount( 1, $messages );
		$this->assertEquals( 'site_name has been updated.', $messages[0]['message'] );
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
	 * Restricted options must be matched no matter how the key is cased or padded.
	 *
	 * WordPress trims option names, and option lookups are resolved by MySQL's
	 * case-insensitive collation, so 'wp_USER_roles' and ' siteurl' both land on the
	 * genuine restricted row. A case-sensitive comparison let them through.
	 *
	 * @return void
	 */
	public function test_process_restricted_options_ignores_key_case_and_whitespace() {
		$schema          = Mockery::mock();
		$schema->options = array(
			'wp_USER_roles' => array( 'customer' => array( 'capabilities' => array( 'manage_options' => true ) ) ),
			'ADMIN_EMAIL'   => 'danger@example.com',
			' siteurl'      => 'https://evil.example.com',
		);

		$import_set_site_options = Mockery::mock( ImportSetSiteOptions::class )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		// Any write at all means the restriction was bypassed.
		$import_set_site_options->shouldNotReceive( 'wp_update_option' );

		$result = $import_set_site_options->process( $schema );

		$this->assertTrue( $result->is_success() );

		$messages = $result->get_messages( 'warn' );
		$this->assertCount( 3, $messages );
		$this->assertEquals( "Cannot modify 'wp_USER_roles' option: Modifying is restricted for this key.", $messages[0]['message'] );
		$this->assertEquals( "Cannot modify 'ADMIN_EMAIL' option: Modifying is restricted for this key.", $messages[1]['message'] );
		$this->assertEquals( "Cannot modify ' siteurl' option: Modifying is restricted for this key.", $messages[2]['message'] );

		$this->assertNotEquals( 'danger@example.com', get_option( 'admin_email' ) );
		$this->assertNotEquals( 'https://evil.example.com', get_option( 'siteurl' ) );
	}

	/**
	 * A key that the database resolves to a restricted row must be restricted too.
	 *
	 * Normalising the string covers case and whitespace, but not every equivalence the
	 * collation applies (accent folding, for example), so the resolved name is checked
	 * as well. The resolution itself is stubbed here to keep the assertion independent
	 * of which collation the test database happens to run.
	 *
	 * @return void
	 */
	public function test_process_restricts_keys_that_resolve_to_a_restricted_option() {
		$key = "wp_us\u{00e9}r_roles";

		$schema          = Mockery::mock();
		$schema->options = array( $key => array( 'customer' => array( 'capabilities' => array( 'manage_options' => true ) ) ) );

		$import_set_site_options = Mockery::mock( ImportSetSiteOptions::class )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$import_set_site_options->shouldReceive( 'get_stored_option_name' )
			->with( $key )
			->andReturn( 'wp_user_roles' );
		$import_set_site_options->shouldNotReceive( 'wp_update_option' );

		$result = $import_set_site_options->process( $schema );

		$this->assertTrue( $result->is_success() );

		$messages = $result->get_messages( 'warn' );
		$this->assertCount( 1, $messages );
		$this->assertEquals( "Cannot modify '{$key}' option: Modifying is restricted for this key.", $messages[0]['message'] );
	}

	/**
	 * The database lookup must resolve a key to the option name as actually stored.
	 *
	 * This is the layer that catches equivalences string normalisation cannot model, so
	 * it is asserted against a real database rather than a stub.
	 *
	 * @return void
	 */
	public function test_get_stored_option_name_resolves_key_through_the_database() {
		$import_set_site_options = new ImportSetSiteOptions();

		$method = new \ReflectionMethod( ImportSetSiteOptions::class, 'get_stored_option_name' );
		$method->setAccessible( true );

		// An exact match resolves to itself.
		$this->assertSame( 'admin_email', $method->invoke( $import_set_site_options, 'admin_email' ) );

		// A differently cased key resolves to the stored name, because the lookup runs
		// through the same case-insensitive collation WordPress writes through.
		$this->assertSame( 'admin_email', $method->invoke( $import_set_site_options, 'ADMIN_EMAIL' ) );

		// Surrounding whitespace is trimmed, as WordPress trims it.
		$this->assertSame( 'admin_email', $method->invoke( $import_set_site_options, '  admin_email  ' ) );

		// A key matching no row resolves to null rather than to something restricted.
		$this->assertNull( $method->invoke( $import_set_site_options, 'blueprint_no_such_option_xyz' ) );
	}

	/**
	 * Options that merely contain a restricted name are still writable.
	 *
	 * Guards against the restriction becoming an over-eager substring match.
	 *
	 * @return void
	 */
	public function test_process_allows_options_that_only_resemble_restricted_ones() {
		$schema          = Mockery::mock();
		$schema->options = array( 'my_siteurl_backup' => 'https://example.com' );

		$import_set_site_options = Mockery::mock( ImportSetSiteOptions::class )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$import_set_site_options->shouldReceive( 'wp_update_option' )
			->with( 'my_siteurl_backup', 'https://example.com' )
			->andReturn( true );
		$import_set_site_options->shouldReceive( 'wp_get_option' )
			->with( 'my_siteurl_backup' )
			->andReturn( 'https://example.com' );

		$result = $import_set_site_options->process( $schema );

		$this->assertTrue( $result->is_success() );
		$this->assertCount( 0, $result->get_messages( 'warn' ) );

		$messages = $result->get_messages( 'info' );
		$this->assertCount( 1, $messages );
		$this->assertEquals( 'my_siteurl_backup has been updated.', $messages[0]['message'] );
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
