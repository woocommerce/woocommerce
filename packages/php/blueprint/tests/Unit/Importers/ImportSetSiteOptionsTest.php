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
	 * Test restricted option spelling variants.
	 *
	 * @testdox Restricted options are matched regardless of key case, padding, or database collation.
	 */
	public function test_process_restricted_options_ignores_key_case_and_whitespace(): void {
		$accented_key    = "wp_us\u{00e9}r_roles";
		$schema          = Mockery::mock();
		$schema->options = array(
			'wp_USER_roles' => array( 'customer' => array( 'capabilities' => array( 'manage_options' => true ) ) ),
			'ADMIN_EMAIL'   => 'danger@example.com',
			' siteurl'      => 'https://evil.example.com',
			$accented_key   => array( 'customer' => array( 'capabilities' => array( 'manage_options' => true ) ) ),
		);

		$sut = Mockery::mock( ImportSetSiteOptions::class )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		// Any write at all means the restriction was bypassed.
		$sut->shouldNotReceive( 'wp_update_option' );

		$result = $sut->process( $schema );

		$this->assertTrue( $result->is_success() );

		$messages = $result->get_messages( 'warn' );
		$this->assertCount( 4, $messages );
		$this->assertEquals( "Cannot modify 'wp_USER_roles' option: Modifying is restricted for this key.", $messages[0]['message'] );
		$this->assertEquals( "Cannot modify 'ADMIN_EMAIL' option: Modifying is restricted for this key.", $messages[1]['message'] );
		$this->assertEquals( "Cannot modify ' siteurl' option: Modifying is restricted for this key.", $messages[2]['message'] );
		$this->assertEquals( "Cannot modify '{$accented_key}' option: Modifying is restricted for this key.", $messages[3]['message'] );

		$this->assertNotEquals( 'danger@example.com', get_option( 'admin_email' ) );
		$this->assertNotEquals( 'https://evil.example.com', get_option( 'siteurl' ) );
	}

	/**
	 * Test an accent-equivalent key without a canonical row.
	 *
	 * @testdox Accent-equivalent keys remain restricted when the canonical option row is absent.
	 */
	public function test_process_restricts_collation_equivalent_key_without_canonical_row(): void {
		global $wpdb;

		$canonical_key = 'rewrite_rules';
		$accented_key  = "r\u{00e9}write_rules";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Preserve any equivalent row while testing its absence.
		$original_row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT option_name, option_value, autoload FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				$canonical_key
			),
			ARRAY_A
		);

		delete_option( $canonical_key );
		delete_option( $accented_key );

		try {
			$schema          = Mockery::mock();
			$schema->options = array( $accented_key => array( 'marker' => 'blocked' ) );
			$sut             = new ImportSetSiteOptions();

			$result = $sut->process( $schema );

			$this->assertTrue( $result->is_success() );
			$messages = $result->get_messages( 'warn' );
			$this->assertCount( 1, $messages );
			$this->assertEquals( "Cannot modify '{$accented_key}' option: Modifying is restricted for this key.", $messages[0]['message'] );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Verify the bypass did not create an exact variant row.
			$stored_variant = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT option_name FROM {$wpdb->options} WHERE BINARY option_name = %s LIMIT 1",
					$accented_key
				)
			);
			$this->assertNull( $stored_variant );
		} finally {
			delete_option( $canonical_key );
			delete_option( $accented_key );
			if ( is_array( $original_row ) ) {
				add_option( $original_row['option_name'], maybe_unserialize( $original_row['option_value'] ), '', $original_row['autoload'] );
			}
		}
	}

	/**
	 * Test batching of database collation checks.
	 *
	 * @testdox Multiple imported option names use one database-collation query.
	 */
	public function test_process_batches_database_collation_checks(): void {
		global $wpdb;

		$schema          = Mockery::mock();
		$schema->options = array(
			'blueprint_alpha' => 'one',
			'blueprint_beta'  => 'two',
			'blueprint_gamma' => 'three',
		);
		$sut             = Mockery::mock( ImportSetSiteOptions::class )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$sut->shouldReceive( 'wp_update_option' )->times( 3 )->andReturn( true );
		$sut->shouldReceive( 'wp_get_option' )->with( 'blueprint_alpha' )->andReturn( 'one' );
		$sut->shouldReceive( 'wp_get_option' )->with( 'blueprint_beta' )->andReturn( 'two' );
		$sut->shouldReceive( 'wp_get_option' )->with( 'blueprint_gamma' )->andReturn( 'three' );

		$query_count_before = $wpdb->num_queries;
		$result             = $sut->process( $schema );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( 1, $wpdb->num_queries - $query_count_before );
	}

	/**
	 * Test that similar unrestricted option names remain writable.
	 *
	 * @testdox Options that merely contain a restricted name remain writable.
	 */
	public function test_process_allows_options_that_only_resemble_restricted_ones(): void {
		$schema          = Mockery::mock();
		$schema->options = array( 'my_siteurl_backup' => 'https://example.com' );

		$sut = Mockery::mock( ImportSetSiteOptions::class )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$sut->shouldReceive( 'wp_update_option' )
			->with( 'my_siteurl_backup', 'https://example.com' )
			->andReturn( true );
		$sut->shouldReceive( 'wp_get_option' )
			->with( 'my_siteurl_backup' )
			->andReturn( 'https://example.com' );

		$result = $sut->process( $schema );

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
