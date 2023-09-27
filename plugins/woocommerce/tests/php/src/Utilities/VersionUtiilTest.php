<?php

namespace Automattic\WooCommerce\Tests\Utilities;

use Automattic\WooCommerce\Utilities\VersionUtil;
use WC_Unit_Test_Case;

/**
 * Class VersionUtiilTest
 *
 * @since x.x.x
 */
class VersionUtiilTest extends WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var VersionUtil
	 */
	private $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->reset_container_resolutions();
		$this->reset_legacy_proxy_mocks();

		$this->sut = $this->get_instance_of( VersionUtil::class );
	}

	public function test_wp_version_at_least() {
		// Store the current wp_version
		$original_wp_version = $GLOBALS['wp_version'];

		// Set a fake wp_version to test.
		$GLOBALS['wp_version'] = '5.6.0';

		$this->assertFalse( $this->sut->wp_version_at_least( '5.6.1' ) );
		$this->assertFalse( $this->sut->wp_version_at_least( '6.3' ) );
		$this->assertTrue( $this->sut->wp_version_at_least( '5.6' ) );
		$this->assertTrue( $this->sut->wp_version_at_least( '5.5' ) );

		// Restore the original wp_version
		$GLOBALS['wp_version'] = $original_wp_version;
	}
}
