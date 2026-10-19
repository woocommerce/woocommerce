/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';

export const ENTITY_KIND = 'woo_settings_payments';
export const OFFLINE_GATEWAYS = [
	{ id: 'bacs', title: __( 'Direct bank transfer', 'woocommerce' ) },
	{ id: 'cheque', title: __( 'Check payments', 'woocommerce' ) },
	{ id: 'cod', title: __( 'Cash on delivery', 'woocommerce' ) },
] as const;

export type OfflineGateway = ( typeof OFFLINE_GATEWAYS )[ number ];
export type GatewayId = OfflineGateway[ 'id' ];
export type ShippingOptions = Record<
	string,
	string | Record< string, string >
>;
export type BankAccount = Record<
	| 'account_name'
	| 'account_number'
	| 'bank_name'
	| 'sort_code'
	| 'iban'
	| 'bic',
	string
> & { country_code?: string };
export const SUPPORT_ENTITY_KIND = 'woo_payment_settings_support';
export const SUPPORT_ENTITIES = [
	{
		kind: SUPPORT_ENTITY_KIND,
		name: 'country',
		baseURL: '/wc/v3/data/countries',
		key: 'code',
	},
	{
		kind: SUPPORT_ENTITY_KIND,
		name: 'general-setting',
		baseURL: '/wc/v3/settings/general',
		key: 'id',
	},
];
export interface OfflineSettings extends Record< string, unknown > {
	id: GatewayId;
	enabled: boolean;
	title: string;
	description: string;
	values: Record< string, string | boolean | string[] | BankAccount[] > & {
		instructions?: string;
		enable_for_methods?: string[];
		enable_for_virtual?: string;
		account_details?: BankAccount[];
	};
	groups: Record<
		string,
		{ fields: { id: string; options?: ShippingOptions }[] }
	>;
	countries?: Record< string, string >;
	default_country?: string;
}

/**
 * Find a Core offline gateway without treating arbitrary entities as settings pages.
 */
export function getOfflineGateway( page?: string ) {
	return OFFLINE_GATEWAYS.find( ( gateway ) => gateway.id === page );
}

/**
 * Describe the Core settings entities using the existing v4 gateway endpoint.
 */
export function getOfflineEntities() {
	return OFFLINE_GATEWAYS.map( ( gateway ) => ( {
		kind: ENTITY_KIND,
		name: gateway.id,
		baseURL: '/wc/v4/settings/payment-gateways',
		key: 'id',
		mergedEdits: { values: true },
		label: gateway.title,
		getTitle: () => gateway.title,
	} ) );
}

/**
 * Use the store country for legacy accounts without their own country.
 */
export function getAccountCountry(
	account: BankAccount,
	defaultCountry = ''
): string {
	const country = account.country_code ?? '';
	return country.length > 0 ? country : defaultCountry;
}

/**
 * Convert the gateway API's grouped shipping choices into DataForm elements.
 */
export function getShippingElements( options: ShippingOptions ) {
	return Object.entries( options ).flatMap( ( [ value, label ] ) =>
		typeof label === 'string'
			? [ { value, label: decodeEntities( label ) } ]
			: Object.entries( label ).map( ( [ id, name ] ) => ( {
					value: id,
					label: decodeEntities( name ),
				} ) )
	);
}
