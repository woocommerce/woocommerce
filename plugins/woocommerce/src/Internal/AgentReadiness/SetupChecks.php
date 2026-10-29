<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

defined( 'ABSPATH' ) || exit;

/**
 * The setup checks on the Agentic commerce settings page. A failing required
 * check keeps UCP from being turned on; a failing recommended one is a warning.
 * Permalinks and guest checkout come from StoreConditions, so both screens
 * judge them the same way.
 *
 * @since 11.4.0
 */
class SetupChecks {

	public const LEVEL_REQUIRED = 'required';

	public const LEVEL_RECOMMENDED = 'recommended';

	/**
	 * Store-wide conditions.
	 *
	 * @var StoreConditions
	 */
	private StoreConditions $store_conditions;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param StoreConditions $store_conditions Store-wide conditions.
	 */
	final public function init( StoreConditions $store_conditions ): void {
		$this->store_conditions = $store_conditions;
	}

	/**
	 * Every check with its level, current state and where to fix it.
	 *
	 * @return array<int, array{id:string,level:string,ok:bool,label:string,fix_url:string}>
	 */
	public function all(): array {
		$conditions = array_column( $this->store_conditions->all(), 'ok', 'id' );
		$page_fix   = static fn( string $option ): string => '#' . $option;

		return array(
			array(
				'id'      => 'https',
				'level'   => self::LEVEL_REQUIRED,
				'ok'      => wc_site_is_https(),
				'label'   => __( 'Site uses HTTPS', 'woocommerce' ),
				'fix_url' => wp_get_update_https_url(),
			),
			array(
				'id'      => 'pretty_permalinks',
				'level'   => self::LEVEL_REQUIRED,
				'ok'      => (bool) ( $conditions['pretty_permalinks'] ?? false ),
				'label'   => __( 'Permalinks are not set to Plain', 'woocommerce' ),
				'fix_url' => admin_url( 'options-permalink.php' ),
			),
			array(
				'id'      => 'privacy_page',
				'level'   => self::LEVEL_REQUIRED,
				'ok'      => self::is_published_page( (int) get_option( AgenticCommerceSettings::PRIVACY_PAGE_OPTION ) ),
				'label'   => __( 'Privacy policy page is selected', 'woocommerce' ),
				'fix_url' => $page_fix( AgenticCommerceSettings::PRIVACY_PAGE_OPTION ),
			),
			array(
				'id'      => 'terms_page',
				'level'   => self::LEVEL_REQUIRED,
				'ok'      => self::is_published_page( (int) get_option( AgenticCommerceSettings::TERMS_PAGE_OPTION ) ),
				'label'   => __( 'Terms and conditions page is selected', 'woocommerce' ),
				'fix_url' => $page_fix( AgenticCommerceSettings::TERMS_PAGE_OPTION ),
			),
			array(
				'id'      => 'refund_returns_page',
				'level'   => self::LEVEL_REQUIRED,
				'ok'      => self::is_published_page( (int) get_option( AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION ) ),
				'label'   => __( 'Refund and returns page is selected', 'woocommerce' ),
				'fix_url' => $page_fix( AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION ),
			),
			array(
				'id'      => 'shipping_policy_page',
				'level'   => self::LEVEL_RECOMMENDED,
				'ok'      => self::is_published_page( (int) get_option( AgenticCommerceSettings::SHIPPING_POLICY_PAGE_OPTION ) ),
				'label'   => __( 'Shipping policy page is selected', 'woocommerce' ),
				'fix_url' => $page_fix( AgenticCommerceSettings::SHIPPING_POLICY_PAGE_OPTION ),
			),
			array(
				'id'      => 'guest_checkout',
				'level'   => self::LEVEL_RECOMMENDED,
				'ok'      => (bool) ( $conditions['guest_checkout'] ?? false ),
				'label'   => __( 'Guest checkout is allowed, so agent shoppers don\'t have to log in at checkout', 'woocommerce' ),
				'fix_url' => admin_url( 'admin.php?page=wc-settings&tab=account' ),
			),
			array(
				'id'      => 'order_emails',
				'level'   => self::LEVEL_RECOMMENDED,
				'ok'      => $this->order_confirmation_email_enabled(),
				'label'   => __( 'Order confirmation emails are on', 'woocommerce' ),
				'fix_url' => admin_url( 'admin.php?page=wc-settings&tab=email&section=wc_email_customer_processing_order' ),
			),
		);
	}

	/**
	 * Ids of the required checks that fail.
	 *
	 * @param array<int, array{id:string,level:string,ok:bool,label:string,fix_url:string}>|null $checks Checks from all(), or null to run them.
	 * @return array<int, string>
	 */
	public function failing_required( ?array $checks = null ): array {
		$ids = array();
		foreach ( $checks ?? $this->all() as $check ) {
			if ( self::LEVEL_REQUIRED === $check['level'] && ! $check['ok'] ) {
				$ids[] = $check['id'];
			}
		}
		return $ids;
	}

	/**
	 * Whether a page id points at a published page.
	 *
	 * @param int $page_id Page id.
	 */
	public static function is_published_page( int $page_id ): bool {
		return $page_id > 0 && 'page' === get_post_type( $page_id ) && 'publish' === get_post_status( $page_id );
	}

	/**
	 * Whether the order confirmation email shoppers get after checkout is on.
	 */
	private function order_confirmation_email_enabled(): bool {
		$emails = WC()->mailer()->get_emails();
		$email  = $emails['WC_Email_Customer_Processing_Order'] ?? null;
		return $email instanceof \WC_Email && $email->is_enabled();
	}
}
