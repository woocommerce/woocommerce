/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getSettingValue } from '../../entity-fields/offline/settings';

export const ENTITY_KIND = 'woo_settings_payments';

/**
 * Records a page saves alongside its own, which aren't settings pages themselves.
 */
export const RELATED_ENTITY_KIND = 'woo_settings_payments_related';

/**
 * Another record a settings page edits and saves with its own. Fields read it under its
 * entity name, so the BACS accounts are at `item.bacs_accounts.accounts`.
 */
export interface RelatedEntity {
	kind: string;
	name: string;
	recordId: string;
}

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
	__unstablePrePersist?: (
		persistedRecord: Record< string, unknown >,
		edits: Record< string, unknown >
	) => Record< string, unknown >;
	related?: RelatedEntity[];
}

/**
 * A core-data entity config for a record that a settings page saves alongside its own.
 */
export type RelatedEntityConfig = Pick<
	SettingsEntityConfig,
	'kind' | 'name' | 'baseURL' | 'key' | 'label' | 'getTitle'
>;

/**
 * Send settings as the plain values the v3 endpoint takes.
 *
 * Fields already edit settings as plain values; this keeps a `{ value }` object that slips
 * through from being saved as the setting itself.
 */
export function getPlainSettings(
	_persistedRecord: Record< string, unknown >,
	edits: Record< string, unknown >
): Record< string, unknown > {
	const settings = edits[ 'settings' ];
	if ( typeof settings !== 'object' || settings === null ) {
		return {};
	}
	return {
		settings: Object.fromEntries(
			Object.entries( settings ).map( ( [ key, value ] ) => [
				key,
				getSettingValue( value ),
			] )
		),
	};
}

/**
 * Describe a page for a gateway on the existing v3 payment gateways endpoint.
 */
function getPaymentGatewayEntity(
	name: string,
	label: string,
	related?: RelatedEntity[]
): SettingsEntityConfig {
	return {
		kind: ENTITY_KIND,
		name,
		baseURL: '/wc/v3/payment_gateways',
		key: 'id',
		label,
		getTitle: () => label,
		__unstablePrePersist: getPlainSettings,
		...( related && { related } ),
	};
}

/**
 * Describe Core's offline payment method pages.
 */
export function getCoreEntities(): SettingsEntityConfig[] {
	return [
		getPaymentGatewayEntity(
			'bacs',
			__( 'Direct bank transfer', 'woocommerce' ),
			[
				{
					kind: RELATED_ENTITY_KIND,
					name: 'bacs_accounts',
					recordId: 'accounts',
				},
			]
		),
		getPaymentGatewayEntity(
			'cheque',
			__( 'Check payments', 'woocommerce' )
		),
		getPaymentGatewayEntity(
			'cod',
			__( 'Cash on delivery', 'woocommerce' )
		),
	];
}

/**
 * Describe the records Core's pages save alongside their own.
 */
export function getCoreRelatedEntities(): RelatedEntityConfig[] {
	return [
		{
			kind: RELATED_ENTITY_KIND,
			name: 'bacs_accounts',
			baseURL: '/wc-admin/settings/payments/bacs',
			key: 'id',
			label: __( 'Bank accounts', 'woocommerce' ),
			getTitle: () => __( 'Bank accounts', 'woocommerce' ),
		},
	];
}
