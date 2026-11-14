<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonHelper;
use Automattic\WooCommerce\StoreApi\Utilities\Ucp\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Store settings that stop agents finding or buying the store's products,
 * whatever the product data. The readiness report and the Agentic commerce
 * settings page both read this list, so they never disagree.
 *
 * `ok` says whether the store is clear of the problem the label names, or is
 * null when it couldn't be checked (StoreOutputChecks); `name` is the check
 * worded as its passing state. `group` is the merchant question the check
 * answers on Sales readiness, as for the store output checks.
 * `blocks` says what a failing condition stops: `listing` hides every product
 * on every channel, `ucp` hides the catalog from agents that use UCP,
 * `checkout` stops or weakens agent orders. A failing `required` condition
 * keeps UCP from being turned on; a failing `recommended` one is a warning.
 *
 * @since 11.4.0
 */
class StoreConditions {

	public const LEVEL_REQUIRED = 'required';

	/**
	 * Recommended conditions shown as Required, which don't keep AI shopping from being turned on:
	 * AI assistants read the catalog through the API whatever search engines are told, and with no
	 * payment method no sale goes through.
	 */
	public const SHOWN_REQUIRED = array( 'search_visible', 'payment_method' );

	public const LEVEL_RECOMMENDED = 'recommended';

	/**
	 * Coming soon mode helper.
	 *
	 * @var ComingSoonHelper
	 */
	private ComingSoonHelper $coming_soon_helper;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param ComingSoonHelper $coming_soon_helper Coming soon mode helper.
	 */
	final public function init( ComingSoonHelper $coming_soon_helper ): void {
		$this->coming_soon_helper = $coming_soon_helper;
	}

	/**
	 * Every condition with its current state.
	 *
	 * @return array<int, array{id:string,ok:bool,blocks:string,group:string,level:string,name:string,label:string,why:string,fix:string,fix_url:string}>
	 */
	public function all(): array {
		$settings_url = static fn( string $tab ): string => admin_url( 'admin.php?page=wc-settings&tab=' . $tab );
		$policy_page  = static fn( string $id, string $option, string $name, string $label, string $why ): array => array(
			'id'      => $id,
			'ok'      => self::is_policy_page( (int) get_option( $option ) ),
			'blocks'  => 'checkout',
			'group'   => 'buy',
			'level'   => self::LEVEL_RECOMMENDED,
			'name'    => $name,
			'label'   => $label,
			'why'     => $why,
			'fix'     => __( 'Choose a published page under WooCommerce > Settings > AI tools > AI shopping.', 'woocommerce' ),
			'fix_url' => AgenticCommerceSettings::url( AgenticCommerceSettings::SHOPPING_SECTION ) . '#' . $option,
		);

		return array(
			array(
				'id'      => 'launched',
				'ok'      => $this->coming_soon_helper->is_site_live(),
				'blocks'  => 'listing',
				'group'   => 'access',
				'level'   => self::LEVEL_REQUIRED,
				'name'    => __( 'Store is launched', 'woocommerce' ),
				'label'   => __( 'Store is in coming soon mode', 'woocommerce' ),
				'why'     => __( 'Visitors, crawlers and shopping agents see a holding page instead of your products.', 'woocommerce' ),
				'fix'     => __( 'Launch the store under WooCommerce > Settings > Site visibility.', 'woocommerce' ),
				'fix_url' => $settings_url( 'site-visibility' ),
			),
			array(
				'id'      => 'search_visible',
				'ok'      => '0' !== (string) get_option( 'blog_public', '1' ),
				'blocks'  => 'listing',
				'group'   => 'find',
				'level'   => self::LEVEL_RECOMMENDED,
				'name'    => __( 'Search engines can index the store', 'woocommerce' ),
				'label'   => __( 'Store is hidden from search engines', 'woocommerce' ),
				'why'     => __( '"Discourage search engines" asks crawlers, including AI shopping crawlers, to skip your store.', 'woocommerce' ),
				'fix'     => __( 'Clear "Discourage search engines from indexing this site" under Settings > Reading.', 'woocommerce' ),
				'fix_url' => admin_url( 'options-reading.php' ),
			),
			array(
				'id'      => 'ucp_on',
				'ok'      => Utils::is_enabled(),
				'blocks'  => 'ucp',
				'group'   => 'access',
				'level'   => self::LEVEL_RECOMMENDED,
				'name'    => __( 'Universal Commerce Protocol (UCP) is on', 'woocommerce' ),
				'label'   => __( 'Universal Commerce Protocol (UCP) is off', 'woocommerce' ),
				'why'     => __( 'Shopping agents that use UCP cannot read your catalog.', 'woocommerce' ),
				'fix'     => __( 'Turn on "Allow AI assistants to shop your store" under WooCommerce > Settings > AI tools > AI shopping.', 'woocommerce' ),
				'fix_url' => AgenticCommerceSettings::url( AgenticCommerceSettings::SHOPPING_SECTION ),
			),
			array(
				'id'      => 'https',
				'ok'      => wc_site_is_https(),
				'blocks'  => 'ucp',
				'group'   => 'access',
				'level'   => self::LEVEL_REQUIRED,
				'name'    => __( 'Site uses HTTPS', 'woocommerce' ),
				'label'   => __( 'Site does not use HTTPS', 'woocommerce' ),
				'why'     => __( 'Agents only send shoppers to a secure checkout.', 'woocommerce' ),
				'fix'     => __( 'Move the site to HTTPS; your host can usually turn it on.', 'woocommerce' ),
				'fix_url' => wp_get_update_https_url(),
			),
			$policy_page(
				'privacy_page',
				AgenticCommerceSettings::PRIVACY_PAGE_OPTION,
				__( 'Privacy policy page is selected', 'woocommerce' ),
				__( 'No privacy policy page', 'woocommerce' ),
				__( 'Agents will be able to link shoppers to your privacy policy before they buy.', 'woocommerce' )
			),
			$policy_page(
				'terms_page',
				AgenticCommerceSettings::TERMS_PAGE_OPTION,
				__( 'Terms and conditions page is selected', 'woocommerce' ),
				__( 'No terms and conditions page', 'woocommerce' ),
				__( 'Agents will be able to link shoppers to your terms before they buy.', 'woocommerce' )
			),
			$policy_page(
				'refund_returns_page',
				AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION,
				__( 'Refund and returns page is selected', 'woocommerce' ),
				__( 'No refund and returns page', 'woocommerce' ),
				__( 'Agents will be able to link shoppers to your return terms before they buy.', 'woocommerce' )
			),
			$policy_page(
				'shipping_policy_page',
				AgenticCommerceSettings::SHIPPING_POLICY_PAGE_OPTION,
				__( 'Shipping policy page is selected', 'woocommerce' ),
				__( 'No shipping policy page', 'woocommerce' ),
				__( 'Shoppers ask agents about delivery times and costs.', 'woocommerce' )
			),
			array(
				'id'      => 'payment_method',
				'ok'      => $this->payment_method_enabled(),
				'blocks'  => 'checkout',
				'group'   => 'buy',
				'level'   => self::LEVEL_RECOMMENDED,
				'name'    => __( 'A payment method is turned on', 'woocommerce' ),
				'label'   => __( 'No payment method is turned on', 'woocommerce' ),
				'why'     => __( 'Shoppers sent by an agent cannot pay for their order.', 'woocommerce' ),
				'fix'     => __( 'Turn on a payment method under WooCommerce > Settings > Payments.', 'woocommerce' ),
				'fix_url' => $settings_url( 'checkout' ),
			),
			array(
				'id'      => 'guest_checkout',
				'ok'      => ! $this->registration_required(),
				'blocks'  => 'checkout',
				'group'   => 'buy',
				'level'   => self::LEVEL_RECOMMENDED,
				'name'    => __( 'Guest checkout is allowed', 'woocommerce' ),
				'label'   => __( 'Guest checkout is off', 'woocommerce' ),
				'why'     => __( 'Shopping agents check out for shoppers without an account on your store, so they cannot place orders.', 'woocommerce' ),
				'fix'     => __( 'Tick "Enable guest checkout" under WooCommerce > Settings > Accounts & Privacy.', 'woocommerce' ),
				'fix_url' => $settings_url( 'account' ),
			),
			array(
				'id'      => 'order_emails',
				'ok'      => $this->order_confirmation_email_enabled(),
				'blocks'  => 'checkout',
				'group'   => 'buy',
				'level'   => self::LEVEL_RECOMMENDED,
				'name'    => __( 'Order confirmation emails are on', 'woocommerce' ),
				'label'   => __( 'Order confirmation emails are off', 'woocommerce' ),
				'why'     => __( 'Shoppers who buy through an agent get no confirmation from your store.', 'woocommerce' ),
				'fix'     => __( 'Turn on the Processing or Completed order email under WooCommerce > Settings > Emails.', 'woocommerce' ),
				'fix_url' => $settings_url( 'email&section=wc_email_customer_processing_order' ),
			),
		);
	}

	/**
	 * Whether a condition is shown as Required: a required one, or one in SHOWN_REQUIRED.
	 *
	 * @param array{id:string,level:string} $condition Condition.
	 */
	public static function shows_required( array $condition ): bool {
		return self::LEVEL_REQUIRED === $condition['level'] || in_array( $condition['id'], self::SHOWN_REQUIRED, true );
	}

	/**
	 * Ids of the required conditions that fail.
	 *
	 * @param array<int, array{id:string,ok:bool|null,level:string}>|null $conditions Conditions from all(), or null to check them now.
	 * @return array<int, string>
	 */
	public function failing_required( ?array $conditions = null ): array {
		$ids = array();
		foreach ( $conditions ?? $this->all() as $condition ) {
			if ( self::LEVEL_REQUIRED === $condition['level'] && false === $condition['ok'] ) {
				$ids[] = $condition['id'];
			}
		}
		return $ids;
	}

	/**
	 * Whether a page id is a published page other than the cart, checkout, shop or My account page.
	 *
	 * @param int $page_id Page id.
	 */
	public static function is_policy_page( int $page_id ): bool {
		return $page_id > 0 && 'page' === get_post_type( $page_id ) && 'publish' === get_post_status( $page_id ) && ! in_array( $page_id, self::store_page_ids(), true );
	}

	/**
	 * The cart, checkout, shop and My account pages, which can't be a policy page.
	 * Advanced > Page setup leaves them out of its terms and refund fields too.
	 *
	 * @return array<int, int>
	 */
	public static function store_page_ids(): array {
		return array_values(
			array_filter(
				array_map( 'wc_get_page_id', array( 'cart', 'checkout', 'shop', 'myaccount' ) ),
				static fn ( $page_id ) => $page_id > 0
			)
		);
	}

	/**
	 * Whether checkout requires an account, as WC_Checkout::is_registration_required()
	 * answers it, without starting the checkout in a background scan.
	 */
	private function registration_required(): bool {
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Core's hook, documented in WC_Checkout.
		return (bool) apply_filters( 'woocommerce_checkout_registration_required', 'yes' !== get_option( 'woocommerce_enable_guest_checkout' ) );
	}

	/**
	 * Whether any payment gateway is turned on. Whether it can take a given order
	 * depends on the cart, so this reads the setting, not is_available().
	 */
	private function payment_method_enabled(): bool {
		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
			if ( $gateway instanceof \WC_Payment_Gateway && 'yes' === $gateway->enabled ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Whether a shopper gets an order email after checkout. Virtual orders skip
	 * processing and are confirmed by the Completed order email instead.
	 */
	private function order_confirmation_email_enabled(): bool {
		$emails = WC()->mailer()->get_emails();
		foreach ( array( 'WC_Email_Customer_Processing_Order', 'WC_Email_Customer_Completed_Order' ) as $id ) {
			$email = $emails[ $id ] ?? null;
			if ( $email instanceof \WC_Email && $email->is_enabled() ) {
				return true;
			}
		}
		return false;
	}
}
