const { ordersApi } = require( './orders' );
const { couponsApi } = require( './coupons' );
const { productsApi } = require( './products' );
const { refundsApi } = require( './refunds' );
const { shippingZonesApi } = require( './shipping-zones' );

module.exports = {
	ordersApi,
	couponsApi,
	productsApi,
	refundsApi,
	shippingZonesApi,
};
