<?php
/**
 * WooPaymentsPmPromotionsService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;

/**
 * Provider-level business rules for native WooPayments payment method promotions.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native WooPayments settings runtime.
 */
class WooPaymentsPmPromotionsService {

	public const PROMOTIONS_CACHE_KEY = 'wcpay_pm_promotions';

	public const PROMOTION_DISMISSALS_OPTION = '_wcpay_pm_promotion_dismissals';

	private const SETTINGS_OPTION = 'woocommerce_woocommerce_payments_settings';

	private const VALID_PROMOTION_TYPES = array(
		'badge',
		'spotlight',
	);

	private const VALID_BADGE_TYPES = array(
		'primary',
		'success',
		'light',
		'warning',
		'alert',
	);

	private const PAYMENT_METHOD_TITLES = array(
		'affirm'            => 'Affirm',
		'afterpay_clearpay' => 'Afterpay/Clearpay',
		'alipay'            => 'Alipay',
		'amazon_pay'        => 'Amazon Pay',
		'apple_pay'         => 'Apple Pay',
		'au_becs_debit'     => 'BECS Direct Debit',
		'bancontact'        => 'Bancontact',
		'card'              => 'Card',
		'eps'               => 'EPS',
		'giropay'           => 'giropay',
		'google_pay'        => 'Google Pay',
		'grabpay'           => 'GrabPay',
		'ideal'             => 'iDEAL',
		'jcb'               => 'JCB',
		'klarna'            => 'Klarna',
		'link'              => 'Link',
		'multibanco'        => 'Multibanco',
		'p24'               => 'Przelewy24',
		'sepa_debit'        => 'SEPA Direct Debit',
		'sofort'            => 'Sofort',
		'wechat_pay'        => 'WeChat Pay',
	);

	/**
	 * Native WooPayments API client.
	 *
	 * @var WooPaymentsApiClient|null
	 */
	private ?WooPaymentsApiClient $api_client = null;

	/**
	 * Native WooPayments account service.
	 *
	 * @var WooPaymentsAccountService|null
	 */
	private ?WooPaymentsAccountService $account_service = null;

	/**
	 * Memoized raw promotions.
	 *
	 * @var array<int,array<string,mixed>>|null
	 */
	private ?array $promotions_memo = null;

	/**
	 * Memoized visible promotions. False means not computed, null means no visible promotions.
	 *
	 * @var array<int,array<string,mixed>>|null|false
	 */
	private $visible_promotions_memo = false;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param WooPaymentsApiClient      $api_client      Native WooPayments API client.
	 * @param WooPaymentsAccountService $account_service Native WooPayments account service.
	 */
	final public function init( WooPaymentsApiClient $api_client, WooPaymentsAccountService $account_service ): void {
		$this->api_client      = $api_client;
		$this->account_service = $account_service;
	}

	/**
	 * Clear cached promotions.
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		delete_transient( self::PROMOTIONS_CACHE_KEY );
		$this->reset_memo();
	}

	/**
	 * Reset in-request memoized promotions.
	 *
	 * @return void
	 */
	public function reset_memo(): void {
		$this->promotions_memo         = null;
		$this->visible_promotions_memo = false;
	}

	/**
	 * Get promotions that should be visible to the current merchant.
	 *
	 * @return array<int,array<string,mixed>>|null
	 */
	public function get_visible_promotions(): ?array {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return null;
		}

		if ( false !== $this->visible_promotions_memo ) {
			return $this->visible_promotions_memo;
		}

		$promotions = array_filter(
			$this->get_promotions(),
			function ( $promotion ): bool {
				return $this->validate_promotion( $promotion );
			}
		);
		$promotions = $this->filter_promotions( $promotions );
		$promotions = $this->normalize_promotions( $promotions );

		if ( empty( $promotions ) ) {
			$this->visible_promotions_memo = null;
			return null;
		}

		$this->visible_promotions_memo = array_values( $promotions );

		return $this->visible_promotions_memo;
	}

	/**
	 * Activate a visible promotion and enable its native payment method.
	 *
	 * @param string $id Promotion instance ID.
	 * @return bool
	 */
	public function activate_promotion( string $id ): bool {
		$promotion = $this->find_promotion_by_id( $id );
		if ( null === $promotion ) {
			return false;
		}

		$payment_method_id = $promotion['payment_method'] ?? '';
		if ( '' === $payment_method_id ) {
			return false;
		}

		if ( ! $this->activate_promotion_on_platform( $id, $payment_method_id, $promotion ) ) {
			return false;
		}

		$this->mark_promotion_dismissed( $id );
		if ( ! $this->enable_payment_method( $payment_method_id ) ) {
			return false;
		}

		$this->clear_cache();
		$this->clear_account_cache();
		$this->record_tracks_event(
			'wcpay_payment_method_promotion_activated',
			array(
				'payment_method_id' => $payment_method_id,
				'promo_id'          => $promotion['promo_id'] ?? null,
				'promo_instance_id' => $id,
			)
		);

		return true;
	}

	/**
	 * Activate a visible promotion for a payment method before settings save enables that method.
	 *
	 * @param string $payment_method_id Payment method ID.
	 * @return bool
	 */
	public function maybe_activate_promotion_for_payment_method( string $payment_method_id ): bool {
		$promotion = $this->find_promotion_by_payment_method( $payment_method_id );
		if ( null === $promotion ) {
			return false;
		}

		if ( empty( $promotion['id'] ) || ! is_string( $promotion['id'] ) ) {
			return false;
		}

		if ( ! $this->activate_promotion_on_platform( $promotion['id'], $payment_method_id, $promotion ) ) {
			return false;
		}

		$this->clear_cache();
		$this->clear_account_cache();
		$this->record_tracks_event(
			'wcpay_payment_method_promotion_activated',
			array(
				'payment_method_id' => $payment_method_id,
				'promo_id'          => $promotion['promo_id'] ?? null,
			)
		);

		return true;
	}

	/**
	 * Dismiss a visible promotion.
	 *
	 * @param string $id Promotion instance ID.
	 * @return bool
	 */
	public function dismiss_promotion( string $id ): bool {
		$promotion = $this->find_promotion_by_id( $id );
		if ( null === $promotion || ! $this->mark_promotion_dismissed( $id ) ) {
			return false;
		}

		$this->record_tracks_event(
			'wcpay_payment_method_promotion_dismissed',
			array(
				'payment_method_id' => $promotion['payment_method'] ?? null,
				'promo_id'          => $promotion['promo_id'] ?? null,
				'promo_instance_id' => $id,
			)
		);
		$this->reset_memo();

		return true;
	}

	/**
	 * Get all locally dismissed promotion IDs.
	 *
	 * @return array<string,int>
	 */
	public function get_promotion_dismissals(): array {
		$dismissals = get_option( self::PROMOTION_DISMISSALS_OPTION, array() );
		if ( ! is_array( $dismissals ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $dismissals as $id => $timestamp ) {
			if ( ! is_string( $id ) || ! is_numeric( $timestamp ) ) {
				continue;
			}

			$sanitized[ sanitize_key( $id ) ] = (int) $timestamp;
		}

		return $sanitized;
	}

	/**
	 * Tell whether a promotion has been dismissed.
	 *
	 * @param string $id Promotion instance ID.
	 * @return bool
	 */
	public function is_promotion_dismissed( string $id ): bool {
		$dismissals = $this->get_promotion_dismissals();

		return isset( $dismissals[ $id ] ) && 0 < $dismissals[ $id ] && $dismissals[ $id ] <= time();
	}

	/**
	 * Fetch and cache raw promotions from the provider.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_promotions(): array {
		if ( null !== $this->promotions_memo ) {
			return $this->promotions_memo;
		}

		$cache = get_transient( self::PROMOTIONS_CACHE_KEY );
		if ( is_wp_error( $cache ) ) {
			$this->promotions_memo = array();
			return $this->promotions_memo;
		}

		$store_context      = array(
			'dismissals' => $this->get_promotion_dismissals(),
			'locale'     => get_locale(),
		);
		$store_context_hash = $this->generate_context_hash( $store_context );

		if (
			is_array( $cache )
			&& isset( $cache['context_hash'] )
			&& is_string( $cache['context_hash'] )
			&& hash_equals( $store_context_hash, $cache['context_hash'] )
			&& isset( $cache['promotions'] )
			&& is_array( $cache['promotions'] )
		) {
			$this->promotions_memo = $this->sanitize_raw_promotions_list( $cache['promotions'] );
			return $this->promotions_memo;
		}

		try {
			$response = $this->get_api_client()->get_pm_promotions( $store_context );
		} catch ( WooPaymentsApiException $e ) {
			set_transient(
				self::PROMOTIONS_CACHE_KEY,
				new \WP_Error( $e->get_error_code(), $e->getMessage(), $e->get_http_code() ),
				6 * HOUR_IN_SECONDS
			);
			$this->log_promotion_error( 'Unable to fetch payment method promotions: ' . $e->getMessage() );
			$this->promotions_memo = array();

			return $this->promotions_memo;
		}

		$this->promotions_memo = $this->sanitize_raw_promotions_list( $response );
		set_transient(
			self::PROMOTIONS_CACHE_KEY,
			array(
				'promotions'   => $this->promotions_memo,
				'context_hash' => $store_context_hash,
				'timestamp'    => time(),
			),
			DAY_IN_SECONDS
		);

		return $this->promotions_memo;
	}

	/**
	 * Keep only raw list entries that can represent promotions.
	 *
	 * @param array<int|string,mixed> $promotions Raw response.
	 * @return array<int,array<string,mixed>>
	 */
	private function sanitize_raw_promotions_list( array $promotions ): array {
		$raw_promotions = isset( $promotions['promotions'] ) && is_array( $promotions['promotions'] )
			? $promotions['promotions']
			: $promotions;
		$sanitized      = array();

		foreach ( $raw_promotions as $promotion ) {
			if ( is_array( $promotion ) ) {
				$sanitized[] = $promotion;
			}
		}

		return $sanitized;
	}

	/**
	 * Validate raw promotion shape before filtering.
	 *
	 * @param mixed $promotion Promotion candidate.
	 * @return bool
	 */
	private function validate_promotion( $promotion ): bool {
		if ( ! is_array( $promotion ) || empty( $promotion ) ) {
			return false;
		}

		foreach ( array( 'id', 'promo_id', 'payment_method', 'type', 'title', 'description', 'tc_url' ) as $field ) {
			if ( ! isset( $promotion[ $field ] ) || ! is_string( $promotion[ $field ] ) || '' === $promotion[ $field ] ) {
				return false;
			}
		}

		return in_array( $promotion['type'], self::VALID_PROMOTION_TYPES, true );
	}

	/**
	 * Filter promotions by native eligibility and local dismissal state.
	 *
	 * @param array<int,array<string,mixed>> $promotions Promotions.
	 * @return array<int,array<string,mixed>>
	 */
	private function filter_promotions( array $promotions ): array {
		$enabled_payment_method_ids = $this->get_enabled_payment_method_ids();
		$valid_payment_method_ids   = $this->get_available_payment_method_ids();
		$account_fees               = $this->get_account_fees();
		$seen_promo_ids             = array();
		$filtered                   = array();

		foreach ( $promotions as $promotion ) {
			$id                = (string) ( $promotion['id'] ?? '' );
			$payment_method_id = (string) ( $promotion['payment_method'] ?? '' );
			$promo_id          = (string) ( $promotion['promo_id'] ?? '' );

			if (
				in_array( $payment_method_id, $enabled_payment_method_ids, true )
				|| ! in_array( $payment_method_id, $valid_payment_method_ids, true )
				|| $this->is_promotion_dismissed( $id )
				|| $this->payment_method_has_active_discount( $payment_method_id, $account_fees )
			) {
				continue;
			}

			if ( ! isset( $seen_promo_ids[ $payment_method_id ] ) ) {
				$seen_promo_ids[ $payment_method_id ] = $promo_id;
			}

			if ( $seen_promo_ids[ $payment_method_id ] !== $promo_id ) {
				continue;
			}

			$filtered[] = $promotion;
		}

		return $filtered;
	}

	/**
	 * Normalize and sanitize visible promotions.
	 *
	 * @param array<int,array<string,mixed>> $promotions Promotions.
	 * @return array<int,array<string,mixed>>
	 */
	private function normalize_promotions( array $promotions ): array {
		$normalized = array();

		foreach ( $promotions as $promotion ) {
			$payment_method_id = (string) $promotion['payment_method'];
			$tc_url            = (string) $promotion['tc_url'];

			if ( empty( $promotion['payment_method_title'] ) ) {
				$promotion['payment_method_title'] = $this->get_payment_method_title( $payment_method_id );
			}

			if ( empty( $promotion['cta_label'] ) ) {
				$promotion['cta_label'] = sprintf(
					/* translators: %s: payment method title. */
					__( 'Enable %s', 'woocommerce' ),
					(string) $promotion['payment_method_title']
				);
			}

			$promotion = $this->sanitize_promotion( $promotion );

			if ( empty( $promotion['tc_label'] ) ) {
				$promotion['tc_label'] = false === strpos( (string) $promotion['description'], $tc_url )
					? __( 'See terms', 'woocommerce' )
					: '';
			}

			$normalized[] = $promotion;
		}

		return $normalized;
	}

	/**
	 * Sanitize a promotion payload.
	 *
	 * @param array<string,mixed> $promotion Promotion.
	 * @return array<string,mixed>
	 */
	private function sanitize_promotion( array $promotion ): array {
		foreach ( array( 'id', 'promo_id', 'payment_method', 'type' ) as $field ) {
			if ( isset( $promotion[ $field ] ) ) {
				$promotion[ $field ] = sanitize_key( (string) $promotion[ $field ] );
			}
		}

		foreach ( array( 'payment_method_title', 'title', 'cta_label', 'tc_label', 'badge_text' ) as $field ) {
			if ( isset( $promotion[ $field ] ) ) {
				$promotion[ $field ] = sanitize_text_field( (string) $promotion[ $field ] );
			}
		}

		$promotion['badge_type'] = isset( $promotion['badge_type'] ) && in_array( $promotion['badge_type'], self::VALID_BADGE_TYPES, true )
			? $promotion['badge_type']
			: 'success';

		if ( isset( $promotion['tc_url'] ) ) {
			$promotion['tc_url'] = esc_url_raw( (string) $promotion['tc_url'] );
		}

		if ( isset( $promotion['image'] ) ) {
			$promotion['image'] = esc_url_raw( (string) $promotion['image'] );
		}

		if ( isset( $promotion['description'] ) ) {
			$promotion['description'] = $this->sanitize_description( (string) $promotion['description'], (string) $promotion['type'] );
		}

		if ( isset( $promotion['footnote'] ) ) {
			$promotion['footnote'] = $this->sanitize_light_html( (string) $promotion['footnote'] );
		}

		return $promotion;
	}

	/**
	 * Sanitize a promotion description according to promotion type.
	 *
	 * @param string $description Promotion description.
	 * @param string $type        Promotion type.
	 * @return string
	 */
	private function sanitize_description( string $description, string $type ): string {
		if ( 'badge' === $type ) {
			return wp_kses(
				$description,
				array(
					'a' => array(
						'href'   => true,
						'rel'    => true,
						'target' => true,
					),
				)
			);
		}

		return $this->sanitize_light_html( $description );
	}

	/**
	 * Sanitize light inline HTML used by spotlight copy.
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	private function sanitize_light_html( string $html ): string {
		return wp_kses(
			$html,
			array(
				'a'      => array(
					'href'   => true,
					'rel'    => true,
					'target' => true,
				),
				'br'     => array(),
				'em'     => array(),
				'strong' => array(),
			)
		);
	}

	/**
	 * Find a visible promotion by ID.
	 *
	 * @param string $id Promotion instance ID.
	 * @return array<string,mixed>|null
	 */
	private function find_promotion_by_id( string $id ): ?array {
		$promotions = $this->get_visible_promotions();
		if ( null === $promotions ) {
			return null;
		}

		foreach ( $promotions as $promotion ) {
			if ( isset( $promotion['id'] ) && $promotion['id'] === $id ) {
				return $promotion;
			}
		}

		return null;
	}

	/**
	 * Find a visible promotion by payment method.
	 *
	 * @param string $payment_method_id Payment method ID.
	 * @return array<string,mixed>|null
	 */
	private function find_promotion_by_payment_method( string $payment_method_id ): ?array {
		$promotions = $this->get_visible_promotions();
		if ( null === $promotions ) {
			return null;
		}

		foreach ( $promotions as $promotion ) {
			if ( isset( $promotion['payment_method'] ) && $promotion['payment_method'] === $payment_method_id ) {
				return $promotion;
			}
		}

		return null;
	}

	/**
	 * Activate a promotion through the provider platform.
	 *
	 * @param string              $id                Promotion instance ID.
	 * @param string              $payment_method_id Payment method ID.
	 * @param array<string,mixed> $promotion         Promotion data.
	 * @return bool
	 */
	private function activate_promotion_on_platform( string $id, string $payment_method_id, array $promotion ): bool {
		if ( ! $this->is_payment_method_available( $payment_method_id ) ) {
			$this->handle_promotion_activation_failure(
				$payment_method_id,
				$promotion,
				'Payment method is not available for the connected account.'
			);
			return false;
		}

		try {
			$this->get_api_client()->activate_pm_promotion( $id );
		} catch ( WooPaymentsApiException $e ) {
			$this->handle_promotion_activation_failure( $payment_method_id, $promotion, $e->getMessage() );
			return false;
		}

		return true;
	}

	/**
	 * Mark a promotion dismissed locally.
	 *
	 * @param string $id Promotion instance ID.
	 * @return bool
	 */
	private function mark_promotion_dismissed( string $id ): bool {
		if ( $this->is_promotion_dismissed( $id ) ) {
			return false;
		}

		$dismissals        = $this->get_promotion_dismissals();
		$dismissals[ $id ] = time();

		return update_option( self::PROMOTION_DISMISSALS_OPTION, $dismissals, false );
	}

	/**
	 * Enable a payment method in native WooPayments gateway settings.
	 *
	 * @param string $payment_method_id Payment method ID.
	 * @return bool
	 */
	private function enable_payment_method( string $payment_method_id ): bool {
		if ( ! $this->is_payment_method_available( $payment_method_id ) ) {
			return false;
		}

		$settings = $this->get_gateway_settings();
		$enabled  = isset( $settings['upe_enabled_payment_method_ids'] ) && is_array( $settings['upe_enabled_payment_method_ids'] )
			? $settings['upe_enabled_payment_method_ids']
			: array();
		$enabled  = $this->sanitize_payment_method_ids( $enabled );

		if ( in_array( $payment_method_id, $enabled, true ) ) {
			return true;
		}

		$enabled[]                                  = $payment_method_id;
		$settings['upe_enabled_payment_method_ids'] = $this->sanitize_payment_method_ids( $enabled );

		return update_option( self::SETTINGS_OPTION, $settings );
	}

	/**
	 * Read native WooPayments gateway settings.
	 *
	 * @return array<string,mixed>
	 */
	private function get_gateway_settings(): array {
		$settings = get_option( self::SETTINGS_OPTION, array() );

		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Get enabled native payment method IDs from persisted gateway settings.
	 *
	 * @return string[]
	 */
	private function get_enabled_payment_method_ids(): array {
		$settings = $this->get_gateway_settings();
		$enabled  = isset( $settings['upe_enabled_payment_method_ids'] ) && is_array( $settings['upe_enabled_payment_method_ids'] )
			? $settings['upe_enabled_payment_method_ids']
			: array();

		return $this->sanitize_payment_method_ids( $enabled );
	}

	/**
	 * Get payment method IDs available to the connected account.
	 *
	 * @return string[]
	 */
	private function get_available_payment_method_ids(): array {
		$settings                 = $this->get_gateway_settings();
		$configured_available_ids = $settings['upe_available_payment_methods'] ?? null;
		if ( is_array( $configured_available_ids ) && ! empty( $configured_available_ids ) ) {
			return $this->sanitize_payment_method_ids( $configured_available_ids );
		}

		$account_fees = $this->get_account_fees();
		if ( ! empty( $account_fees ) ) {
			$available_ids = $this->sanitize_payment_method_ids( array_keys( $account_fees ) );
			if ( in_array( 'card', $available_ids, true ) ) {
				$available_ids[] = 'apple_pay';
				$available_ids[] = 'google_pay';
			}

			return array_values( array_unique( $available_ids ) );
		}

		return $this->sanitize_payment_method_ids( array_merge( array( 'card' ), $this->get_enabled_payment_method_ids() ) );
	}

	/**
	 * Tell whether a payment method is available to the connected account.
	 *
	 * @param string $payment_method_id Payment method ID.
	 * @return bool
	 */
	private function is_payment_method_available( string $payment_method_id ): bool {
		return in_array( $payment_method_id, $this->get_available_payment_method_ids(), true );
	}

	/**
	 * Sanitize payment method IDs against the native supported set.
	 *
	 * @param array<int|string,mixed> $payment_method_ids Payment method IDs.
	 * @return string[]
	 */
	private function sanitize_payment_method_ids( array $payment_method_ids ): array {
		$supported = WooPaymentsSettingsService::get_supported_payment_method_ids();
		$sanitized = array();

		foreach ( $payment_method_ids as $payment_method_id ) {
			if ( ! is_scalar( $payment_method_id ) ) {
				continue;
			}

			$payment_method_id = sanitize_key( (string) $payment_method_id );
			if ( in_array( $payment_method_id, $supported, true ) ) {
				$sanitized[] = $payment_method_id;
			}
		}

		return array_values( array_unique( $sanitized ) );
	}

	/**
	 * Get account fees from the cached native account data.
	 *
	 * @return array<string,mixed>
	 */
	private function get_account_fees(): array {
		$account_service = $this->get_account_service();
		$account_data    = $account_service ? $account_service->get_cached_account_data() : array();
		$fees            = $account_data['fees'] ?? array();

		return is_array( $fees ) ? $fees : array();
	}

	/**
	 * Tell whether a payment method already has an active discount.
	 *
	 * @param string              $payment_method_id Payment method ID.
	 * @param array<string,mixed> $account_fees      Account fees.
	 * @return bool
	 */
	private function payment_method_has_active_discount( string $payment_method_id, array $account_fees ): bool {
		if ( empty( $account_fees[ $payment_method_id ] ) || ! is_array( $account_fees[ $payment_method_id ] ) ) {
			return false;
		}

		$payment_method_fees = $account_fees[ $payment_method_id ];
		if ( empty( $payment_method_fees['discount'] ) || ! is_array( $payment_method_fees['discount'] ) ) {
			return false;
		}

		$first_discount = reset( $payment_method_fees['discount'] );

		return is_array( $first_discount ) && ! empty( $first_discount['discount'] );
	}

	/**
	 * Get a display title for a payment method ID.
	 *
	 * @param string $payment_method_id Payment method ID.
	 * @return string
	 */
	private function get_payment_method_title( string $payment_method_id ): string {
		if ( isset( self::PAYMENT_METHOD_TITLES[ $payment_method_id ] ) ) {
			return self::PAYMENT_METHOD_TITLES[ $payment_method_id ];
		}

		return ucwords( str_replace( '_', ' ', $payment_method_id ) );
	}

	/**
	 * Generate a stable cache context hash.
	 *
	 * @param array<string,mixed> $context Store context.
	 * @return string
	 */
	private function generate_context_hash( array $context ): string {
		return md5(
			(string) wp_json_encode(
				array(
					'dismissals' => $context['dismissals'] ?? array(),
					'locale'     => $context['locale'] ?? '',
				)
			)
		);
	}

	/**
	 * Handle promotion activation failures.
	 *
	 * @param string              $payment_method_id Payment method ID.
	 * @param array<string,mixed> $promotion         Promotion data.
	 * @param string              $error_message     Error message.
	 * @return void
	 */
	private function handle_promotion_activation_failure( string $payment_method_id, array $promotion, string $error_message ): void {
		$this->log_promotion_error( sprintf( 'Failed to activate promotion for payment method %1$s: %2$s', $payment_method_id, $error_message ) );
		$this->record_tracks_event(
			'wcpay_payment_method_promotion_activation_failed',
			array(
				'payment_method_id' => $payment_method_id,
				'promo_id'          => $promotion['promo_id'] ?? null,
			)
		);
	}

	/**
	 * Record a Tracks event when the WC Admin helper is available.
	 *
	 * @param string              $event_name Event name.
	 * @param array<string,mixed> $properties Event properties.
	 * @return void
	 */
	private function record_tracks_event( string $event_name, array $properties ): void {
		if ( function_exists( 'wc_admin_record_tracks_event' ) ) {
			wc_admin_record_tracks_event( $event_name, $properties );
		}
	}

	/**
	 * Log a promotion service error.
	 *
	 * @param string $message Log message.
	 * @return void
	 */
	private function log_promotion_error( string $message ): void {
		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->warning( $message, array( 'source' => 'woocommerce-woopayments-promotions' ) );
		}
	}

	/**
	 * Clear account data after successful promotion activation.
	 *
	 * @return void
	 */
	private function clear_account_cache(): void {
		$account_service = $this->get_account_service();
		if ( $account_service instanceof WooPaymentsAccountService ) {
			$account_service->clear_cache();
		}
	}

	/**
	 * Get the API client, resolving lazily when possible.
	 *
	 * @return WooPaymentsApiClient
	 * @throws WooPaymentsApiException When the service is unavailable.
	 */
	private function get_api_client(): WooPaymentsApiClient {
		if ( $this->api_client instanceof WooPaymentsApiClient ) {
			return $this->api_client;
		}

		if ( function_exists( 'wc_get_container' ) ) {
			try {
				$service = wc_get_container()->get( WooPaymentsApiClient::class );
				if ( $service instanceof WooPaymentsApiClient ) {
					$this->api_client = $service;
					return $this->api_client;
				}
			} catch ( \Throwable $e ) {
				// Fall through to the typed provider exception below.
			}
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message is internal application state.
		throw new WooPaymentsApiException( __( 'WooPayments promotions are unavailable.', 'woocommerce' ), 'wcpay_pm_promotions_unavailable', 503 );
	}

	/**
	 * Get the account service, resolving lazily when possible.
	 *
	 * @return WooPaymentsAccountService|null
	 */
	private function get_account_service(): ?WooPaymentsAccountService {
		if ( $this->account_service instanceof WooPaymentsAccountService ) {
			return $this->account_service;
		}

		if ( function_exists( 'wc_get_container' ) ) {
			try {
				$service = wc_get_container()->get( WooPaymentsAccountService::class );
				if ( $service instanceof WooPaymentsAccountService ) {
					$this->account_service = $service;
				}
			} catch ( \Throwable $e ) {
				return null;
			}
		}

		return $this->account_service;
	}
}
