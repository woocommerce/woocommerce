import { __ } from '@wordpress/i18n';
import type { Entity } from './types';

/**
 * Entity definition for WooCommerce orders, registered against
 * `@wordpress/core-data` so any consumer can `getEntityRecords(
 * WC_ORDER_ENTITY.kind, WC_ORDER_ENTITY.name, query )`.
 *
 * Points at `/wc/v3/orders` — the stable REST endpoint that abstracts over
 * HPOS vs. legacy post-meta storage. We don't use `kind: 'postType'` with
 * `'shop_order'` because the `shop_order` CPT is not registered with
 * `show_in_rest: true` in WooCommerce core today.
 */
export const WC_ORDER_ENTITY: Entity = {
	name: 'order',
	kind: 'woocommerce',
	baseURL: '/wc/v3/orders',
	key: 'id',
	label: __( 'Order', 'woocommerce' ),
	plural: __( 'Orders', 'woocommerce' ),
	supportsPagination: true,
};

/**
 * Entity definition for WooCommerce product reviews.
 *
 * Points at `/wc/v3/products/reviews` — the WC-namespaced endpoint that
 * guarantees the results are product reviews (i.e. comments attached to a
 * `product` post). The alternative (`/wp/v2/comments?type=review`) only
 * filters by the `comment_type` convention, which is not enforced to belong
 * to WooCommerce, so consumers reading "store activity" must use this one.
 */
export const WC_PRODUCT_REVIEW_ENTITY: Entity = {
	name: 'review',
	kind: 'woocommerce',
	baseURL: '/wc/v3/products/reviews',
	key: 'id',
	label: __( 'Product review', 'woocommerce' ),
	plural: __( 'Product reviews', 'woocommerce' ),
	supportsPagination: true,
};
