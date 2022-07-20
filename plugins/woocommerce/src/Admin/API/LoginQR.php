<?php
/**
 * REST API Data countries controller.
 *
 * Handles requests to the /data/countries endpoint.
 */

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Data countries controller class.
 *
 * @internal
 * @extends WC_REST_Data_Controller
 */
class LoginQR extends \WC_REST_Data_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-admin';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'login-qr';

	/**
	 * Register routes.
	 *
	 * @since 3.5.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/jetpack_status',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'jetpack_status' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/generate_qr',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'generate_qr' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		parent::register_routes();
	}

	/**
	 * Get Jetpack status.
	 *
	 * @return array
	 */
	public function jetpack_status() {
		return rest_ensure_response(
			[
				'installed'      => true,
				'activated'      => true,
				'connected'      => true,
				'user_connected' => false,
			]
		);
	}

	/**
	 * Generate QR code.
	 *
	 * @return array
	 */
	public function generate_qr() {
		return rest_ensure_response(
			[
				'image' => 'data:image/png;base64, iVBORw0KGgoAAAANSUhEUgAAAZAAAAGQAQMAAAC6caSPAAAABlBMVEX///8AAABVwtN+AAACFUlEQVR4nO3bO3KDMBDG8fWkoOQIOQpHg6P5KBwhpQsGRY9dSRA7r4nkFP9tEmT9VH2DHoAIRbWu0eXa/eV8S83zJq/avErp4i8gkB5k0YBOe/A3mapfRV4C0XHiBQTShbyEDrdAfIIt0aEtjOMSieMsEMgTyJj/pkZfm+8DgTyT+Brc9ZK7xvoq/BBICyKpUoI9iVc6zjeWChBIA5LLyMW9pV1SiHUkpSCQHuROFeLjvd3vA4G0JJbksmlPSdZGXynROg4E0oOITu3WVaf4nGSnN9jdekIgPyaz7W70ON3iWWKZ15ZWEEhbUrY8yRvVZeQU/55vsBBIW2In50NJ8l7P7NYrnBotAoH0I+UMaNb5Px4QhXG2dIOdjvM+BNKepF9d6BorJnmV6oDoej/8EEgTMuawii0C8k48NNm5Zbz7bhBIF6KVWmOC7YCoeroznMIPgbQl+kw8X8SHjxpvOZwpjafH6BBISyL6a9kd2XLUSscpSYZA2hMRm/erDZHtkiI5LhUgkF+RGMvF4mkLTnuJUjO7QiAdyKHK2jKV7sRFTp9FQCBtic3brrylpgvO9eM7vdXAEEhTov/Ur5pXb6lp3m0xsEIgfUj9Yc4y5AMi98lSAQLpSUKvULN99L3leDsI5HlkKgdEQ/3dmHkIpAdJ2a0375I/zHExvI/ehYNAWpFchyRLvsEafbSxgkD+nFDUv6t37pg7IDeGw/cAAAAASUVORK5CYII=',
			]
		);
	}
}
