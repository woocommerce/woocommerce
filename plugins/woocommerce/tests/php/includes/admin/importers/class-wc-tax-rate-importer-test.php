<?php
/**
 * Tests for the WC_Tax_Rate_Importer class.
 *
 * @package WooCommerce\Tests\Admin\Importers
 */

declare( strict_types = 1 );

require_once ABSPATH . 'wp-admin/includes/class-wp-importer.php';
require_once WC_ABSPATH . 'includes/admin/importers/class-wc-tax-rate-importer.php';

/**
 * Tests for the WC_Tax_Rate_Importer class.
 */
class WC_Tax_Rate_Importer_Test extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Tax_Rate_Importer
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new WC_Tax_Rate_Importer();
	}

	/**
	 * @testdox Should return UTF-8 data unchanged and convert ISO-8859-1 data to UTF-8.
	 */
	public function test_format_data_from_csv_converts_latin1_to_utf8(): void {
		$this->assertSame( 'Café', $this->sut->format_data_from_csv( 'Café', 'UTF-8' ), 'UTF-8 data should not change.' );
		$this->assertSame( 'Café', $this->sut->format_data_from_csv( "Caf\xE9", 'ISO-8859-1' ), 'ISO-8859-1 data should be converted.' );
	}

	/**
	 * @testdox Should convert ISO-8859-1 bytes to UTF-8 when mbstring is not available.
	 */
	public function test_latin1_fallback_converts_bytes_to_utf8(): void {
		$method = new ReflectionMethod( WC_Tax_Rate_Importer::class, 'latin1_to_utf8' );
		$method->setAccessible( true );

		$this->assertSame( "A\x7F\xC2\x80\xC2\xA0\xC3\xA9\xC3\xBF", $method->invoke( null, "A\x7F\x80\xA0\xE9\xFF" ) );
	}
}
