/**
 * A basic coupon.
 *
 * For more details on a coupon's properties, see:
 *
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#coupons
 *
 */
const coupon = {
	code: '10off',
	amount: '10',
	discount_type: 'percent',
	description: '',
	date_expires: '',
	date_expires_gmt: '',
	individual_use: true,
	product_ids: [],
	excluded_product_ids: [],
	usage_limit: 0,
	usage_limit_per_user: 0,
	limit_usage_to_x_items: 0,
	free_shipping: false,
	product_categories: [],
	excluded_product_categories: [],
	exclude_sale_items: false,
	minimum_amount: '100.00',
	maximum_amount: '',
	email_restrictions: [],
};

module.exports = {
	coupon,
};
