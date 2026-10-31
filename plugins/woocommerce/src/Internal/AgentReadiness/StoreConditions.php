<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonHelper;
use Automattic\WooCommerce\StoreApi\Utilities\UcpUtils;

defined( 'ABSPATH' ) || exit;

/**
 * Store settings that stop agents finding or buying the store's products,
 * whatever the product data. The readiness report and the Agentic commerce
 * settings page both read this list, so they never disagree.
 *
 * `ok` says whether the store is clear of the problem the label names, or is
 * null when it couldn't be checked (StoreOutputChecks); `name` is the check
 * worded as its passing state.
 * `blocks` says what a failing condition stops: `listing` hides every product
 * on every channel, `ucp` hides the catalog from agents that use UCP,
 * `checkout` stops or weakens agent orders. A failing `required` condition
 * keeps UCP from being turned on; a failing `recommended` one is a warning.
 *
 * @since 11.4.0
 */
class StoreConditions {

	public const LEVEL_REQUIRED = 'required';

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
	 * @return array<int, array{id:string,ok:bool,blocks:string,level:string,name:string,label:string,why:string,fix:string,fix_url:string}>
	 */
	public function all(): array {
		global $wp_rewrite;

		$settings_url = static fn( string $tab ): string => admin_url( 'admin.php?page=wc-settings&tab=' . $tab );
		$policy_page  = static fn( string $id, string $option, string $name, string $label, string $why ): array => array(
			'id'      => $id,
			'ok'      => self::is_published_page( (int) get_option( $option ) ),
			'blocks'  => 'checkout',
			'level'   => self::LEVEL_RECOMMENDED,
			'name'    => $name,
			'label'   => $label,
			'why'     => $why,
			'fix'     => __( 'Choose a published page under WooCommerce > Settings > Agentic commerce.', 'woocommerce' ),
			'fix_url' => $settings_url( AgenticCommerceSettings::TAB ) . '#' . $option,
		);

		return array(
			array(
				'id'      => 'launched',
				'ok'      => $this->coming_soon_helper->is_site_live(),
				'blocks'  => 'listing',
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
				'level'   => self::LEVEL_RECOMMENDED,
				'name'    => __( 'Search engines can index the store', 'woocommerce' ),
				'label'   => __( 'Store is hidden from search engines', 'woocommerce' ),
				'why'     => __( '"Discourage search engines" asks crawlers, including AI shopping crawlers, to skip your store.', 'woocommerce' ),
				'fix'     => __( 'Clear "Discourage search engines from indexing this site" under Settings > Reading.', 'woocommerce' ),
				'fix_url' => admin_url( 'options-reading.php' ),
			),
			array(
				'id'      => 'ucp_on',
				'ok'      => UcpUtils::is_enabled(),
				'blocks'  => 'ucp',
				'level'   => self::LEVEL_RECOMMENDED,
				'name'    => __( 'Universal Commerce Protocol (UCP) is on', 'woocommerce' ),
				'label'   => __( 'Universal Commerce Protocol (UCP) is off', 'woocommerce' ),
				'why'     => __( 'Shopping agents that use UCP cannot read your catalog.', 'woocommerce' ),
				'fix'     => __( 'Turn on "Let AI agents shop your store" under WooCommerce > Settings > Agentic commerce.', 'woocommerce' ),
				'fix_url' => $settings_url( AgenticCommerceSettings::TAB ),
			),
			array(
				'id'      => 'https',
				'ok'      => wc_site_is_https(),
				'blocks'  => 'ucp',
				'level'   => self::LEVEL_REQUIRED,
				'name'    => __( 'Site uses HTTPS', 'woocommerce' ),
				'label'   => __( 'Site does not use HTTPS', 'woocommerce' ),
				'why'     => __( 'Agents only send shoppers to a secure checkout.', 'woocommerce' ),
				'fix'     => __( 'Move the site to HTTPS; your host can usually turn it on.', 'woocommerce' ),
				'fix_url' => wp_get_update_https_url(),
			),
			array(
				'id'      => 'pretty_permalinks',
				'ok'      => $wp_rewrite->using_permalinks(),
				'blocks'  => 'ucp',
				'level'   => self::LEVEL_REQUIRED,
				'name'    => __( 'Permalinks are not set to Plain', 'woocommerce' ),
				'label'   => __( 'Permalinks are set to Plain', 'woocommerce' ),
				'why'     => __( 'Your store does not serve the UCP discovery file at /.well-known/ucp, so agents cannot find your catalog and checkout.', 'woocommerce' ),
				'fix'     => __( 'Choose any permalink structure other than Plain under Settings > Permalinks.', 'woocommerce' ),
				'fix_url' => admin_url( 'options-permalink.php' ),
			),
			$policy_page(
				'privacy_page',
				AgenticCommerceSettings::PRIVACY_PAGE_OPTION,
				__( 'Privacy policy page is selected', 'woocommerce' ),
				__( 'No privacy policy page', 'woocommerce' ),
				__( 'Agents link shoppers to your privacy policy before they buy.', 'woocommerce' )
			),
			$policy_page(
				'terms_page',
				AgenticCommerceSettings::TERMS_PAGE_OPTION,
				__( 'Terms and conditions page is selected', 'woocommerce' ),
				__( 'No terms and conditions page', 'woocommerce' ),
				__( 'Agents link shoppers to your terms before they buy.', 'woocommerce' )
			),
			$policy_page(
				'refund_returns_page',
				AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION,
				__( 'Refund and returns page is selected', 'woocommerce' ),
				__( 'No refund and returns page', 'woocommerce' ),
				__( 'Agents link shoppers to your return terms before they buy.', 'woocommerce' )
			),
			$policy_page(
				'shipping_policy_page',
				AgenticCommerceSettings::SHIPPING_POLICY_PAGE_OPTION,
				__( 'Shipping policy page is selected', 'woocommerce' ),
				__( 'No shipping policy page', 'woocommerce' ),
				__( 'Shoppers ask agents about delivery times and costs.', 'woocommerce' )
			),
			array(
				'id'      => 'guest_checkout',
				'ok'      => ! $this->registration_required(),
				'blocks'  => 'checkout',
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
				'level'   => self::LEVEL_RECOMMENDED,
				'name'    => __( 'Order confirmation emails are on', 'woocommerce' ),
				'label'   => __( 'Order confirmation emails are off', 'woocommerce' ),
				'why'     => __( 'Shoppers who buy through an agent get no confirmation from your store.', 'woocommerce' ),
				'fix'     => __( 'Turn on the Processing order email under WooCommerce > Settings > Emails.', 'woocommerce' ),
				'fix_url' => $settings_url( 'email&section=wc_email_customer_processing_order' ),
			),
		);
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
	 * Whether a page id points at a published page.
	 *
	 * @param int $page_id Page id.
	 */
	public static function is_published_page( int $page_id ): bool {
		return $page_id > 0 && 'page' === get_post_type( $page_id ) && 'publish' === get_post_status( $page_id );
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
	 * Whether the order confirmation email shoppers get after checkout is on.
	 */
	private function order_confirmation_email_enabled(): bool {
		$emails = WC()->mailer()->get_emails();
		$email  = $emails['WC_Email_Customer_Processing_Order'] ?? null;
		return $email instanceof \WC_Email && $email->is_enabled();
	}
}
