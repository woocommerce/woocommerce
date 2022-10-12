const {
	order,
	getOrderExample,
	getOrderExampleSearchTest,
} = require( './order' );
const { coupon } = require( './coupon' );
const { customer } = require( './customer' );
const { refund } = require( './refund' );
const { getTaxRateExamples, allUSTaxesExample } = require( './tax-rate' );
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
	customer,
	order,
	getOrderExample,
	getOrderExampleSearchTest,
	coupon,
	shared,
	refund,
	allUSTaxesExample,
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
