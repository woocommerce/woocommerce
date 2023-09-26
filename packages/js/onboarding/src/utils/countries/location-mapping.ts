/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Location } from '.';

/**
 * This file is used to map a location to a WC region label
 * so that we can find the correct country option
 * since WPCOM geolocation returns a different
 * region or city name.
 */

// Key is the country code, value is an object with keys as region/city names and values as WC region labels.
const MAPPING: Record< string, Record< string, string > > = {
	PH: {
		'National Capital Region': __( 'Metro Manila', 'woocommerce' ),
	},
	IT: {
		Rome: __( 'Roma', 'woocommerce' ),
	},
};

/**
 * Returns a WC mapping region name for the given country, region and city.
 */
export const getMappingRegion = ( {
	country_short: countryCode,
	region = '',
	city = '',
}: Location ) => {
	if ( ! countryCode ) {
		return null;
	}

	const countryMapping = MAPPING[ countryCode ];
	if ( ! countryMapping ) {
		return null;
	}

	const regionMapping = countryMapping[ region ];
	if ( regionMapping ) {
		return regionMapping;
	}

	const cityMapping = countryMapping[ city ];
	if ( cityMapping ) {
		return cityMapping;
	}

	return null;
};

export default MAPPING;
