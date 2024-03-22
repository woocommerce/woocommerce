/**
 * External dependencies
 */
import {
	ALLOWED_COUNTRIES,
	ALLOWED_STATES,
	LOGIN_URL,
} from '@woocommerce/block-settings';
import { getSetting } from '@woocommerce/settings';
import { isObject, isString } from '@woocommerce/types';
import { decodeEntities } from '@wordpress/html-entities';

export const LOGIN_TO_CHECKOUT_URL = `${ LOGIN_URL }?redirect_to=${ encodeURIComponent(
	window.location.href
) }`;

export const isLoginRequired = ( customerId: number ): boolean => {
	return ! customerId && ! getSetting( 'checkoutAllowsGuest', false );
};

export const getFormattedState = (
	address: CartBillingAddress | CartShippingAddress
): string => {
	return isObject( ALLOWED_STATES[ address.country ] ) &&
		isString( ALLOWED_STATES[ address.country ][ address.state ] )
		? decodeEntities( ALLOWED_STATES[ address.country ][ address.state ] )
		: address.state;
};

export const getFormattedCountry = (
	address: CartBillingAddress | CartShippingAddress
): string => {
	return isString( ALLOWED_COUNTRIES[ address.country ] )
		? decodeEntities( ALLOWED_COUNTRIES[ address.country ] )
		: address.country;
};

/**
 * Extract the name format from the parent address format.
 */
export const extractName = ( format: string ): string => {
	// Name can be a few different formats:
	const nameTokens = [
		'{name}',
		'{name_upper}',
		'{first_name} {last_name}',
		'{last_name} {first_name}',
		'{first_name_upper} {last_name_upper}',
		'{last_name_upper} {first_name_upper}',
		'{first_name} {last_name_upper}',
		'{first_name_upper} {last_name}',
		'{last_name} {first_name_upper}',
		'{last_name_upper} {first_name}',
	];
	return nameTokens.find( ( token ) => format.indexOf( token ) >= 0 ) || '';
};


export const reloadPage = (): void => void window.location.reload( true );
