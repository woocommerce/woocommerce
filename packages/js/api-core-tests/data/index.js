const { order, getOrderExample } = require( './order' );
const { coupon } = require( './coupon' );
const { refund } = require( './refund' );
const { getExampleTaxRate } = require( './tax-rate' );
const { getExampleVariation } = require( './variation' );
const shared = require( './shared' );

module.exports = {
	order,
	getOrderExample,
	coupon,
	shared,
	refund,
	getExampleTaxRate,
	getExampleVariation,
};
