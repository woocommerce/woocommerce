<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonHelper;
use Automattic\WooCommerce\StoreApi\Utilities\UcpUtils;

defined( 'ABSPATH' ) || exit;

/**
 * Store settings that stop agents finding or buying the store's products,
 * whatever the product data. `ok` says whether the store is clear of the
 * problem the label names. `blocks` says what a failing condition stops:
 * `listing` hides every product on every channel, `ucp` hides the catalog
 * from agents that use UCP, `checkout` stops agents placing orders.
 *
 * @since 11.4.0
 */
class StoreConditions {

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
	 * @return array<int, array{id:string,ok:bool,blocks:string,label:string,why:string,fix:string}>
	 */
	public function all(): array {
		global $wp_rewrite;

		return array(
			array(
				'id'     => 'launched',
				'ok'     => $this->coming_soon_helper->is_site_live(),
				'blocks' => 'listing',
				'label'  => __( 'Store is in coming soon mode', 'woocommerce' ),
				'why'    => __( 'Visitors, crawlers and shopping agents see a holding page instead of your products.', 'woocommerce' ),
				'fix'    => __( 'Launch the store under WooCommerce > Settings > Site visibility.', 'woocommerce' ),
			),
			array(
				'id'     => 'search_visible',
				'ok'     => '0' !== (string) get_option( 'blog_public', '1' ),
				'blocks' => 'listing',
				'label'  => __( 'Store is hidden from search engines', 'woocommerce' ),
				'why'    => __( '"Discourage search engines" asks crawlers, including AI shopping crawlers, to skip your store.', 'woocommerce' ),
				'fix'    => __( 'Clear "Discourage search engines from indexing this site" under Settings > Reading.', 'woocommerce' ),
			),
			array(
				'id'     => 'ucp_on',
				'ok'     => UcpUtils::is_enabled(),
				'blocks' => 'ucp',
				'label'  => __( 'Universal Commerce Protocol (UCP) is off', 'woocommerce' ),
				'why'    => __( 'Shopping agents that use UCP cannot read your catalog.', 'woocommerce' ),
				'fix'    => __( 'Turn on "Let AI agents shop your store" under WooCommerce > Settings > Agentic commerce.', 'woocommerce' ),
			),
			array(
				'id'     => 'pretty_permalinks',
				'ok'     => $wp_rewrite->using_permalinks(),
				'blocks' => 'ucp',
				'label'  => __( 'Permalinks are set to Plain', 'woocommerce' ),
				'why'    => __( 'Your store does not serve the UCP discovery file at /.well-known/ucp, so agents cannot find your catalog and checkout.', 'woocommerce' ),
				'fix'    => __( 'Choose any permalink structure other than Plain under Settings > Permalinks.', 'woocommerce' ),
			),
			array(
				'id'     => 'guest_checkout',
				'ok'     => ! WC()->checkout()->is_registration_required(),
				'blocks' => 'checkout',
				'label'  => __( 'Guest checkout is off', 'woocommerce' ),
				'why'    => __( 'Shopping agents check out for shoppers without an account on your store, so they cannot place orders.', 'woocommerce' ),
				'fix'    => __( 'Tick "Enable guest checkout" under WooCommerce > Settings > Accounts & Privacy.', 'woocommerce' ),
			),
		);
	}
}
