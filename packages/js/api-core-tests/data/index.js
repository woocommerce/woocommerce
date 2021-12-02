const { order, getOrderExample } = require( './order' );
const { coupon } = require( './coupon' );
const { refund } = require( './refund' );
const { getExampleTaxRate } = require( './tax-rate' );
const { getExampleVariation } = require( './variation' );
const {
	simpleProduct,
	variableProduct,
	variation,
	virtualProduct,
	groupedProduct,
	externalProduct,
} = require( './products-crud' );
const shared = require( './shared' );

module.exports = {
	order,
	getOrderExample,
	coupon,
	shared,
	refund,
	getExampleTaxRate,
	getExampleVariation,
	simpleProduct,
	variableProduct,
	variation,
	virtualProduct,
	groupedProduct,
	externalProduct,
};
