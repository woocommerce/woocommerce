/**
 * Get the number of packages in a shippingRates array.
 *
 * @param {Array} shippingRates Shipping rates and packages array.
 */
export const getShippingRatesPackageCount = ( shippingRates ) => {
	return shippingRates.length;
};

/**
 * Get the number of rates in a shippingRates array.
 *
 * @param {Array} shippingRates Shipping rates and packages array.
 */
export const getShippingRatesRateCount = ( shippingRates ) => {
	return shippingRates.reduce( function ( count, shippingPackage ) {
		return count + shippingPackage.shipping_rates.length;
	}, 0 );
};
