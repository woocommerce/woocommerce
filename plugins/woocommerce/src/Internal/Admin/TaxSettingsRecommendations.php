<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * TaxSettingsRecommendations Class.
 *
 * Handles dismissal of the recommended tax solutions card on the Tax settings
 * screen. The dismissal is stored site-wide in the
 * `woocommerce_settings_tax_recommendations_hidden` option, but is read and
 * written through a dedicated REST endpoint instead of the deprecated Options
 * REST API (whose allowlist is frozen). This keeps the dismissal working in all
 * environments, including non-production.
 *
 * @internal
 */
class TaxSettingsRecommendations {

	/**
	 * Option name used to persist the dismissal.
	 *
	 * @var string
	 */
	const DISMISSED_OPTION_NAME = 'woocommerce_settings_tax_recommendations_hidden';

	/**
	 * Class initialization, to be executed when the class is resolved by the container.
	 *
	 * @internal
	 */
	final public function init(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_filter( 'woocommerce_admin_shared_settings', array( $this, 'preload_settings' ) );
	}

	/**
	 * Register the REST route used to dismiss the recommended tax solutions card.
	 */
	public function register_routes(): void {
		register_rest_route(
			'wc-admin',
			'/tax/recommendations/dismiss',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'dismiss' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);
	}

	/**
	 * Check whether the current user can dismiss the recommendations.
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Full details about the request.
	 * @return bool|\WP_Error
	 */
	public function permissions_check( \WP_REST_Request $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Squiz.Commenting.FunctionComment.IncorrectTypeHint
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new \WP_Error(
				'woocommerce_rest_cannot_edit',
				__( 'Sorry, you are not allowed to dismiss these recommendations.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Persist the dismissal of the recommended tax solutions card.
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function dismiss( \WP_REST_Request $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Squiz.Commenting.FunctionComment.IncorrectTypeHint
		// Already dismissed: update_option() returns false when the value is
		// unchanged, which is not a failure, so report success directly.
		if ( 'yes' === get_option( self::DISMISSED_OPTION_NAME ) ) {
			return new \WP_REST_Response( array( 'dismissed' => true ), 200 );
		}

		if ( ! update_option( self::DISMISSED_OPTION_NAME, 'yes' ) ) {
			return new \WP_Error(
				'woocommerce_rest_tax_recommendations_dismiss_failed',
				__( 'The dismissal could not be saved.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		return new \WP_REST_Response( array( 'dismissed' => true ), 200 );
	}

	/**
	 * Preload the dismissal state into the wc-admin settings so the client can
	 * render the correct initial visibility without an extra request.
	 *
	 * @param array $settings Shared settings.
	 * @return array
	 */
	public function preload_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			return $settings;
		}

		$settings['taxRecommendationsHidden'] = 'yes' === get_option( self::DISMISSED_OPTION_NAME );

		return $settings;
	}
}
