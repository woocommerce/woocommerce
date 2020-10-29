/**
 * Internal dependencies
 */
const {
	customer,
	merchant,
	shopper
} = require( './flows' );

const CustomerFlow = {
	...shopper,
	...customer,
};

export {
	CustomerFlow,
	merchant as StoreOwnerFlow,
};
