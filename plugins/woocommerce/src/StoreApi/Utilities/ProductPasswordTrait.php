<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Utilities;

/**
 * ProductPasswordTrait
 *
 * Shared functionality for handling password-protected products in Store API routes.
 * Follows the same pattern as WP_REST_Posts_Controller for password handling.
 *
 * @since 10.6.0
 */
trait ProductPasswordTrait {
	/**
	 * Product IDs that have passed password verification for the current request.
	 *
	 * @var array<int, true>
	 */
	protected $password_check_passed = array();

	/**
	 * Check if the user can access password-protected product content.
	 *
	 * @param \WC_Product      $product Product object.
	 * @param \WP_REST_Request $request Request object.
	 * @return bool True if the user can access password-protected content.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @since 10.6.0
	 */
	protected function can_access_password_content( \WC_Product $product, \WP_REST_Request $request ): bool {
		if ( ! $product->get_post_password() ) {
			return false;
		}

		if ( empty( $request['password'] ) ) {
			return false;
		}

		return hash_equals( (string) $product->get_post_password(), $request['password'] );
	}

	/**
	 * Filter callback for 'post_password_required' to bypass password check
	 * for products that have already been verified in the current request.
	 *
	 * @param bool     $required Whether the post requires a password.
	 * @param \WP_Post $post     Post object.
	 * @return bool
	 *
	 * @since 10.6.0
	 */
	public function check_password_required( $required, $post ) {
		if ( ! $required ) {
			return $required;
		}

		$post = get_post( $post );

		if ( ! $post ) {
			return $required;
		}

		if ( ! empty( $this->password_check_passed[ $post->ID ] ) ) {
			return false;
		}

		return $required;
	}

	/**
	 * If a valid password is provided for a password-protected product,
	 * bypass the password requirement for the current request.
	 *
	 * Follows the same pattern as WP_REST_Posts_Controller::can_access_password_content().
	 *
	 * @param \WC_Product      $product Product object.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @since 10.6.0
	 */
	protected function maybe_unlock_password_protected_product( \WC_Product $product, \WP_REST_Request $request ): void {
		if ( ! $this->can_access_password_content( $product, $request ) ) {
			return;
		}

		$this->password_check_passed[ $product->get_id() ] = true;
		add_filter( 'post_password_required', array( $this, 'check_password_required' ), 10, 2 );
	}
}
