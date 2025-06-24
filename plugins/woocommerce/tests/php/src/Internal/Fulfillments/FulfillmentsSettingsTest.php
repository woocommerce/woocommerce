<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments;

use WC_Unit_Test_Case;

/**
 * Class FulfillmentsSettingsTest
 *
 * Tests for the FulfillmentsSettings class.
 */
class FulfillmentsSettingsTest extends WC_Unit_Test_Case {
	/**
	 * Tests the add_auto_fulfill_settings method.
	 */
	public function test_add_auto_fulfill_settings() {
		$settings = array(
			array(
				'type' => 'sectionend',
				'id'   => 'catalog_options',
			),
		);

		$fulfillments_settings = new \Automattic\WooCommerce\Internal\Fulfillments\FulfillmentsSettings();
		$modified_settings     = $fulfillments_settings->add_auto_fulfill_settings( $settings, '' );

		$this->assertCount( 5, $modified_settings );
		$this->assertEquals( 'catalog_options', $modified_settings[0]['id'] );
		$this->assertEquals( 'sectionend', $modified_settings[0]['type'] );
		$this->assertEquals( 'auto_fulfill_options', $modified_settings[1]['id'] );
		$this->assertEquals( 'title', $modified_settings[1]['type'] );
		$this->assertEquals( 'auto_fulfill_downloadable', $modified_settings[2]['id'] );
		$this->assertEquals( 'checkbox', $modified_settings[2]['type'] );
		$this->assertEquals( 'auto_fulfill_virtual', $modified_settings[3]['id'] );
		$this->assertEquals( 'checkbox', $modified_settings[3]['type'] );
		$this->assertEquals( 'auto_fulfill_options', $modified_settings[4]['id'] );
		$this->assertEquals( 'sectionend', $modified_settings[4]['type'] );
	}
}
