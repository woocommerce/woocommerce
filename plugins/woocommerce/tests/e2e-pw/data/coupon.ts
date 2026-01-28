/**
 * A basic coupon.
 *
 * For more details on a coupon's properties, see:
 *
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#coupons
 *
 */

export interface Coupon {
	code: string;
	amount: string;
	discount_type: string;
	description: string;
	date_expires: string;
	date_expires_gmt: string;
	individual_use: boolean;
	product_ids: number[];
	excluded_product_ids: number[];
	usage_limit: number;
	usage_limit_per_user: number;
	limit_usage_to_x_items: number;
	free_shipping: boolean;
	product_categories: number[];
	excluded_product_categories: number[];
	exclude_sale_items: boolean;
	minimum_amount: string;
	maximum_amount: string;
	email_restrictions: string[];
}

export const coupon: Coupon = {
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
