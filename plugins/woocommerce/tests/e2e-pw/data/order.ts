import { customerBilling, customerShipping } from './shared';
import type { CustomerBilling, CustomerShipping } from './shared';
import {
	customerBillingSearchTest,
	customerShippingSearchTest,
} from './shared/customer';

/**
 * A basic order.
 *
 * For more details on the order properties, see:
 *
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#order-properties
 */

export interface ProductLineItems {
	name: string;
	product_id: number;
	variation_id: number;
	quantity: number;
	tax_class: string;
	subtotal: string;
	total: string;
}

export interface ShippingLines {
	method_title: string;
	method_id: string;
	total: string;
}

export interface FeeLines {
	name: string;
	tax_class: string;
	tax_status: string;
	total: string;
}

export interface CouponLines {
	code: string;
}

export interface Order {
	payment_method: string;
	payment_method_title: string;
	status: string;
	set_paid: boolean;
	currency: string;
	customer_note: string;
	customer_id: number;
	billing: CustomerBilling;
	shipping: CustomerShipping;
	line_items: ProductLineItems[];
	shipping_lines: ShippingLines[];
	fee_lines: FeeLines[];
	coupon_lines: CouponLines[];
}

export interface OrderExample extends Order {
	id: number;
}

export const order: Order = {
	payment_method: '',
	payment_method_title: '',
	status: 'pending',
	set_paid: false,
	currency: 'USD',
	customer_note: '',
	customer_id: 0,
	billing: customerBilling,
	shipping: customerShipping,
	line_items: [],
	shipping_lines: [],
	fee_lines: [],
	coupon_lines: [],
};

export const productLineItems: ProductLineItems = {
	name: '',
	product_id: 93,
	variation_id: 0,
	quantity: 2,
	tax_class: '',
	subtotal: '',
	total: '',
};

export const shippingLines: ShippingLines = {
	method_title: 'Flat rate',
	method_id: 'flat_rate',
	total: '10.00',
};

export const feeLines: FeeLines = {
	name: 'Fee',
	tax_class: '',
	tax_status: 'none',
	total: '',
};

export const couponLines: CouponLines = {
	code: '10off',
};

/**
 * Builds an example order request.
 *
 * @return Sample Order payload.
 */
export const getOrderExample = (): OrderExample => {
	const orderExample: OrderExample = {
		id: 0,
		payment_method: 'cod',
		payment_method_title: 'Cash on Delivery',
		status: 'processing',
		set_paid: false,
		currency: 'USD',
		customer_note: 'A customer provided note.',
		customer_id: 0,
		billing: customerBilling,
		shipping: customerShipping,
		line_items: [ productLineItems ],
		shipping_lines: [ shippingLines ],
		fee_lines: [ feeLines ],
		coupon_lines: [ couponLines ],
	};
	return orderExample;
};

export const getOrderExampleSearchTest = (): OrderExample => {
	const orderExampleSearchTest: OrderExample = {
		id: 0,
		payment_method: 'cod',
		payment_method_title: 'Cash on Delivery',
		status: 'processing',
		set_paid: false,
		currency: 'USD',
		customer_note: 'A customer provided note.',
		customer_id: 0,
		billing: customerBillingSearchTest,
		shipping: customerShippingSearchTest,
		line_items: [ productLineItems ],
		shipping_lines: [ shippingLines ],
		fee_lines: [ feeLines ],
		coupon_lines: [ couponLines ],
	};
	return orderExampleSearchTest;
};
