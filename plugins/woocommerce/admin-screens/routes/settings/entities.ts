/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const ENTITY_KIND = 'woo_settings_payments';

/**
 * A core-data entity config for a settings page. The page's URL segment is the entity name and
 * its record ID, and its fields are registered separately in `entity-fields/`.
 */
export interface SettingsEntityConfig {
	kind: string;
	name: string;
	baseURL: string;
	key: string;
	label: string;
	getTitle: () => string;
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
		},
	];
}
