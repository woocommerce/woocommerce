<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\OrderWithdrawal;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Registers the order withdrawal endpoint and related feature-gated UI.
 *
 * @internal Just for internal use.
 */
final class OrderWithdrawalController implements RegisterHooksInterface {

	private const FEATURE_ID      = 'order_withdrawal';
	private const ENDPOINT_KEY    = 'order-withdrawal';
	private const ENDPOINT_OPTION = 'woocommerce_myaccount_order_withdrawal_endpoint';

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( FeaturesController::FEATURE_ENABLED_CHANGED_ACTION, array( $this, 'maybe_flush_rewrite_rules' ), 10, 1 );
		add_filter( 'woocommerce_get_query_vars', array( $this, 'add_query_var' ), 10, 1 );
		add_filter( 'woocommerce_endpoint_' . self::ENDPOINT_KEY . '_title', array( $this, 'get_endpoint_title' ), 10, 1 );
		add_filter( 'woocommerce_settings_pages', array( $this, 'add_endpoint_setting' ), 10, 1 );
		add_action( 'woocommerce_account_' . self::ENDPOINT_KEY . '_endpoint', array( $this, 'render_view' ) );
	}

	/**
	 * Whether order withdrawal is enabled.
	 */
	public function is_enabled(): bool {
		return FeaturesUtil::feature_is_enabled( self::FEATURE_ID );
	}

	/**
	 * Whether the current My Account request is for the public order withdrawal endpoint.
	 */
	public function is_endpoint_request(): bool {
		global $wp;

		return $this->is_enabled()
			&& isset( $wp->query_vars[ self::ENDPOINT_KEY ] )
			&& self::ENDPOINT_KEY === WC()->query->get_current_endpoint();
	}

	/**
	 * Queue a rewrite rules flush when the feature is toggled.
	 *
	 * @param string $feature_id Feature being toggled.
	 */
	public function maybe_flush_rewrite_rules( string $feature_id ): void {
		if ( self::FEATURE_ID === $feature_id ) {
			update_option( 'woocommerce_queue_flush_rewrite_rules', 'yes' );
		}
	}

	/**
	 * Register the order withdrawal query var.
	 *
	 * @param array $query_vars Existing query vars keyed by endpoint key.
	 */
	public function add_query_var( $query_vars ): array {
		if ( ! is_array( $query_vars ) ) {
			return array();
		}

		if ( $this->is_enabled() ) {
			$query_vars[ self::ENDPOINT_KEY ] = (string) get_option( self::ENDPOINT_OPTION, self::ENDPOINT_KEY );
		}

		return $query_vars;
	}

	/**
	 * Order withdrawal endpoint page title.
	 *
	 * @param string $title Default title.
	 */
	public function get_endpoint_title( $title ): string {
		return __( 'Order withdrawal', 'woocommerce' );
	}

	/**
	 * Add the endpoint setting when the feature is enabled.
	 *
	 * @param array $settings Page settings.
	 */
	public function add_endpoint_setting( $settings ): array {
		if ( ! is_array( $settings ) ) {
			return array();
		}

		if ( ! $this->is_enabled() ) {
			return $settings;
		}

		$endpoint_setting = array(
			'title'    => __( 'Order withdrawal', 'woocommerce' ),
			'desc'     => __( 'Endpoint for the order withdrawal page.', 'woocommerce' ),
			'id'       => self::ENDPOINT_OPTION,
			'type'     => 'text',
			'default'  => self::ENDPOINT_KEY,
			'desc_tip' => true,
		);

		$new_settings = array();
		$added        = false;

		foreach ( $settings as $setting ) {
			if ( is_array( $setting ) && self::ENDPOINT_OPTION === ( $setting['id'] ?? '' ) ) {
				return $settings;
			}

			if (
				! $added &&
				is_array( $setting ) &&
				'sectionend' === ( $setting['type'] ?? '' ) &&
				'account_endpoint_options' === ( $setting['id'] ?? '' )
			) {
				$new_settings[] = $endpoint_setting;
				$added          = true;
			}

			$new_settings[] = $setting;
		}

		if ( ! $added ) {
			$new_settings[] = $endpoint_setting;
		}

		return $new_settings;
	}

	/**
	 * Render the order withdrawal view.
	 */
	public function render_view(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		?>
		<h2><?php esc_html_e( 'Order withdrawal', 'woocommerce' ); ?></h2>
		<p><?php esc_html_e( 'This is placeholder content for the order withdrawal page.', 'woocommerce' ); ?></p>
		<?php
	}
}
