<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Store-wide conditions that hide every product from agents, whatever the
 * product data. `ok` says whether the store is clear of the problem the label
 * names.
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
	 * @return array<int, array{id:string,ok:bool,label:string,why:string,fix:string}>
	 */
	public function all(): array {
		return array(
			array(
				'id'    => 'launched',
				'ok'    => $this->coming_soon_helper->is_site_live(),
				'label' => __( 'Store is in coming soon mode', 'woocommerce' ),
				'why'   => __( 'Visitors, crawlers and shopping agents see a holding page instead of your products.', 'woocommerce' ),
				'fix'   => __( 'Launch the store under WooCommerce > Settings > Site visibility.', 'woocommerce' ),
			),
			array(
				'id'    => 'search_visible',
				'ok'    => '0' !== (string) get_option( 'blog_public', '1' ),
				'label' => __( 'Store is hidden from search engines', 'woocommerce' ),
				'why'   => __( '"Discourage search engines" asks crawlers, including AI shopping crawlers, to skip your store.', 'woocommerce' ),
				'fix'   => __( 'Clear "Discourage search engines from indexing this site" under Settings > Reading.', 'woocommerce' ),
			),
		);
	}
}
