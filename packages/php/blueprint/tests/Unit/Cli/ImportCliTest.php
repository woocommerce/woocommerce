<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\Cli;

use Automattic\WooCommerce\Blueprint\Cli\ImportCli;
use Automattic\WooCommerce\Blueprint\Tests\TestCase;

/**
 * Tests for ImportCli.
 */
class ImportCliTest extends TestCase {
	/**
	 * The System Under Test.
	 *
	 * @var ImportCli
	 */
	private $sut;

	/**
	 * Blueprint fixture path.
	 *
	 * @var string
	 */
	private $schema_path;

	/**
	 * Set up the test case.
	 */
	protected function setUp(): void {
		parent::setUp();

		\WP_CLI::$calls    = array();
		$this->schema_path = $this->get_fixture_path( 'empty-steps.json' );
		$this->sut         = new ImportCli( $this->schema_path );
	}

	/**
	 * Test that the command warns and asks for confirmation.
	 *
	 * @testdox Warns and asks for confirmation before importing a Blueprint.
	 */
	public function test_warns_and_confirms_before_importing(): void {
		$this->sut->run( array() );

		$this->assertSame( 'warning', \WP_CLI::$calls[0][0] );
		$this->assertStringContainsString( 'Only import files from a source you trust.', \WP_CLI::$calls[0][1] );
		$this->assertSame( array( 'confirm', 'Do you want to continue?', array() ), \WP_CLI::$calls[1] );
		$this->assertSame( array( 'success', "$this->schema_path imported successfully" ), \WP_CLI::$calls[2] );
	}

	/**
	 * Test that the command warns when confirmation is skipped.
	 *
	 * @testdox Displays the warning and passes the yes flag to confirmation.
	 */
	public function test_warns_when_confirmation_is_skipped(): void {
		$this->sut->run( array( 'yes' => true ) );

		$this->assertSame( 'warning', \WP_CLI::$calls[0][0] );
		$this->assertSame(
			array( 'confirm', 'Do you want to continue?', array( 'yes' => true ) ),
			\WP_CLI::$calls[1]
		);
	}
}
