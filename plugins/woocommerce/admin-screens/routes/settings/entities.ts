/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

const ENTITY_KIND_PREFIX = 'woo_settings_';

/**
 * The entity kind for a settings area's URL segment, such as `woo_settings_payments` for `payments`.
 */
export function getEntityKind( section: string ): string {
	return ENTITY_KIND_PREFIX + section;
}

export const ENTITY_KIND = getEntityKind( 'payments' );

/**
 * A core-data entity config for a settings page, at `/settings/$section/$page`. The kind holds the
 * area's segment and the name is the page's segment and record ID. `meta.section` is the area's label
 * and wp-admin URL for the first breadcrumb. Fields are registered on the server from `packages/entity-fields/`.
 */
export interface SettingsEntityConfig {
	kind: string;
	name: string;
	baseURL: string;
	key: string;
	label: string;
	getTitle: () => string;
	meta?: { section?: { label: string; url: string } };
}

/**
 * Describe Core's example page, which uses the existing v3 payment gateways endpoint.
 */
export function getCoreEntities(): SettingsEntityConfig[] {
	return [
		{
			kind: ENTITY_KIND,
			name: 'cheque',
			baseURL: '/wc/v3/payment_gateways',
			key: 'id',
			label: __( 'Check payments', 'woocommerce' ),
			getTitle: () => __( 'Check payments', 'woocommerce' ),
			meta: {
				section: {
					label: __( 'Payments', 'woocommerce' ),
					url: 'admin.php?page=wc-settings&tab=checkout',
				},
			},
		},
	];
}
