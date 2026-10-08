<?php
/**
 * REST controller for subscription engine contracts.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api\Rest
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api\Rest;

use WP_REST_Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Contracts REST controller, under `wc/v3/subscriptions-engine/contracts`.
 *
 * Registers no routes yet: extensions read and write contracts through the
 * {@see \Automattic\WooCommerce\SubscriptionsEngine\Api\Contracts} facade and expose
 * their own customer actions.
 */
final class ContractsController extends WP_REST_Controller {

	private const REST_NAMESPACE = 'wc/v3';

	private const REST_BASE = 'subscriptions-engine/contracts';

	/**
	 * Build the controller.
	 */
	public function __construct() {
		$this->namespace = self::REST_NAMESPACE;
		$this->rest_base = self::REST_BASE;
	}

	/**
	 * Wire route registration.
	 */
	public static function register_hooks(): void {
		add_action(
			'rest_api_init',
			static function (): void {
				( new self() )->register_routes();
			}
		);
	}

	/**
	 * Register the routes. None yet.
	 */
	public function register_routes(): void {
	}
}
