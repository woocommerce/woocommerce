<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Blueprint\Exporters;

use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportWCPaymentGateways;
use WC_Unit_Test_Case;

/**
 * Tests for the legacy payment gateways Blueprint exporter.
 */
class ExportWCPaymentGatewaysTest extends WC_Unit_Test_Case {
	/**
	 * The System Under Test.
	 *
	 * @var ExportWCPaymentGateways
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new class() extends ExportWCPaymentGateways {
			/**
			 * Return a gateway containing representative settings.
			 *
			 * @return array
			 */
			public function get_wc_payment_gateways() {
				return array(
					'example' => (object) array(
						'settings' => array(
							'enabled' => 'yes',
							'title'   => 'Example gateway',
						),
					),
				);
			}
		};
	}

	/**
	 * @testdox Should not export payment settings.
	 */
	public function test_export_returns_empty_options(): void {
		$export = $this->sut->export()->prepare_json_array();

		$this->assertEquals( (object) array(), $export['options'], 'Payment settings should not be included in Blueprint exports.' );
	}
}
