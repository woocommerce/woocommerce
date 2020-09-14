/**
 * pluckAddress takes a full address object and returns relevant fields for calculating
 * shipping, so we can track when one of them change to update rates.
 *
 * @param {Object} address          An object containing all address information
 *
 * @return {Object} pluckedAddress  An object containing shipping address that are needed to fetch an address.
 */
export const pluckAddress = ( { country, state, city, postcode } ) => ( {
	country,
	state,
	city,
	postcode: postcode.replace( ' ', '' ).toUpperCase(),
} );
