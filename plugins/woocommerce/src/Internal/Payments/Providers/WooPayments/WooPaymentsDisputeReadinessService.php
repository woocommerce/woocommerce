<?php
/**
 * WooPaymentsDisputeReadinessService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Admin\Settings\Utils;

/**
 * Builds the native WooPayments dispute readiness overview payload.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsDisputeReadinessService {

	public const DISMISSAL_OPTION                         = 'wcpay_dispute_readiness_card_dismissed';
	public const STATEMENT_DESCRIPTOR_CONFIRMATION_OPTION = 'wcpay_dispute_readiness_statement_descriptor_confirmed';

	private const SIGNAL_REFUND_POLICY        = 'refund_policy';
	private const SIGNAL_TERMS_AND_CONDITIONS = 'terms_and_conditions';
	private const SIGNAL_STATEMENT_DESCRIPTOR = 'statement_descriptor';
	private const SIGNAL_SUPPORT_CONTACT      = 'support_contact';

	private const STATUS_COMPLETE   = 'complete';
	private const STATUS_INCOMPLETE = 'incomplete';

	/**
	 * WooPayments account service.
	 *
	 * @var WooPaymentsAccountService
	 */
	private WooPaymentsAccountService $account_service;

	/**
	 * Initialize the service.
	 *
	 * @internal
	 *
	 * @param WooPaymentsAccountService $account_service WooPayments account service.
	 */
	final public function init( WooPaymentsAccountService $account_service ): void {
		$this->account_service = $account_service;
	}

	/**
	 * Returns the disabled response used while the feature flag is off.
	 *
	 * @since 11.0.0
	 *
	 * @return array<string,mixed>
	 */
	public function get_disabled_overview_payload(): array {
		return array(
			'overview' => array(
				'enabled'             => false,
				'hidden'              => true,
				'score'               => 0,
				'total'               => 0,
				'state'               => self::STATUS_INCOMPLETE,
				'isDismissed'         => true,
				'completeSignalIds'   => array(),
				'incompleteSignalIds' => array(),
				'signals'             => array(),
				'dismissal'           => array(
					'isDismissed'       => true,
					'isStoredDismissal' => false,
					'reappearReason'    => 'feature_disabled',
				),
			),
		);
	}

	/**
	 * Builds the enabled overview payload.
	 *
	 * @since 11.0.0
	 *
	 * @return array<string,mixed>
	 */
	public function get_overview_payload(): array {
		$signals               = $this->get_signals();
		$total                 = count( $signals );
		$complete_signal_ids   = array();
		$incomplete_signal_ids = array();

		foreach ( $signals as $signal ) {
			if ( self::STATUS_COMPLETE === $signal['status'] ) {
				$complete_signal_ids[] = $signal['id'];
			} else {
				$incomplete_signal_ids[] = $signal['id'];
			}
		}

		$score     = count( $complete_signal_ids );
		$dismissal = $this->get_dismissal_state( $score, $total, $incomplete_signal_ids );

		return array(
			'overview' => array(
				'enabled'             => true,
				'hidden'              => false,
				'score'               => $score,
				'total'               => $total,
				'state'               => $score === $total ? self::STATUS_COMPLETE : self::STATUS_INCOMPLETE,
				'isDismissed'         => $dismissal['isDismissed'],
				'completeSignalIds'   => $complete_signal_ids,
				'incompleteSignalIds' => $incomplete_signal_ids,
				'signals'             => $signals,
				'dismissal'           => $dismissal,
			),
		);
	}

	/**
	 * Stores dismissal metadata for the current overview state.
	 *
	 * @since 11.0.0
	 *
	 * @return array<string,mixed> Updated overview payload.
	 */
	public function dismiss_overview_card(): array {
		$payload  = $this->get_overview_payload();
		$overview = $payload['overview'];

		update_option(
			self::DISMISSAL_OPTION,
			array(
				'dismissed'             => true,
				'dismissed_at'          => gmdate( 'c' ),
				'score_at_dismissal'    => $overview['score'],
				'total_at_dismissal'    => $overview['total'],
				'incomplete_signal_ids' => $overview['incompleteSignalIds'],
			),
			false
		);

		return $this->get_overview_payload();
	}

	/**
	 * Confirms that the current statement descriptor clearly identifies the store.
	 *
	 * @since 11.0.0
	 *
	 * @return array<string,mixed> Updated overview payload.
	 */
	public function confirm_statement_descriptor(): array {
		$descriptor = $this->get_statement_descriptor( $this->get_preserved_account_data() );

		update_option(
			self::STATEMENT_DESCRIPTOR_CONFIRMATION_OPTION,
			array(
				'confirmed'             => true,
				'confirmed_at'          => gmdate( 'c' ),
				'descriptor'            => $descriptor,
				'normalized_descriptor' => $this->normalize_descriptor_value( $descriptor ),
			),
			false
		);

		return $this->get_overview_payload();
	}

	/**
	 * Builds all overview signals.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_signals(): array {
		$account_data = $this->get_preserved_account_data();

		return array(
			$this->get_statement_descriptor_signal( $account_data ),
			$this->get_refund_policy_signal(),
			$this->get_support_contact_signal( $account_data ),
			$this->get_terms_and_conditions_signal(),
		);
	}

	/**
	 * Returns the refund policy signal.
	 *
	 * @return array<string,mixed>
	 */
	private function get_refund_policy_signal(): array {
		$page_id = (int) get_option( 'woocommerce_refund_returns_page_id', 0 );
		$status  = $this->is_complete_page( $page_id ) ? self::STATUS_COMPLETE : self::STATUS_INCOMPLETE;

		return array(
			'id'          => self::SIGNAL_REFUND_POLICY,
			'status'      => $status,
			'label'       => __( 'Refund policy page published', 'woocommerce' ),
			'description' => __( 'Publish a refund policy so customers can resolve issues with you before filing a dispute.', 'woocommerce' ),
			'actionLabel' => __( 'Fix it', 'woocommerce' ),
			'actionUrl'   => $this->get_page_action_url( $page_id ),
		);
	}

	/**
	 * Returns the terms and conditions signal.
	 *
	 * @return array<string,mixed>
	 */
	private function get_terms_and_conditions_signal(): array {
		$page_id = function_exists( 'wc_terms_and_conditions_page_id' ) ? (int) wc_terms_and_conditions_page_id() : (int) get_option( 'woocommerce_terms_page_id', 0 );
		$status  = $this->is_complete_page( $page_id ) ? self::STATUS_COMPLETE : self::STATUS_INCOMPLETE;

		return array(
			'id'          => self::SIGNAL_TERMS_AND_CONDITIONS,
			'status'      => $status,
			'label'       => __( 'Terms & conditions linked at checkout', 'woocommerce' ),
			'description' => __( 'Add a T&C link at checkout so customers acknowledge your policies before completing a purchase.', 'woocommerce' ),
			'actionLabel' => __( 'Fix it', 'woocommerce' ),
			'actionUrl'   => $this->get_woocommerce_advanced_settings_url(),
		);
	}

	/**
	 * Returns the statement descriptor signal.
	 *
	 * @param array<string,mixed> $account_data Preserved account data.
	 * @return array<string,mixed>
	 */
	private function get_statement_descriptor_signal( array $account_data ): array {
		$descriptor    = $this->get_statement_descriptor( $account_data );
		$trimmed       = trim( $descriptor );
		$is_empty      = '' === $trimmed;
		$needs_review  = ! $is_empty && ! $this->looks_like_recognizable_descriptor( $descriptor, $account_data );
		$is_confirmed  = $needs_review && $this->is_statement_descriptor_confirmed( $descriptor );
		$status        = ! $is_empty && ( ! $needs_review || $is_confirmed ) ? self::STATUS_COMPLETE : self::STATUS_INCOMPLETE;
		$review_prompt = null;

		if ( self::STATUS_INCOMPLETE === $status && $needs_review ) {
			$review_prompt = array(
				'text'              => __( "Your statement descriptor will show up on your customers' bank statements. Does it clearly identify your store?", 'woocommerce' ),
				'currentDescriptor' => $trimmed,
				'confirmLabel'      => __( 'Looks good', 'woocommerce' ),
				'updateLabel'       => __( 'Update', 'woocommerce' ),
			);
		}

		$signal = array(
			'id'          => self::SIGNAL_STATEMENT_DESCRIPTOR,
			'status'      => $status,
			'label'       => __( 'Recognizable statement descriptor', 'woocommerce' ),
			'description' => __( 'Make sure your business name appears clearly on customer bank statements to prevent confusion.', 'woocommerce' ),
			'actionLabel' => __( 'Fix it', 'woocommerce' ),
			'actionUrl'   => $this->get_woopayments_settings_url(),
			'reason'      => $this->get_statement_descriptor_reason( $descriptor, $needs_review, $is_confirmed ),
		);

		if ( null !== $review_prompt ) {
			$signal['reviewPrompt'] = $review_prompt;
		}

		return $signal;
	}

	/**
	 * Returns the support contact signal.
	 *
	 * @param array<string,mixed> $account_data Preserved account data.
	 * @return array<string,mixed>
	 */
	private function get_support_contact_signal( array $account_data ): array {
		$business_profile = isset( $account_data['business_profile'] ) && is_array( $account_data['business_profile'] ) ? $account_data['business_profile'] : array();
		$support_email    = isset( $business_profile['support_email'] ) ? trim( (string) $business_profile['support_email'] ) : '';
		$support_phone    = isset( $business_profile['support_phone'] ) ? trim( (string) $business_profile['support_phone'] ) : '';
		$status           = '' !== $support_email || '' !== $support_phone ? self::STATUS_COMPLETE : self::STATUS_INCOMPLETE;

		return array(
			'id'          => self::SIGNAL_SUPPORT_CONTACT,
			'status'      => $status,
			'label'       => __( 'Customer support contact linked in order emails', 'woocommerce' ),
			'description' => __( 'Give customers a direct way to reach you from their order emails to handle issues quickly.', 'woocommerce' ),
			'actionLabel' => __( 'Fix it', 'woocommerce' ),
			'actionUrl'   => $this->get_woopayments_settings_url(),
		);
	}

	/**
	 * Returns preserved account data without forcing a platform refresh.
	 *
	 * @return array<string,mixed>
	 */
	private function get_preserved_account_data(): array {
		return $this->account_service->get_preserved_account_data_snapshot();
	}

	/**
	 * Determines whether a configured page exists, is published, and has meaningful content.
	 *
	 * @param int $page_id Page ID.
	 * @return bool
	 */
	private function is_complete_page( int $page_id ): bool {
		if ( ! $this->is_published_page( $page_id ) ) {
			return false;
		}

		$page = get_post( $page_id );
		if ( ! $page instanceof \WP_Post ) {
			return false;
		}

		$content = strip_shortcodes( (string) $page->post_content );
		$content = wp_strip_all_tags( $content );
		$content = preg_replace( '/\s+/', '', $content );

		return '' !== $content;
	}

	/**
	 * Determines whether a configured page exists and is published.
	 *
	 * @param int $page_id Page ID.
	 * @return bool
	 */
	private function is_published_page( int $page_id ): bool {
		if ( $page_id <= 0 ) {
			return false;
		}

		$page = get_post( $page_id );

		return $page && 'publish' === $page->post_status;
	}

	/**
	 * Returns the edit URL for an assigned page or the WooCommerce page setup settings URL.
	 *
	 * @param int $page_id Page ID.
	 * @return string
	 */
	private function get_page_action_url( int $page_id ): string {
		if ( $page_id > 0 && get_post( $page_id ) ) {
			$edit_post_link = get_edit_post_link( $page_id, '' );

			return $edit_post_link ? $edit_post_link : admin_url( 'post.php?post=' . $page_id . '&action=edit' );
		}

		return $this->get_woocommerce_advanced_settings_url();
	}

	/**
	 * Returns the WooCommerce advanced settings URL.
	 *
	 * @return string
	 */
	private function get_woocommerce_advanced_settings_url(): string {
		return admin_url( 'admin.php?page=wc-settings&tab=advanced' );
	}

	/**
	 * Returns the native WooPayments settings URL.
	 *
	 * @return string
	 */
	private function get_woopayments_settings_url(): string {
		return Utils::wc_payments_settings_url( '/woopayments/settings' );
	}

	/**
	 * Returns the current dismissal state.
	 *
	 * @param int      $score                 Current score.
	 * @param int      $total                 Current total.
	 * @param string[] $incomplete_signal_ids Current incomplete signal IDs.
	 * @return array<string,mixed>
	 */
	private function get_dismissal_state( int $score, int $total, array $incomplete_signal_ids ): array {
		$stored = get_option( self::DISMISSAL_OPTION, array() );
		if ( ! is_array( $stored ) || empty( $stored['dismissed'] ) ) {
			return array(
				'isDismissed'       => false,
				'isStoredDismissal' => false,
				'reappearReason'    => null,
			);
		}

		$score_at_dismissal     = isset( $stored['score_at_dismissal'] ) ? (int) $stored['score_at_dismissal'] : 0;
		$total_at_dismissal     = isset( $stored['total_at_dismissal'] ) ? (int) $stored['total_at_dismissal'] : 0;
		$stored_incomplete_ids  = isset( $stored['incomplete_signal_ids'] ) && is_array( $stored['incomplete_signal_ids'] ) ? array_values( $stored['incomplete_signal_ids'] ) : array();
		$current_incomplete_ids = array_values( $incomplete_signal_ids );
		$reappear_reason        = null;

		sort( $stored_incomplete_ids );
		sort( $current_incomplete_ids );

		if ( $total !== $total_at_dismissal ) {
			$reappear_reason = 'total_changed';
		} elseif ( $score < $score_at_dismissal ) {
			$reappear_reason = 'score_decreased';
		} elseif ( $score === $score_at_dismissal && $current_incomplete_ids !== $stored_incomplete_ids ) {
			$reappear_reason = 'incomplete_signals_changed';
		}

		return array(
			'isDismissed'         => null === $reappear_reason,
			'isStoredDismissal'   => true,
			'reappearReason'      => $reappear_reason,
			'dismissedAt'         => isset( $stored['dismissed_at'] ) ? (string) $stored['dismissed_at'] : null,
			'scoreAtDismissal'    => $score_at_dismissal,
			'totalAtDismissal'    => $total_at_dismissal,
			'incompleteSignalIds' => $stored_incomplete_ids,
		);
	}

	/**
	 * Returns the current account statement descriptor.
	 *
	 * @param array<string,mixed> $account_data Preserved account data.
	 * @return string
	 */
	private function get_statement_descriptor( array $account_data ): string {
		return isset( $account_data['statement_descriptor'] ) ? (string) $account_data['statement_descriptor'] : '';
	}

	/**
	 * Returns the reason code for the statement descriptor signal.
	 *
	 * @param string $descriptor   Statement descriptor.
	 * @param bool   $needs_review Whether the descriptor needs review.
	 * @param bool   $is_confirmed Whether the merchant confirmed the descriptor.
	 * @return string
	 */
	private function get_statement_descriptor_reason( string $descriptor, bool $needs_review, bool $is_confirmed ): string {
		if ( '' === trim( $descriptor ) ) {
			return 'empty';
		}

		if ( $is_confirmed ) {
			return 'confirmed';
		}

		return $needs_review ? 'needs_review' : 'looks_recognizable';
	}

	/**
	 * Determines whether a descriptor looks recognizable enough to complete automatically.
	 *
	 * @param string              $descriptor   Statement descriptor.
	 * @param array<string,mixed> $account_data Preserved account data.
	 * @return bool
	 */
	private function looks_like_recognizable_descriptor( string $descriptor, array $account_data ): bool {
		$normalized_descriptor = $this->normalize_descriptor_value( $descriptor );
		if ( '' === $normalized_descriptor || $this->is_generic_descriptor_value( $normalized_descriptor ) ) {
			return false;
		}

		foreach ( $this->get_store_descriptor_candidates( $account_data ) as $candidate ) {
			$normalized_candidate = $this->normalize_descriptor_value( (string) $candidate );
			if ( '' === $normalized_candidate || $this->is_generic_descriptor_value( $normalized_candidate ) ) {
				continue;
			}

			if ( $normalized_descriptor === $normalized_candidate ) {
				return true;
			}
		}

		return strlen( $normalized_descriptor ) >= 5;
	}

	/**
	 * Determines whether the merchant confirmed the current descriptor.
	 *
	 * @param string $descriptor Statement descriptor.
	 * @return bool
	 */
	private function is_statement_descriptor_confirmed( string $descriptor ): bool {
		$stored = get_option( self::STATEMENT_DESCRIPTOR_CONFIRMATION_OPTION, array() );
		if ( ! is_array( $stored ) || empty( $stored['confirmed'] ) ) {
			return false;
		}

		$stored_descriptor = isset( $stored['normalized_descriptor'] ) ? (string) $stored['normalized_descriptor'] : '';

		return '' !== $stored_descriptor && $stored_descriptor === $this->normalize_descriptor_value( $descriptor );
	}

	/**
	 * Returns possible store-specific descriptor comparison values.
	 *
	 * @param array<string,mixed> $account_data Preserved account data.
	 * @return string[]
	 */
	private function get_store_descriptor_candidates( array $account_data ): array {
		$candidates = array_merge(
			array(
				get_bloginfo( 'name' ),
				get_home_url(),
				get_site_url(),
			),
			$this->get_url_candidates( get_home_url() ),
			$this->get_url_candidates( get_site_url() )
		);

		$business_profile = isset( $account_data['business_profile'] ) && is_array( $account_data['business_profile'] ) ? $account_data['business_profile'] : array();
		if ( ! empty( $business_profile['url'] ) ) {
			$candidates = array_merge( $candidates, $this->get_url_candidates( (string) $business_profile['url'] ) );
		}

		return array_values( array_unique( array_filter( $candidates ) ) );
	}

	/**
	 * Returns host/path candidates for a URL.
	 *
	 * @param string $url URL.
	 * @return string[]
	 */
	private function get_url_candidates( string $url ): array {
		$host = (string) wp_parse_url( $url, PHP_URL_HOST );
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );

		return array_values(
			array_filter(
				array(
					$url,
					$host,
					preg_replace( '/^www\./i', '', $host ),
					trim( $path, '/' ),
				)
			)
		);
	}

	/**
	 * Determines whether a normalized descriptor value is generic.
	 *
	 * @param string $normalized_descriptor Normalized descriptor.
	 * @return bool
	 */
	private function is_generic_descriptor_value( string $normalized_descriptor ): bool {
		$generic_values = array(
			'woocommerce',
			'woocommercepayments',
			'woopayments',
			'woopaymentsstore',
			'mystore',
			'teststore',
			'store',
			'shop',
			'onlinestore',
		);

		foreach ( $generic_values as $generic_value ) {
			if ( $normalized_descriptor === $generic_value || 0 === strpos( $normalized_descriptor, $generic_value ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Normalizes values for statement descriptor comparison.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private function normalize_descriptor_value( string $value ): string {
		$value = strtolower( trim( $value ) );
		$value = preg_replace( '#^https?://#', '', $value );
		$value = is_string( $value ) ? $value : '';
		$value = preg_replace( '#^www\.#', '', $value );
		$value = is_string( $value ) ? $value : '';
		$value = preg_replace( '/[^a-z0-9]/', '', $value );

		return is_string( $value ) ? $value : '';
	}
}
