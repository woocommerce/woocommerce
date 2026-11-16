<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments;

use Automattic\WooCommerce\Admin\Features\Fulfillments\DataStore\FulfillmentsDataStore;
use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsController;
use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsPrivacy;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Importer\FulfillmentsImporterRestController;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Importer\ImportSession;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use WC_Unit_Test_Case;

/**
 * Tests for the FulfillmentsController class.
 */
class FulfillmentsControllerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var FulfillmentsController
	 */
	private $sut;

	/**
	 * Original value of the fulfillments feature flag.
	 *
	 * @var mixed
	 */
	private $original_fulfillments_flag;

	/**
	 * Original value of the fulfillment tables marker.
	 *
	 * @var mixed
	 */
	private $original_tables_created;

	/**
	 * Feature flag value before the class enabled the feature.
	 *
	 * @var mixed
	 */
	private static $class_fulfillments_flag;

	/**
	 * Create the fulfillment tables once, outside the per-test transaction.
	 *
	 * initialize_fulfillments() creates the tables lazily when the flag is on. That is
	 * DDL, which must not run inside a test transaction, so it runs here first.
	 */
	public static function wpSetUpBeforeClass(): void {
		self::$class_fulfillments_flag = get_option( 'woocommerce_feature_fulfillments_enabled' );
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$controller = wc_get_container()->get( FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();
	}

	/**
	 * Restore the feature flag the class changed.
	 */
	public static function wpTearDownAfterClass(): void {
		if ( false === self::$class_fulfillments_flag ) {
			delete_option( 'woocommerce_feature_fulfillments_enabled' );
		} else {
			update_option( 'woocommerce_feature_fulfillments_enabled', self::$class_fulfillments_flag );
		}
	}

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_fulfillments_flag = get_option( 'woocommerce_feature_fulfillments_enabled' );
		$this->original_tables_created    = get_option( 'woocommerce_fulfillments_db_tables_created' );
		$this->sut                        = wc_get_container()->get( FulfillmentsController::class );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		if ( false === $this->original_fulfillments_flag ) {
			delete_option( 'woocommerce_feature_fulfillments_enabled' );
		} else {
			update_option( 'woocommerce_feature_fulfillments_enabled', $this->original_fulfillments_flag );
		}
		if ( false === $this->original_tables_created ) {
			delete_option( 'woocommerce_fulfillments_db_tables_created' );
		} else {
			update_option( 'woocommerce_fulfillments_db_tables_created', $this->original_tables_created );
		}
		parent::tearDown();
	}

	/**
	 * @testdox register() attaches the import session cleanup handler even while the feature flag is off.
	 */
	public function test_register_attaches_cleanup_handler_with_feature_disabled(): void {
		update_option( 'woocommerce_feature_fulfillments_enabled', 'no' );
		remove_action( ImportSession::CLEANUP_HOOK, array( ImportSession::class, 'handle_cleanup_hook' ), 10 );
		$this->assertFalse( has_action( ImportSession::CLEANUP_HOOK, array( ImportSession::class, 'handle_cleanup_hook' ) ), 'The cleanup handler must be detached before register() runs' );

		$this->sut->register();

		$this->assertSame( 10, has_action( ImportSession::CLEANUP_HOOK, array( ImportSession::class, 'handle_cleanup_hook' ) ), 'register() must attach the cleanup handler at priority 10 while the feature is off' );
	}

	/**
	 * @testdox Should register the import REST controller only while the feature flag is on.
	 */
	public function test_initialize_fulfillments_registers_import_rest_controller_only_when_enabled(): void {
		$importer = wc_get_container()->get( FulfillmentsImporterRestController::class );
		$callback = array( $importer, 'handle_woocommerce_rest_api_get_rest_namespaces' );
		remove_filter( 'woocommerce_rest_api_get_rest_namespaces', $callback );

		update_option( 'woocommerce_feature_fulfillments_enabled', 'no' );
		$this->sut->initialize_fulfillments();
		$this->assertFalse( has_filter( 'woocommerce_rest_api_get_rest_namespaces', $callback ), 'The prepare route must not be registered while the feature is off' );

		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$this->sut->initialize_fulfillments();
		$this->assertSame( 10, has_filter( 'woocommerce_rest_api_get_rest_namespaces', $callback ), 'The prepare route is registered once the feature is on' );
	}

	/**
	 * @testdox Should register the order-fulfillment data store when feature is enabled.
	 */
	public function test_register_data_stores_when_feature_enabled(): void {
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );

		$result = $this->sut->register_data_stores( array() );

		$this->assertArrayHasKey( 'order-fulfillment', $result, 'Data store should be registered when feature is enabled' );
		$this->assertSame( FulfillmentsDataStore::class, $result['order-fulfillment'], 'Data store class should be FulfillmentsDataStore' );
	}

	/**
	 * @testdox Should not register the order-fulfillment data store when the feature is disabled and the tables were never created.
	 */
	public function test_register_data_stores_when_feature_disabled(): void {
		update_option( 'woocommerce_feature_fulfillments_enabled', 'no' );
		delete_option( 'woocommerce_fulfillments_db_tables_created' );

		$result = $this->sut->register_data_stores( array() );

		$this->assertArrayNotHasKey( 'order-fulfillment', $result, 'Data store should not be registered when feature is disabled' );
	}

	/**
	 * @testdox Should register the order-fulfillment data store when the feature is disabled but its tables exist.
	 */
	public function test_register_data_stores_when_feature_disabled_but_tables_exist(): void {
		update_option( 'woocommerce_feature_fulfillments_enabled', 'no' );
		update_option( 'woocommerce_fulfillments_db_tables_created', true );

		$result = $this->sut->register_data_stores( array() );

		$this->assertArrayHasKey(
			'order-fulfillment',
			$result,
			'Fulfillment data left behind by the disabled feature still has to be readable for the privacy export'
		);
		$this->assertSame( FulfillmentsDataStore::class, $result['order-fulfillment'] );
	}

	/**
	 * @testdox Should preserve existing data stores when feature is enabled.
	 */
	public function test_register_data_stores_preserves_existing_stores(): void {
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$existing = array( 'some-store' => 'SomeStoreClass' );

		$result = $this->sut->register_data_stores( $existing );

		$this->assertArrayHasKey( 'some-store', $result, 'Existing data stores should be preserved' );
		$this->assertSame( 'SomeStoreClass', $result['some-store'] );
		$this->assertArrayHasKey( 'order-fulfillment', $result );
	}

	/**
	 * @testdox Should preserve existing data stores when feature is disabled.
	 */
	public function test_register_data_stores_preserves_existing_stores_when_disabled(): void {
		update_option( 'woocommerce_feature_fulfillments_enabled', 'no' );
		delete_option( 'woocommerce_fulfillments_db_tables_created' );
		$existing = array( 'some-store' => 'SomeStoreClass' );

		$result = $this->sut->register_data_stores( $existing );

		$this->assertArrayHasKey( 'some-store', $result, 'Existing data stores should be preserved' );
		$this->assertArrayNotHasKey( 'order-fulfillment', $result );
	}

	/**
	 * @testdox Should keep both privacy hooks registered when the feature is disabled.
	 */
	public function test_initialize_fulfillments_registers_privacy_hooks_when_feature_disabled(): void {
		update_option( 'woocommerce_feature_fulfillments_enabled', 'no' );
		update_option( 'woocommerce_fulfillments_db_tables_created', true );

		// Both branches of initialize_fulfillments() register these hooks, so confirm which one
		// is under test rather than letting the enabled branch satisfy the assertions.
		$this->assertFalse(
			wc_get_container()->get( FeaturesController::class )->feature_is_enabled( 'fulfillments' ),
			'This test covers the disabled branch.'
		);

		// The container instance is shared, so clear both hooks before asserting that the
		// disabled path puts them back.
		$privacy = wc_get_container()->get( FulfillmentsPrivacy::class );
		remove_action( 'woocommerce_privacy_remove_order_personal_data', array( $privacy, 'remove_fulfillment_personal_data' ) );
		remove_filter( 'woocommerce_privacy_export_order_personal_data', array( $privacy, 'export_order_fulfillment_data' ) );

		$this->sut->initialize_fulfillments();

		$this->assertNotFalse(
			has_action( 'woocommerce_privacy_remove_order_personal_data', array( $privacy, 'remove_fulfillment_personal_data' ) ),
			'Tracking data left behind by the disabled feature should still be erasable'
		);
		$this->assertNotFalse(
			has_filter( 'woocommerce_privacy_export_order_personal_data', array( $privacy, 'export_order_fulfillment_data' ) ),
			'An access request should still return tracking data the disabled feature left behind'
		);
	}

	/**
	 * @testdox Should register no privacy hooks when the feature is disabled and the tables were never created.
	 */
	public function test_initialize_fulfillments_registers_no_privacy_hooks_without_tables(): void {
		update_option( 'woocommerce_feature_fulfillments_enabled', 'no' );
		delete_option( 'woocommerce_fulfillments_db_tables_created' );

		// Without the marker the enabled branch would drop and recreate the fulfillment tables,
		// and DDL commits implicitly, so the base class transaction could not roll it back.
		$this->assertFalse(
			wc_get_container()->get( FeaturesController::class )->feature_is_enabled( 'fulfillments' ),
			'This test covers the disabled branch.'
		);

		$privacy = wc_get_container()->get( FulfillmentsPrivacy::class );
		remove_action( 'woocommerce_privacy_remove_order_personal_data', array( $privacy, 'remove_fulfillment_personal_data' ) );
		remove_filter( 'woocommerce_privacy_export_order_personal_data', array( $privacy, 'export_order_fulfillment_data' ) );

		$this->sut->initialize_fulfillments();

		$this->assertFalse(
			has_action( 'woocommerce_privacy_remove_order_personal_data', array( $privacy, 'remove_fulfillment_personal_data' ) ),
			'A store that never created the tables has no fulfillment data to erase'
		);
		$this->assertFalse(
			has_filter( 'woocommerce_privacy_export_order_personal_data', array( $privacy, 'export_order_fulfillment_data' ) ),
			'A store that never created the tables has no fulfillment data to export'
		);
	}

	/**
	 * @testdox Should return non-array input unchanged.
	 */
	public function test_register_data_stores_returns_non_array_unchanged(): void {
		$result = $this->sut->register_data_stores( 'not-an-array' );

		$this->assertSame( 'not-an-array', $result, 'Non-array input should be returned unchanged' );
	}
}
