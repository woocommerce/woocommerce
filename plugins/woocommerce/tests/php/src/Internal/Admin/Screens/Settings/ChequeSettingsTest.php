<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Screens\Settings;

use Automattic\WooCommerce\Internal\Admin\Screens\Settings\ChequeSettings;
use WC_Unit_Test_Case;

/**
 * Tests for the ChequeSettings class.
 */
class ChequeSettingsTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ChequeSettings
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new ChequeSettings();
	}

	/**
	 * @testdox Should add the cheque form layout when the screen's flag is on.
	 */
	public function test_adds_form_layout_when_flag_is_on(): void {
		add_filter(
			'woocommerce_admin_features',
			static function ( $features ) {
				$features[] = 'experimental-admin-screens';
				return $features;
			}
		);
		$config = $this->get_config();

		$result = $this->sut->handle_get_entity_view_config_woo_settings_payments_cheque( $config );

		$this->assertSame( $config, $result, 'The filter should return the View Config object it received' );
		$this->assertSame(
			array(
				array(
					array(
						'form' => array(
							'fields' => array(
								array(
									'id'       => 'general',
									'label'    => 'General',
									'layout'   => array(
										'type'          => 'card',
										'isCollapsible' => false,
									),
									'children' => array( 'enabled', 'title', 'description', 'instructions' ),
								),
							),
						),
					),
					1,
				),
			),
			$config->merged,
			'The cheque fields should be merged into the View Config as one card'
		);
	}

	/**
	 * @testdox Should leave the View Config unchanged when the screen's flag is off.
	 */
	public function test_leaves_config_unchanged_when_flag_is_off(): void {
		add_filter(
			'woocommerce_admin_features',
			static function ( $features ) {
				return array_values( array_diff( $features, array( 'experimental-admin-screens' ) ) );
			}
		);
		$config = $this->get_config();

		$this->sut->handle_get_entity_view_config_woo_settings_payments_cheque( $config );

		$this->assertSame( array(), $config->merged, 'Nothing should be merged while the flag is off' );
	}

	/**
	 * A View Config stand-in that records merges.
	 *
	 * @return object
	 */
	private function get_config() {
		return new class() {
			/**
			 * Arguments passed to each merge() call.
			 *
			 * @var array
			 */
			public array $merged = array();

			/**
			 * Record a merge.
			 *
			 * @param array $config   Partial config.
			 * @param int   $priority Merge priority.
			 * @return self
			 */
			public function merge( array $config, int $priority ): self {
				$this->merged[] = array( $config, $priority );
				return $this;
			}
		};
	}
}
