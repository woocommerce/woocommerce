<?php
/**
 * DownloadPermissionsAdjusterTest class file.
 */

namespace Automattic\WooCommerce\Tests\Internal;

use Automattic\WooCommerce\Internal\DownloadPermissionsAdjuster;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper;

/**
 * Tests for DownloadPermissionsAdjuster.
 */
class DownloadPermissionsAdjusterTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var DownloadPermissionsAdjuster
	 */
	private $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp() {
		$this->sut = new DownloadPermissionsAdjuster();
		$this->sut->init();

		// This is needed for "product->set_downloads" to work without actual files.
		add_filter(
			'woocommerce_downloadable_file_allowed_mime_types',
			function() {
				return array( 'foo' => 'nonsense/foo' );
			}
		);
		add_filter(
			'woocommerce_downloadable_file_exists',
			function( $exists, $filename ) {
				return true;
			},
			10,
			2
		);
	}

	/**
	 * @testdox DownloadPermissionsAdjuster class hooks on 'adjust_download_permissions' on initialization.
	 */
	public function test_class_hooks_on_adjust_download_permissions() {
		remove_all_actions( 'adjust_download_permissions' );
		$this->assertFalse( has_action( 'adjust_download_permissions' ) );
		$this->setUp();
		$this->assertTrue( has_action( 'adjust_download_permissions' ) );
	}

	/**
	 * @testdox 'maybe_schedule_adjust_download_permissions' does nothing if the product has no children.
	 */
	public function test_no_adjustment_is_scheduled_if_product_has_no_children() {
		$as_get_scheduled_actions_invoked  = false;
		$as_schedule_single_action_invoked = false;

		$this->register_legacy_proxy_function_mocks(
			array(
				'as_get_scheduled_actions'  => function( $args, $return_format ) use ( &$as_get_scheduled_actions_invoked ) {
					$as_get_scheduled_actions_invoked = true;
				},
				'as_schedule_single_action' => function( $timestamp, $hook, $args ) use ( &$as_schedule_single_action_invoked ) {
					$as_schedule_single_action_invoked = true;
				},
			)
		);

		$product = ProductHelper::create_simple_product();
		$this->sut->maybe_schedule_adjust_download_permissions( $product );

		$this->assertFalse( $as_get_scheduled_actions_invoked );
		$this->assertFalse( $as_schedule_single_action_invoked );
	}

	/**
	 * @testdox 'maybe_schedule_adjust_download_permissions' does nothing if the an adjustment is already pending.
	 */
	public function test_no_adjustment_is_scheduled_if_already_scheduled() {
		$as_get_scheduled_actions_args     = null;
		$as_schedule_single_action_invoked = false;

		$this->register_legacy_proxy_function_mocks(
			array(
				'as_get_scheduled_actions'  => function( $args, $return_format ) use ( &$as_get_scheduled_actions_args ) {
					$as_get_scheduled_actions_args = $args;
					return array( 1 );
				},
				'as_schedule_single_action' => function( $timestamp, $hook, $args ) use ( &$as_schedule_single_action_invoked ) {
					$as_schedule_single_action_invoked = true;
				},
			)
		);

		$product = ProductHelper::create_variation_product();
		$this->sut->maybe_schedule_adjust_download_permissions( $product );

		$expected_get_scheduled_actions_args = array(
			'hook'   => 'adjust_download_permissions',
			'args'   => array( $product->get_id() ),
			'status' => \ActionScheduler_Store::STATUS_PENDING,
		);
		$this->assertEquals( $expected_get_scheduled_actions_args, $as_get_scheduled_actions_args );
		$this->assertFalse( $as_schedule_single_action_invoked );
	}

	/**
	 * @testdox 'maybe_schedule_adjust_download_permissions' schedules an adjustment if not scheduled already.
	 */
	public function test_no_adjustment_is_scheduled_if_not_yet_scheduled() {
		$as_get_scheduled_actions_args  = null;
		$as_schedule_single_action_args = null;

		$this->register_legacy_proxy_function_mocks(
			array(
				'as_get_scheduled_actions'  => function( $params, $return_format ) use ( &$as_get_scheduled_actions_args ) {
					$as_get_scheduled_actions_args = $params;
					return array();
				},
				'as_schedule_single_action' => function( $timestamp, $hook, $args ) use ( &$as_schedule_single_action_args ) {
					$as_schedule_single_action_args = array( $timestamp, $hook, $args );
				},
				'time'                      => function() {
					return 0; },
			)
		);

		$product = ProductHelper::create_variation_product();
		$this->sut->maybe_schedule_adjust_download_permissions( $product );

		$expected_get_scheduled_actions_args = array(
			'hook'   => 'adjust_download_permissions',
			'args'   => array( $product->get_id() ),
			'status' => \ActionScheduler_Store::STATUS_PENDING,
		);
		$this->assertEquals( $expected_get_scheduled_actions_args, $as_get_scheduled_actions_args );

		$expected_as_schedule_single_action_args = array(
			1,
			'adjust_download_permissions',
			array( $product->get_id() ),
		);
		$this->assertEquals( $expected_as_schedule_single_action_args, $as_schedule_single_action_args );
	}

	/**
	 * @testdox 'adjust_download_permissions' creates child download permissions when they are missing (see method comment for details).
	 */
	public function test_adjust_download_permissions_creates_additional_permissions_if_not_exist() {
		$download = array(
			'name' => 'the_file',
			'file' => 'the_file.foo',
		);

		$product = ProductHelper::create_variation_product();
		$product->set_downloads( array( $download ) );
		$product->save();
		$parent_download_id = current( $product->get_downloads() )->get_id();

		$child = wc_get_product( current( $product->get_children() ) );
		$child->set_downloads( array( $download ) );
		$child->save();
		$child_download_id = current( $child->get_downloads() )->get_id();

		$data_for_data_store =
			array(
				$product->get_id() =>
				array(
					(object) array(
						'data' => array(
							'download_id'         => $parent_download_id,
							'user_id'             => 1234,
							'order_id'            => 5678,
							'downloads_remaining' => 34,
							'access_granted'      => '2000-01-01',
							'access_expires'      => '2034-02-27',
						),
					),
				),
			);

		$data_store = $this->create_mock_data_store( $data_for_data_store );

		$this->setUp();
		$this->sut->adjust_download_permissions( $product->get_id() );

		$expected_created_data = array(
			'download_id'         => $child_download_id,
			'user_id'             => 1234,
			'order_id'            => 5678,
			'product_id'          => $child->get_id(),
			'downloads_remaining' => 34,
			'access_granted'      => '2000-01-01',
			'access_expires'      => '2034-02-27',
		);

		$this->assertEquals( $expected_created_data, $data_store->created_data );
	}

	/**
	 * @testdox 'adjust_download_permissions' doesn't create child download permissions that already exist.
	 */
	public function test_adjust_download_permissions_dont_create_additional_permissions_if_already_exists() {
		$download = array(
			'name' => 'the_file',
			'file' => 'the_file.foo',
		);

		$product = ProductHelper::create_variation_product();
		$product->set_downloads( array( $download ) );
		$product->save();
		$parent_download_id = current( $product->get_downloads() )->get_id();

		$child = wc_get_product( current( $product->get_children() ) );
		$child->set_downloads( array( $download ) );
		$child->save();
		$child_download_id = current( $child->get_downloads() )->get_id();

		$data_for_data_store =
			array(
				$product->get_id() =>
					array(
						(object) array(
							'data' => array(
								'download_id' => $parent_download_id,
								'user_id'     => 1234,
								'order_id'    => 5678,
							),
						),
					),
				$child->get_id()   =>
					array(
						(object) array(
							'data' => array(
								'download_id' => $child_download_id,
								'user_id'     => 1234,
								'order_id'    => 5678,
							),
						),
					),
			);

		$data_store = $this->create_mock_data_store( $data_for_data_store );

		$this->setUp();
		$this->sut->adjust_download_permissions( $product->get_id() );

		$this->assertEmpty( $data_store->created_data );
	}

	/**
	 * @testdox 'adjust_download_permissions' creates child download permissions when one exists but for a different order or customer id.
	 *
	 * @testWith [9999, 5678]
	 *           [1234, 9999]
	 * @param int $user_id User id the child download permission exists for.
	 * @param int $order_id Order id the child download permission exists for.
	 */
	public function test_adjust_download_permissions_creates_additional_permissions_if_exists_but_not_matching( $user_id, $order_id ) {
		$download = array(
			'name' => 'the_file',
			'file' => 'the_file.foo',
		);

		$product = ProductHelper::create_variation_product();
		$product->set_downloads( array( $download ) );
		$product->save();
		$parent_download_id = current( $product->get_downloads() )->get_id();

		$child = wc_get_product( current( $product->get_children() ) );
		$child->set_downloads( array( $download ) );
		$child->save();
		$child_download_id = current( $child->get_downloads() )->get_id();

		$data_for_data_store =
			array(
				$product->get_id() =>
					array(
						(object) array(
							'data' => array(
								'download_id' => $parent_download_id,
								'user_id'     => 1234,
								'order_id'    => 5678,
							),
						),
					),
				$child->get_id()   =>
					array(
						(object) array(
							'data' => array(
								'download_id' => $child_download_id,
								'user_id'     => $user_id,
								'order_id'    => $order_id,
							),
						),
					),
			);

		$data_store = $this->create_mock_data_store( $data_for_data_store );

		$this->setUp();
		$this->sut->adjust_download_permissions( $product->get_id() );

		$expected = array(
			'download_id' => $child_download_id,
			'user_id'     => 1234,
			'order_id'    => 5678,
			'product_id'  => $child->get_id(),
		);

		$this->assertEquals( $expected, $data_store->created_data );
	}

	/**
	 * Create and register a mock customer downloads data store.
	 *
	 * @param array $data An array where keys are product ids, and values are what 'get_downloads' will return for that input.
	 * @return object An object that mocks the customer downloads data store.
	 */
	private function create_mock_data_store( $data ) {
		// phpcs:disable Squiz.Commenting
		$data_store = new class($data) {
			private $data;
			public $created_data = null;

			public function __construct( $data ) {
				$this->data = $data;
			}

			public function get_downloads( $params ) {
				if ( array_key_exists( $params['product_id'], $this->data ) ) {
					return $this->data[ $params['product_id'] ];
				} else {
					return array();
				}
			}

			public function create_from_data( $data ) {
				$this->created_data = $data;
			}
		};
		// phpcs:enable Squiz.Commenting

		$this->register_legacy_proxy_class_mocks(
			array(
				'WC_Data_Store' => $data_store,
			)
		);

		return $data_store;
	}
}
