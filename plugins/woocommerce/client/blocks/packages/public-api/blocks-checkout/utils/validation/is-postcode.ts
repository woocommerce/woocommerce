/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import type { CountryData } from '@woocommerce/types';

export interface IsPostcodeProps {
	postcode: string;
	country: string;
}

const isPostcode = ( { postcode, country }: IsPostcodeProps ): boolean => {
	if ( typeof postcode !== 'string' || typeof country !== 'string' ) {
		return false;
	}

	if ( /[^ \t\n\r\f\vA-Za-z0-9-]/.test( postcode ) ) {
		return false;
	}

	const countryData = getSetting< Record< string, Partial< CountryData > > >(
		'countryData',
		{}
	);
	const rule = countryData[ country ]?.postcode;
	if ( ! rule ) {
		return true;
	}

	try {
		return new RegExp( `^(?:${ rule })$`, 'i' ).test(
			postcode.replace( /[\s-]/g, '' )
		);
	} catch {
		// The server ignores a rule it cannot compile.
		return true;
	}
};

export default isPostcode;
