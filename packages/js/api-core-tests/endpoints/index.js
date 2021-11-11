const { ordersApi } = require( './orders' );
const { couponsApi } = require( './coupons' );
const { productsApi } = require( './products' );
const { refundsApi } = require( './refunds' );
const { shippingZonesApi } = require( './shipping-zones' );
const { shippingMethodsApi } = require( './shipping-methods' );

module.exports = {
	ordersApi,
	couponsApi,
	productsApi,
	refundsApi,
	shippingZonesApi,
	shippingMethodsApi,
};
