<?php
/**
 * Finance controller.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Payments\Finance;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the payments finance REST API when the feature is enabled.
 *
 * @internal
 */
class FinanceController implements RegisterHooksInterface {

	/**
	 * The feature flag id.
	 *
	 * @var string
	 */
	public const FEATURE_ID = 'payments_finance';

	/**
	 * The finance REST controller.
	 *
	 * @var FinanceRestController
	 */
	private FinanceRestController $rest_controller;

	/**
	 * The finance admin menu.
	 *
	 * @var FinanceMenu
	 */
	private FinanceMenu $menu;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param FinanceRestController $rest_controller The finance REST controller.
	 * @param FinanceMenu           $menu            The finance admin menu.
	 */
	final public function init( FinanceRestController $rest_controller, FinanceMenu $menu ): void {
		$this->rest_controller = $rest_controller;
		$this->menu            = $menu;
	}

	/**
	 * Register hooks.
	 *
	 * @since 11.2.0
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'handle_init' ) );
	}

	/**
	 * Handle the init hook: register the REST controller and the admin menu when the feature is enabled.
	 *
	 * @internal
	 */
	public function handle_init(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$this->rest_controller->register();
		$this->menu->register();
	}

	/**
	 * Whether the payments finance feature is enabled.
	 *
	 * @return bool
	 *
	 * @since 11.2.0
	 */
	public function is_enabled(): bool {
		return FeaturesUtil::feature_is_enabled( self::FEATURE_ID );
	}
}
