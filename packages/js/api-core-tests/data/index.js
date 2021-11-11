const { order, getOrderExample } = require( './order' );
const { coupon } = require( './coupon' );
const { refund } = require( './refund' );
const { getShippingZoneExample } = require( './shipping-zone' );
const shared = require( './shared' );

module.exports = {
	order,
	getOrderExample,
	coupon,
	shared,
	refund,
	getShippingZoneExample,
};
