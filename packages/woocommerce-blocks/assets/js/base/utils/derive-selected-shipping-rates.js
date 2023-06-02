/**
 * Get an array of selected shipping rates keyed by Package ID.
 *
 * @param {Array} shippingRates Array of shipping rates.
 * @return {Object} Object containing the package IDs and selected rates in the format: { [packageId:string]: rateId:string }
 */
export const deriveSelectedShippingRates = ( shippingRates ) =>
	shippingRates
		.map( ( { package_id: packageId, shipping_rates: packageRates } ) => [
			packageId,
			packageRates.find( ( rate ) => rate.selected )?.rate_id,
		] )
		// A fromEntries polyfill, creates an object from an array of arrays.
		.reduce( ( obj, [ key, val ] ) => {
			if ( val ) {
				obj[ key ] = val;
			}
			return obj;
		}, {} );
