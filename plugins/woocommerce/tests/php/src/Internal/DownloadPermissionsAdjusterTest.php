<?php
/**
 * DownloadPermissionsAdjusterTest class file.
 */

namespace Automattic\WooCommerce\Tests\Internal;

use Automattic\WooCommerce\Internal\DownloadPermissionsAdjuster;
use Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Register as Download_Directories;
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
	public function setUp(): void {
		parent::setUp();
		$this->initialize_subject();

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

		// In these tests, we are not directly concerned with Approved Download Directory functionality.
		wc_get_container()->get( Download_Directories::class )->set_mode( Download_Directories::MODE_DISABLED );
	}

	/**
	 * Reset non-transactional test doubles and option caches.
	 */
	public function tearDown(): void {
		try {
			$this->reset_legacy_proxy_mocks();
		} finally {
			try {
				parent::tearDown();
			} finally {
				wp_cache_delete( 'wc_downloads_approved_directories_mode', 'options' );
				wp_cache_delete( 'alloptions', 'options' );
				wp_cache_delete( 'notoptions', 'options' );
			}
		}
	}

	/**
	 * Initialize the subject after test doubles have been registered.
	 */
	private function initialize_subject(): void {
		$this->sut = new DownloadPermissionsAdjuster();
		$this->sut->init();
	}

	/**
	 * @testdox DownloadPermissionsAdjuster class hooks on 'adjust_download_permissions' on initialization.
	 */
	public function test_class_hooks_on_adjust_download_permissions() {
		remove_all_actions( 'adjust_download_permissions' );
		$this->assertFalse( has_action( 'adjust_download_permissions' ) );
		$this->initialize_subject();
		$this->assertTrue( has_action( 'adjust_download_permissions' ) );
	}

	/**
	 * @testdox 'maybe_schedule_adjust_download_permissions' does nothing if the product has no children.
	 */
	public function test_no_adjustment_is_scheduled_if_product_has_no_children() {
		$product = ProductHelper::create_simple_product();
		$this->sut->maybe_schedule_adjust_download_permissions( $product );

		$this->assertSame( array(), $this->pending_adjustments_for( $product->get_id() ) );
	}

	/**
	 * @testdox 'maybe_schedule_adjust_download_permissions' does nothing if the an adjustment is already pending.
	 */
	public function test_no_adjustment_is_scheduled_if_already_scheduled() {
		$product  = $this->create_downloadable_variation_product();
		$existing = as_schedule_single_action( time() + HOUR_IN_SECONDS, 'adjust_download_permissions', array( $product->get_id() ) );

		$this->sut->maybe_schedule_adjust_download_permissions( $product );

		$this->assertEquals( array( $existing ), $this->pending_adjustments_for( $product->get_id() ), 'A pending adjustment blocks a second one' );
	}

	/**
	 * @testdox 'maybe_schedule_adjust_download_permissions' schedules an adjustment if not scheduled already.
	 */
	public function test_no_adjustment_is_scheduled_if_not_yet_scheduled() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'time' => function () {
					return 0;
				},
			)
		);

		$product = $this->create_downloadable_variation_product();
		$this->sut->maybe_schedule_adjust_download_permissions( $product );

		$this->assertCount( 1, $this->pending_adjustments_for( $product->get_id() ) );
		$this->assertSame( 1, as_next_scheduled_action( 'adjust_download_permissions', array( $product->get_id() ) ), 'The adjustment is scheduled one second after the (mocked) current time' );
	}

	/**
	 * Get the IDs of the pending adjustment actions scheduled for a product.
	 *
	 * @param int $product_id The product ID.
	 * @return int[]
	 */
	private function pending_adjustments_for( int $product_id ): array {
		return as_get_scheduled_actions(
			array(
				'hook'   => 'adjust_download_permissions',
				'args'   => array( $product_id ),
				'status' => \ActionScheduler_Store::STATUS_PENDING,
			),
			'ids'
		);
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
		$child->set_downloadable( true );
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

		$this->initialize_subject();
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
		$child->set_downloadable( true );
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

		$this->initialize_subject();
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
		$child->set_downloadable( true );
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

		$this->initialize_subject();
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

	/**
	 * Creates a variable product with a downloadable variation. No downloads are added.
	 *
	 * @return \WC_Product A product.
	 */
	private function create_downloadable_variation_product() {
		$product = ProductHelper::create_variation_product();

		$child = wc_get_product( current( $product->get_children() ) );
		$child->set_downloadable( true );
		$child->save();

		return $product;
	}

}
