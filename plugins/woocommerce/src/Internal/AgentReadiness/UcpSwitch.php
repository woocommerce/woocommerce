<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\StoreApi\Utilities\UcpUtils;

defined( 'ABSPATH' ) || exit;

/**
 * Turns UCP on or off from the Agentic commerce settings page. Turning it on
 * is refused while a required setup check fails; turning it off always works.
 *
 * @since 11.4.0
 */
class UcpSwitch {

	public const STATUS_ACTIVE = 'active';

	public const STATUS_OFF = 'off';

	public const STATUS_NEEDS_ATTENTION = 'needs_attention';

	/**
	 * Features controller.
	 *
	 * @var FeaturesController
	 */
	private FeaturesController $features_controller;

	/**
	 * Store conditions.
	 *
	 * @var StoreConditions
	 */
	private StoreConditions $store_conditions;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param FeaturesController $features_controller Features controller.
	 * @param StoreConditions    $store_conditions    Store conditions.
	 */
	final public function init( FeaturesController $features_controller, StoreConditions $store_conditions ): void {
		$this->features_controller = $features_controller;
		$this->store_conditions    = $store_conditions;
	}

	/**
	 * Turn UCP on or off.
	 *
	 * @param bool $enable Whether to turn UCP on.
	 * @return array{enabled:bool,status:string,failing_checks:array<int,string>}|\WP_Error Error when a required check fails.
	 */
	public function set_enabled( bool $enable ) {
		$failing = $this->store_conditions->failing_required();
		if ( $enable && ! empty( $failing ) ) {
			\WC_Tracks::record_event( 'agentic_commerce_ucp_blocked', array( 'failing_checks' => implode( ',', $failing ) ) );
			return new \WP_Error(
				'woocommerce_agentic_commerce_setup_incomplete',
				sprintf(
					/* translators: %d: number of failing required setup checks. */
					_n( 'Fix %d required setup check to turn this on.', 'Fix %d required setup checks to turn this on.', count( $failing ), 'woocommerce' ),
					count( $failing )
				),
				array(
					'status'         => 409,
					'failing_checks' => $failing,
				)
			);
		}

		if ( UcpUtils::is_enabled() !== $enable ) {
			$this->features_controller->change_feature_enable( UcpUtils::FEATURE_ID, $enable );
			\WC_Tracks::record_event( 'agentic_commerce_ucp_toggled', array( 'enabled' => $enable ? 'yes' : 'no' ) );
		}

		return array(
			'enabled'        => $enable,
			'status'         => $this->status( $failing ),
			'failing_checks' => $failing,
		);
	}

	/**
	 * Active, off, or needs attention when UCP is on but a required check fails.
	 *
	 * @param array<int, string>|null $failing Failing required check ids, or null to run the checks.
	 */
	public function status( ?array $failing = null ): string {
		if ( ! UcpUtils::is_enabled() ) {
			return self::STATUS_OFF;
		}
		$failing = $failing ?? $this->store_conditions->failing_required();
		return empty( $failing ) ? self::STATUS_ACTIVE : self::STATUS_NEEDS_ATTENTION;
	}
}
