<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyTrackingController;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilderFactory;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyTrackingOrderCountProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyTrackingProjectionService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyTrackingController class.
 */
class MultiCurrencyTrackingControllerTest extends WC_Unit_Test_Case {

	private const TRACKER_HOOK = 'woocommerce_tracker_data';

	/**
	 * Tear down test fixtures.
	 */
	public function tear_down(): void {
		remove_all_filters( self::TRACKER_HOOK );

		parent::tear_down();
	}

	/**
	 * @testdox Should not register tracker data when plugin owns runtime.
	 */
	public function test_does_not_register_tracker_data_when_plugin_owns_runtime(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN );

		$sut->register();

		$this->assertFalse( has_filter( self::TRACKER_HOOK, array( $sut, 'add_tracker_data' ) ) );
	}

	/**
	 * @testdox Should register tracker data once when core owns runtime.
	 */
	public function test_registers_tracker_data_once_when_core_owns_runtime(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );

		$sut->register();
		$sut->register();

		$this->assertSame( 50, has_filter( self::TRACKER_HOOK, array( $sut, 'add_tracker_data' ) ) );
	}

	/**
	 * @testdox Should project tracker data with aggregated multi-currency order counts.
	 */
	public function test_projects_tracker_data_with_aggregated_multi_currency_order_counts(): void {
		$projection_service  = $this->create_projection_service();
		$order_count_service = $this->create_order_count_service();
		$sut                 = $this->create_controller(
			MultiCurrencyRuntimeArbiter::OWNER_CORE,
			$projection_service,
			$order_count_service
		);

		$result = $sut->add_tracker_data( array( 'existing' => 'value' ) );

		$this->assertSame(
			array(
				'existing'             => 'value',
				'wcpay_multi_currency' => array(
					'counts'     => 2,
					'currencies' => array(
						'GBP' => array(
							'counts'   => 2,
							'totals'   => 20.5,
							'gateways' => array(
								'woocommerce_payments' => array(
									'counts' => 2,
									'totals' => 20.5,
								),
							),
						),
					),
				),
			),
			$result
		);
		$this->assertSame( array( 'existing' => 'value' ), $projection_service->last_data );
		$this->assertSame( $result['wcpay_multi_currency'], $projection_service->last_order_counts );
	}

	/**
	 * @testdox Should pass the resolved storage mode to the order count service.
	 */
	public function test_passes_resolved_storage_mode_to_order_count_service(): void {
		$hpos_order_count_service   = $this->create_order_count_service();
		$legacy_order_count_service = $this->create_order_count_service();
		$hpos_controller            = $this->create_controller(
			MultiCurrencyRuntimeArbiter::OWNER_CORE,
			$this->create_projection_service(),
			$hpos_order_count_service,
			static fn(): bool => true
		);
		$legacy_controller          = $this->create_controller(
			MultiCurrencyRuntimeArbiter::OWNER_CORE,
			$this->create_projection_service(),
			$legacy_order_count_service,
			static fn(): bool => false
		);

		$hpos_controller->add_tracker_data( array() );
		$legacy_controller->add_tracker_data( array() );

		$this->assertTrue( $hpos_order_count_service->last_is_hpos_enabled );
		$this->assertFalse( $legacy_order_count_service->last_is_hpos_enabled );
	}

	/**
	 * Create a tracking controller.
	 *
	 * @param string                                                $owner               Runtime owner.
	 * @param MultiCurrencyTrackingProjectionService|null           $projection_service  Projection service.
	 * @param MultiCurrencyTrackingOrderCountProjectionService|null $order_count_service Order count service.
	 * @param callable|null                                         $storage_resolver    Storage mode resolver.
	 * @return MultiCurrencyTrackingController
	 */
	private function create_controller(
		string $owner,
		?MultiCurrencyTrackingProjectionService $projection_service = null,
		?MultiCurrencyTrackingOrderCountProjectionService $order_count_service = null,
		?callable $storage_resolver = null
	): MultiCurrencyTrackingController {
		$controller = new MultiCurrencyTrackingController();
		$controller->init(
			$this->create_arbiter( $owner ),
			wc_get_container()->get( MultiCurrencyStateBuilderFactory::class )
		);

		if ( null !== $projection_service ) {
			$controller->set_tracking_projection_service( $projection_service );
		}

		if ( null !== $order_count_service ) {
			$controller->set_order_count_projection_service( $order_count_service );
		}

		if ( null !== $storage_resolver ) {
			$controller->set_hpos_enabled_resolver( $storage_resolver );
		}

		return $controller;
	}

	/**
	 * Create a projection service test double.
	 *
	 * @return MultiCurrencyTrackingProjectionService&object{last_data: array<string,mixed>, last_order_counts: array<string,mixed>}
	 */
	private function create_projection_service(): MultiCurrencyTrackingProjectionService {
		return new class() extends MultiCurrencyTrackingProjectionService {
			/**
			 * Last tracker data received by the projection.
			 *
			 * @var array<string,mixed>
			 */
			public array $last_data = array();

			/**
			 * Last order counts received by the projection.
			 *
			 * @var array<string,mixed>
			 */
			public array $last_order_counts = array();

			/**
			 * Constructor.
			 */
			public function __construct() {}

			/**
			 * Project multi-currency tracker data.
			 *
			 * @param array<string,mixed> $data         Existing tracker data.
			 * @param array<string,mixed> $order_counts Precomputed order-count payload.
			 * @return array<string,mixed>
			 */
			public function project_tracker_data( array $data, array $order_counts = array() ): array {
				$this->last_data         = $data;
				$this->last_order_counts = $order_counts;

				$data['wcpay_multi_currency'] = $order_counts;

				return $data;
			}
		};
	}

	/**
	 * Create an order count projection service test double.
	 *
	 * @return MultiCurrencyTrackingOrderCountProjectionService&object{last_is_hpos_enabled: bool|null}
	 */
	private function create_order_count_service(): MultiCurrencyTrackingOrderCountProjectionService {
		return new class() extends MultiCurrencyTrackingOrderCountProjectionService {
			/**
			 * Last storage mode received by the service.
			 *
			 * @var bool|null
			 */
			public ?bool $last_is_hpos_enabled = null;

			/**
			 * Get the order-count query for the active order storage mode.
			 *
			 * @param bool $is_hpos_enabled Whether HPOS order storage is enabled.
			 * @return string
			 */
			public function get_order_count_query( bool $is_hpos_enabled ): string {
				$this->last_is_hpos_enabled = $is_hpos_enabled;

				return "SELECT 'woocommerce_payments' AS gateway, 'GBP' AS currency, 20.5 AS totals, 2 AS counts";
			}
		};
	}

	/**
	 * Create a static multi-currency runtime arbiter.
	 *
	 * @param string $owner Runtime owner.
	 * @return MultiCurrencyRuntimeArbiter
	 */
	private function create_arbiter( string $owner ): MultiCurrencyRuntimeArbiter {
		return new class( $owner ) extends MultiCurrencyRuntimeArbiter {
			/**
			 * Runtime owner.
			 *
			 * @var string
			 */
			private string $owner;

			/**
			 * Constructor.
			 *
			 * @param string $owner Runtime owner.
			 */
			public function __construct( string $owner ) {
				$this->owner = $owner;
			}

			/**
			 * Get the multi-currency runtime owner for the current site.
			 *
			 * @return string
			 */
			public function get_runtime_owner(): string {
				return $this->owner;
			}

			/**
			 * Tell whether core multi-currency may register hooks.
			 *
			 * @return bool
			 */
			public function should_core_register(): bool {
				return MultiCurrencyRuntimeArbiter::OWNER_CORE === $this->owner;
			}
		};
	}
}
