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
