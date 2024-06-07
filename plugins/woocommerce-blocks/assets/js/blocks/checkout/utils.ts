/**
 * External dependencies
 */
import {
	ALLOWED_COUNTRIES,
	ALLOWED_STATES,
	LOGIN_URL,
} from '@woocommerce/block-settings';
import { getSetting } from '@woocommerce/settings';
import {
	CartBillingAddress,
	CartShippingAddress,
} from '@woocommerce/type-defs/cart';
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

/**
 * Format an address for display in the address card.
 */
export const formatAddress = (
	address: CartBillingAddress | CartShippingAddress,
	format: string
): { name: string; address: string[] } => {
	const nameFormat = extractName( format );

	const addressFormatWithoutName = format.replace( `${ nameFormat }\n`, '' );

	const addressTokens = [
		[ '{company}', address?.company || '' ],
		[ '{address_1}', address?.address_1 || '' ],
		[ '{address_2}', address?.address_2 || '' ],
		[ '{city}', address?.city || '' ],
		[ '{state}', getFormattedState( address ) ],
		[ '{postcode}', address?.postcode || '' ],
		[ '{country}', getFormattedCountry( address ) ],
		[ '{company_upper}', ( address?.company || '' ).toUpperCase() ],
		[ '{address_1_upper}', ( address?.address_1 || '' ).toUpperCase() ],
		[ '{address_2_upper}', ( address?.address_2 || '' ).toUpperCase() ],
		[ '{city_upper}', ( address?.city || '' ).toUpperCase() ],
		[ '{state_upper}', getFormattedState( address ).toUpperCase() ],
		[ '{state_code}', address?.state || '' ],
		[ '{postcode_upper}', ( address?.postcode || '' ).toUpperCase() ],
		[ '{country_upper}', getFormattedCountry( address ).toUpperCase() ],
	];
	const nameTokens = [
		[
			'{name}',
			address?.first_name +
				// Only include the space if the first name was present.
				( address?.first_name && address?.last_name ? ' ' : '' ) +
				address?.last_name,
		],
		[
			'{name_upper}',
			(
				address?.first_name +
				// Only include the space if the first name was present.
				( address?.first_name && address?.last_name ? ' ' : '' ) +
				address?.last_name
			).toUpperCase(),
		],
		[ '{first_name}', address?.first_name || '' ],
		[ '{last_name}', address?.last_name || '' ],
		[ '{first_name_upper}', ( address?.first_name || '' ).toUpperCase() ],
		[ '{last_name_upper}', ( address?.last_name || '' ).toUpperCase() ],
	];
	let parsedName = nameFormat;

	nameTokens.forEach( ( [ token, value ] ) => {
		parsedName = parsedName.replace( token, value );
	} );

	let parsedAddress = addressFormatWithoutName;
	addressTokens.forEach( ( [ token, value ] ) => {
		parsedAddress = parsedAddress.replace( token, value );
	} );
	const addressParts = parsedAddress
		.replace( /^,\s|,\s$/g, '' )
		.replace( /\n{2,}/, '\n' )
		.split( '\n' )
		.filter( Boolean );

	return { name: parsedName, address: addressParts };
};

export const reloadPage = (): void => void window.location.reload( true );
