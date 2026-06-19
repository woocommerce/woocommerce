/**
 * Internal dependencies
 */
import type { WooPaymentsMoneyMovementDataView } from './types';

type WooPaymentsMoneyMovementViewPreferences = Pick<
	WooPaymentsMoneyMovementDataView,
	'fields' | 'layout' | 'showTitle' | 'titleField'
>;

const STORAGE_PREFIX = 'woocommerce_woopayments_money_movement_view_';

const hasStorage = () =>
	typeof window !== 'undefined' && typeof window.localStorage !== 'undefined';

const getStorageKey = ( viewId: string ) => `${ STORAGE_PREFIX }${ viewId }`;

const isRecord = ( value: unknown ): value is Record< string, unknown > =>
	typeof value === 'object' && value !== null && ! Array.isArray( value );

const getStringArray = ( value: unknown ) =>
	Array.isArray( value )
		? value.filter( ( item ): item is string => typeof item === 'string' )
		: undefined;

const sanitizePreferences = (
	value: unknown
): WooPaymentsMoneyMovementViewPreferences => {
	if ( ! isRecord( value ) ) {
		return {};
	}

	const preferences: WooPaymentsMoneyMovementViewPreferences = {};
	const fields = getStringArray( value.fields );

	if ( fields && fields.length > 0 ) {
		preferences.fields = fields;
	}

	if ( isRecord( value.layout ) ) {
		preferences.layout = value.layout;
	}

	if ( typeof value.showTitle === 'boolean' ) {
		preferences.showTitle = value.showTitle;
	}

	if ( typeof value.titleField === 'string' && value.titleField ) {
		preferences.titleField = value.titleField;
	}

	return preferences;
};

export const getMoneyMovementViewPreferences = (
	viewId: string
): WooPaymentsMoneyMovementViewPreferences => {
	if ( ! hasStorage() ) {
		return {};
	}

	try {
		const storedValue = window.localStorage.getItem(
			getStorageKey( viewId )
		);

		return storedValue
			? sanitizePreferences( JSON.parse( storedValue ) )
			: {};
	} catch ( error ) {
		return {};
	}
};

export const setMoneyMovementViewPreferences = (
	viewId: string,
	view: WooPaymentsMoneyMovementDataView
): WooPaymentsMoneyMovementViewPreferences => {
	const preferences = sanitizePreferences( view );

	if ( hasStorage() ) {
		window.localStorage.setItem(
			getStorageKey( viewId ),
			JSON.stringify( preferences )
		);
	}

	return preferences;
};

export const mergeMoneyMovementViewPreferences = (
	view: WooPaymentsMoneyMovementDataView,
	preferences: WooPaymentsMoneyMovementViewPreferences
): WooPaymentsMoneyMovementDataView => ( {
	...view,
	...preferences,
	fields: preferences.fields || view.fields,
	layout: preferences.layout || view.layout,
} );
