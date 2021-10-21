const { order, getOrderExample } = require('./order');
const { coupon } = require('./coupon');
const { refund } = require('./refund');
const shared = require('./shared');

module.exports = {
	order,
	getOrderExample,
	coupon,
	shared,
	refund,
};
