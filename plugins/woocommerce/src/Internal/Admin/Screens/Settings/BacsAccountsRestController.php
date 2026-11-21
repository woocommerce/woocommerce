<?php
/**
 * REST route for the bank accounts on the experimental BACS settings screen.
 *
 * @package WooCommerce\Internal\Admin\Screens\Settings
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Screens\Settings;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Internal\Admin\Screens\AdminScreens;
use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and saves the BACS bank accounts, which the v3 payment gateways endpoint doesn't cover.
 *
 * Both methods return the saved accounts, so the screen can keep them in core-data as the record `accounts`.
 *
 * @internal
 */
class BacsAccountsRestController extends RestApiControllerBase {

	/**
	 * The option that holds the accounts.
	 */
	private const OPTION = 'woocommerce_bacs_accounts';

	/**
	 * The fields of a stored account, in the order the classic form saves them.
	 */
	private const ACCOUNT_FIELDS = array( 'account_name', 'account_number', 'bank_name', 'sort_code', 'iban', 'bic', 'country_code' );

	/**
	 * The root namespace for the JSON REST API endpoints.
	 *
	 * @var string
	 */
	protected string $route_namespace = 'wc-admin';

	/**
	 * The Payments settings service, for the merchant's business country.
	 *
	 * @var Payments
	 */
	private Payments $payments;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param Payments $payments The Payments settings service.
	 */
	final public function init( Payments $payments ): void {
		$this->payments = $payments;
	}

	/**
	 * Get the WooCommerce REST API namespace for the class.
	 *
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return 'wc-admin-settings-screen-bacs-accounts';
	}

	/**
	 * Register the route while the settings screen is enabled.
	 */
	public function register_routes(): void {
		if ( ! Features::is_enabled( AdminScreens::FEATURE_FLAG ) ) {
			return;
		}

		register_rest_route(
			$this->route_namespace,
			'/settings/payments/bacs/accounts',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'get_accounts' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'update_accounts' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => array(
						'accounts' => array(
							'description' => __( 'The bank accounts customers can pay into, in display order.', 'woocommerce' ),
							'type'        => 'array',
							'required'    => true,
							'items'       => $this->get_account_schema(),
						),
					),
				),
				'schema' => fn() => $this->get_schema(),
			)
		);
	}

	/**
	 * Return the saved accounts and the country to start new accounts in.
	 *
	 * @param WP_REST_Request $request The request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return array
	 */
	protected function get_accounts( WP_REST_Request $request ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- run() passes the request to every callback.
		$accounts = get_option( self::OPTION, array() );

		return array(
			'id'              => 'accounts',
			'accounts'        => is_array( $accounts ) ? array_values( array_filter( $accounts, 'is_array' ) ) : array(),
			'default_country' => $this->payments->get_country(),
		);
	}

	/**
	 * Replace the saved accounts, then return them.
	 *
	 * @param WP_REST_Request $request The request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return array
	 */
	protected function update_accounts( WP_REST_Request $request ): array {
		$accounts = array();
		foreach ( (array) $request->get_param( 'accounts' ) as $account ) {
			$saved = array();
			foreach ( self::ACCOUNT_FIELDS as $field ) {
				$saved[ $field ] = wc_clean( wp_unslash( (string) ( $account[ $field ] ?? '' ) ) );
			}
			$accounts[] = $saved;
		}

		/**
		 * Fired before saving, as WC_Gateway_BACS::save_account_details() does on the classic form.
		 * This action is documented in includes/admin/class-wc-admin-settings.php.
		 *
		 * @since 11.4.0
		 */
		do_action( 'woocommerce_update_option', array( 'id' => self::OPTION ) );
		update_option( self::OPTION, $accounts );

		return $this->get_accounts( $request );
	}

	/**
	 * Allow the users who can manage payment gateways.
	 *
	 * @param WP_REST_Request $request The request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return bool|WP_Error
	 */
	private function check_permissions( WP_REST_Request $request ) {
		$context = 'GET' === $request->get_method() ? 'read' : 'edit';
		if ( wc_rest_check_manager_permissions( 'payment_gateways', $context ) ) {
			return true;
		}

		$error_information = $this->get_authentication_error_by_method( $request->get_method() );
		if ( is_null( $error_information ) ) {
			return false;
		}

		return new WP_Error(
			$error_information['code'],
			$error_information['message'],
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Get the schema of one account.
	 *
	 * @return array
	 */
	private function get_account_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array_fill_keys( self::ACCOUNT_FIELDS, array( 'type' => 'string' ) ),
		);
	}

	/**
	 * Get the schema of the response.
	 *
	 * @return array
	 */
	private function get_schema(): array {
		$schema               = $this->get_base_schema();
		$schema['title']      = 'bacs_accounts';
		$schema['properties'] = array(
			'id'              => array(
				'description' => __( 'The record ID, which is always "accounts".', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'accounts'        => array(
				'description' => __( 'The bank accounts customers can pay into, in display order.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'items'       => $this->get_account_schema(),
			),
			'default_country' => array(
				'description' => __( 'The country new accounts start in: the business location, or the store country.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);

		return $schema;
	}
}
