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
}

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
	label: string
): SettingsEntityConfig {
	return {
		kind: ENTITY_KIND,
		name,
		baseURL: '/wc/v3/payment_gateways',
		key: 'id',
		label,
		getTitle: () => label,
		__unstablePrePersist: getPlainSettings,
	};
}

/**
 * Describe Core's example page, which uses the existing v3 payment gateways endpoint.
 */
export function getCoreEntities(): SettingsEntityConfig[] {
	return [
		getPaymentGatewayEntity(
			'cheque',
			__( 'Check payments', 'woocommerce' )
		),
	];
}
