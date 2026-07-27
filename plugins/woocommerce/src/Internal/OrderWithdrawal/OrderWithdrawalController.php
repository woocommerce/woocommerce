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
	private const ENDPOINT_SLUG   = 'withdraw-order';
	private const ENDPOINT_OPTION = 'woocommerce_myaccount_order_withdrawal_endpoint';

	/**
	 * Form processor.
	 *
	 * @var OrderWithdrawalFormProcessor
	 */
	private OrderWithdrawalFormProcessor $form_processor;

	/**
	 * Form view.
	 *
	 * @var OrderWithdrawalFormView
	 */
	private OrderWithdrawalFormView $form_view;

	/**
	 * Initialize dependencies.
	 *
	 * @param OrderWithdrawalFormProcessor $form_processor Form processor.
	 * @param OrderWithdrawalFormView      $form_view Form view.
	 * @internal
	 *
	 * @since 11.1.0
	 */
	final public function init( OrderWithdrawalFormProcessor $form_processor, OrderWithdrawalFormView $form_view ): void { // phpcs:ignore Generic.CodeAnalysis.UnnecessaryFinalModifier.Found -- Required by WooCommerce injection method rules.
		$this->form_processor = $form_processor;
		$this->form_view      = $form_view;
	}

	/**
	 * Register hooks.
	 *
	 * @since 11.1.0
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
	 *
	 * @since 11.1.0
	 */
	public function is_enabled(): bool {
		return FeaturesUtil::feature_is_enabled( self::FEATURE_ID );
	}

	/**
	 * Whether the current My Account request is for the public order withdrawal endpoint.
	 *
	 * @since 11.1.0
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
	 *
	 * @since 11.1.0
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
	 * @return array
	 *
	 * @since 11.1.0
	 */
	public function add_query_var( $query_vars ): array {
		if ( ! is_array( $query_vars ) ) {
			return array();
		}

		if ( $this->is_enabled() ) {
			$query_vars[ self::ENDPOINT_KEY ] = (string) get_option( self::ENDPOINT_OPTION, self::ENDPOINT_SLUG );
		}

		return $query_vars;
	}

	/**
	 * Order withdrawal endpoint page title.
	 *
	 * @param string $title Default title.
	 * @return string
	 *
	 * @since 11.1.0
	 */
	public function get_endpoint_title( $title ): string {
		return __( 'Withdraw from contract', 'woocommerce' );
	}

	/**
	 * Add the endpoint setting when the feature is enabled.
	 *
	 * @param array $settings Page settings.
	 * @return array
	 *
	 * @since 11.1.0
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
			'default'  => self::ENDPOINT_SLUG,
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
	 *
	 * @since 11.1.0
	 */
	public function render_view(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		wc_get_template( 'myaccount/form-order-withdrawal.php', $this->get_template_args() );
	}

	/**
	 * Get template arguments for the order withdrawal form.
	 *
	 * @return array<string,mixed>
	 */
	private function get_template_args(): array {
		return $this->form_view->get_template_args(
			$this->form_processor->process_current_request(),
			$this->get_form_action_url(),
			$this->get_shop_url()
		);
	}

	/**
	 * Get a safe shop URL for the confirmation link.
	 */
	private function get_shop_url(): string {
		$shop_url = wc_get_page_permalink( 'shop' );

		return $shop_url ? $shop_url : home_url( '/' );
	}

	/**
	 * Get the form action URL.
	 */
	private function get_form_action_url(): string {
		$account_url = wc_get_page_permalink( 'myaccount' );

		return wc_get_endpoint_url( self::ENDPOINT_KEY, '', $account_url ? $account_url : home_url( '/' ) );
	}
}
