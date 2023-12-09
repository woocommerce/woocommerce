<?php
namespace Automattic\WooCommerce\Blocks\Tests\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\MiniCartUtils;

/**
 * Tests for the MiniCartUtils class
 *
 * @since $VID:$
 */
class MiniCartUtilsTest extends \WP_UnitTestCase {
	/**
	 * We ensure old attributes are migrated.
	 */
	public function test_migrate_attributes_to_color_panel() {
		$mock_attributes     = array(
			'priceColorValue'        => '#9b51e0',
			'iconColorValue'         => '#fcb900',
			'productCountColorValue' => '#000000',
		);
		$expected_attributes = array(
			'priceColor'        => array(
				'color' => '#9b51e0',
			),
			'iconColor'         => array(
				'color' => '#fcb900',
			),
			'productCountColor' => array(
				'color' => '#000000',
			),
		);

		$this->assertEquals( $expected_attributes, MiniCartUtils::migrate_attributes_to_color_panel( $mock_attributes ) );
	}
}
