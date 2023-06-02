/**
 * External dependencies
 */
import isShallowEqual from '@wordpress/is-shallow-equal';
import { pluckAddress } from '@woocommerce/base-utils';

/**
 * Does a shallow compare of important address data to determine if the cart needs updating.
 *
 * @param {Object} previousAddress An object containing all previous address information
 * @param {Object} address An object containing all address information
 *
 * @return {boolean} True if the store needs updating due to changed data.
 */
export const shouldUpdateAddressStore = ( previousAddress, address ) => {
	if ( ! address.country ) {
		return false;
	}
	return ! isShallowEqual(
		pluckAddress( previousAddress ),
		pluckAddress( address )
	);
};
