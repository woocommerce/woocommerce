<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\RestApi\Routes\V4\Orders;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\UpdateUtils;
use Automattic\WooCommerce\Tests\Helpers\MetaDataAssertionTrait;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the UpdateUtils class.
 */
class UpdateUtilsTest extends WC_Unit_Test_Case {
	use MetaDataAssertionTrait;

	/**
	 * The System Under Test.
	 *
	 * @var TestableUpdateUtils
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new TestableUpdateUtils();
	}

	/**
	 * @testdox Should handle incomplete meta_data entries without errors.
	 */
	public function test_update_meta_data_with_incomplete_entries(): void {
		$order = wc_create_order();

		$this->sut->call_update_meta_data( $order, $this->get_incomplete_meta_data_input() );

		$this->assert_incomplete_meta_data_handled_correctly( $order );
	}
}

/**
 * Testable subclass that exposes the protected update_meta_data method.
 *
 * phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound
 * phpcs:disable Squiz.Classes.ClassFileName.NoMatch
 * phpcs:disable Suin.Classes.PSR4.IncorrectClassName
 */
class TestableUpdateUtils extends UpdateUtils {

	/**
	 * Public wrapper for the protected update_meta_data method.
	 *
	 * @param WC_Order $order     Order object.
	 * @param array    $meta_data Meta data array.
	 */
	public function call_update_meta_data( WC_Order $order, array $meta_data ) {
		$this->update_meta_data( $order, $meta_data );
	}
}
