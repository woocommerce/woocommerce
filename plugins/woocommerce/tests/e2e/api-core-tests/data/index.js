const { order, getOrderExample, createOrderInDb, deleteOrderFromDb } = require('./order');
const { coupon } = require('./coupon');
const { refund } = require('./refund');
const shared = require('./shared');

module.exports = {
	order,
	getOrderExample,
	createOrderInDb,
	deleteOrderFromDb,
	coupon,
	shared,
	refund,
};
