const { ordersApi } = require( './orders' );
const { couponsApi } = require( './coupons' );
const { productsApi } = require( './products' );
const { refundsApi } = require( './refunds' );
const { taxRatesApi } = require( './tax-rates' );
const { variationsApi } = require( './variations' );

module.exports = {
	ordersApi,
	couponsApi,
	productsApi,
	refundsApi,
	taxRatesApi,
	variationsApi,
};
