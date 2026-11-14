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
 * A settings area, such as payments. Its URL segment comes before the page's, it holds the
 * entities of one kind, and its label and wp-admin URL make the first breadcrumb.
 */
export interface SettingsSection {
	slug: string;
	kind: string;
	label: string;
	url: string;
}

/**
 * Describe the settings areas the screen shows.
 */
export function getSections(): SettingsSection[] {
	return [
		{
			slug: 'payments',
			kind: ENTITY_KIND,
			label: __( 'Payments', 'woocommerce' ),
			url: 'admin.php?page=wc-settings&tab=checkout',
		},
	];
}

/**
 * Find a settings area by its URL segment.
 */
export function getSection( slug?: string ): SettingsSection | undefined {
	return getSections().find( ( section ) => section.slug === slug );
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
