<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\StoreApi\Utilities\Ucp\Utils as UcpUtils;

defined( 'ABSPATH' ) || exit;

/**
 * Turns UCP on or off from the Agentic commerce settings page. A failing
 * required setup check keeps it from being turned on. Turning it off still
 * works, and a switch that is already on shows as Needs attention.
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
	 * @return array{enabled:bool,status:string,failing_checks:array<int,string>}
	 */
	public function set_enabled( bool $enable ): array {
		$failing = $this->store_conditions->failing_required();

		// Already on stays on, so it can be turned off. Off stays off until the required checks pass.
		if ( $enable && ! UcpUtils::is_enabled() && array() !== $failing ) {
			return array(
				'enabled'        => false,
				'status'         => self::STATUS_OFF,
				'failing_checks' => $failing,
			);
		}

		if ( UcpUtils::is_enabled() !== $enable ) {
			$this->features_controller->change_feature_enable( UcpUtils::FEATURE_ID, $enable );
			\WC_Tracks::record_event( 'agentic_commerce_ucp_toggled', array( 'enabled' => $enable ? 'yes' : 'no' ) );
		}

		return array(
			'enabled'        => UcpUtils::is_enabled(),
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
