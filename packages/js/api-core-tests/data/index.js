const { order, getOrderExample } = require( './order' );
const { coupon } = require( './coupon' );
const { refund } = require( './refund' );
const { getTaxRateExamples } = require( './tax-rate' );
const { getVariationExample } = require( './variation' );
const {
	simpleProduct,
	variableProduct,
	variation,
	virtualProduct,
	groupedProduct,
	externalProduct,
} = require( './products-crud' );
const { getShippingZoneExample } = require( './shipping-zone' );
const { getShippingMethodExample } = require( './shipping-method' );
const shared = require( './shared' );

module.exports = {
	order,
	getOrderExample,
	coupon,
	shared,
	refund,
	getTaxRateExamples,
	getVariationExample,
	simpleProduct,
	variableProduct,
	variation,
	virtualProduct,
	groupedProduct,
	externalProduct,
	getShippingZoneExample,
	getShippingMethodExample,
};
