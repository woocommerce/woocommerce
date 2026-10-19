/**
 * External dependencies
 */
import type { Field } from '@wordpress/dataviews';
import { __ } from '@wordpress/i18n';

export const ENTITY_KIND = 'woo_settings_payments';

export type SettingsRecord = Record< string, unknown >;

/**
 * A core-data entity config for a settings page. The page's URL segment is the entity name and
 * its record ID, and the stage reads `label` and `fields` back through `getEntityConfig()`.
 */
export interface SettingsEntityConfig {
	kind: string;
	name: string;
	baseURL: string;
	key: string;
	label: string;
	getTitle: () => string;
	fields: Field< SettingsRecord >[];
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
			fields: [
				{
					id: 'enabled',
					type: 'boolean',
					Edit: 'toggle',
					label: __( 'Enable check payments', 'woocommerce' ),
				},
				{
					id: 'title',
					type: 'text',
					label: __( 'Title', 'woocommerce' ),
					description: __(
						'Payment method name that the customer will see during checkout.',
						'woocommerce'
					),
				},
				{
					id: 'description',
					type: 'text',
					Edit: 'textarea',
					label: __( 'Description', 'woocommerce' ),
					description: __(
						'Payment method description that the customer will see during checkout.',
						'woocommerce'
					),
				},
			],
		},
	];
}
