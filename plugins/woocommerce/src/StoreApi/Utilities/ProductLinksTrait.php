<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Utilities;

/**
 * ProductLinksTrait
 *
 * Shared functionality for preparing product links including embeddable links for upsells, cross-sells, and related products.
 */
trait ProductLinksTrait {
	/**
	 * Prepare links for the request.
	 *
	 * @param \WC_Product      $item Product object.
	 * @param \WP_REST_Request $request Request object.
	 * @return array
	 *
	 * @since 10.6.0
	 */
	protected function prepare_links( $item, $request ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$links = array(
			'self'       => array(
				'href' => rest_url( $this->get_namespace() . '/products/' . $item->get_id() ),
			),
			'collection' => array(
				'href' => rest_url( $this->get_namespace() . '/products' ),
			),
		);

		if ( $item->get_parent_id() ) {
			$links['up'] = array(
				'href' => rest_url( $this->get_namespace() . '/products/' . $item->get_parent_id() ),
			);
		}

		$upsell_ids = $item->get_upsell_ids();
		if ( ! empty( $upsell_ids ) ) {
			$links['upsells'] = array(
				'href'       => add_query_arg(
					array( 'include' => implode( ',', $upsell_ids ) ),
					rest_url( $this->get_namespace() . '/products' )
				),
				'embeddable' => true,
			);
		}

		$cross_sell_ids = $item->get_cross_sell_ids();
		if ( ! empty( $cross_sell_ids ) ) {
			$links['cross_sells'] = array(
				'href'       => add_query_arg(
					array( 'include' => implode( ',', $cross_sell_ids ) ),
					rest_url( $this->get_namespace() . '/products' )
				),
				'embeddable' => true,
			);
		}

		$links['related'] = array(
			'href'       => add_query_arg(
				array(
					'related'  => $item->get_id(),
					'per_page' => 10,
				),
				rest_url( $this->get_namespace() . '/products' )
			),
			'embeddable' => true,
		);

		return $links;
	}
}
