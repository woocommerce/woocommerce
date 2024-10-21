import {
	Order,
	BillingOrderAddress,
	ShippingOrderAddress,
	OrderLineItem,
	OrderShippingLine,
	OrderFeeLine,
	OrderTaxRate,
	OrderCouponLine,
	MetaData,
	OrderRefundLine,
} from '../../../../models';

import {
	createBillingAddressTransformer,
	createOrderTransformer,
	createShippingAddressTransformer,
} from '../transformer';
/*
	This Object is a JSON representation of single Order GET operation from the WooCommerce REST API.

	Developer note:
	We use JSON.stringify here to convert an Object into a String, as JavaScript passes Objects
	around by reference, and we don't want tests to modify the original `responseOrderJson` variable.
 */
const responseOrderJson = JSON.stringify( {
	id: 1218,
	parent_id: 0,
	status: 'pending',
	currency: 'USD',
	version: '6.1.0',
	prices_include_tax: false,
	date_created: '2021-12-01T05:43:38',
	date_modified: '2021-12-01T07:31:05',
	discount_total: '5.00',
	discount_tax: '0.60',
	shipping_total: '0.00',
	shipping_tax: '0.00',
	cart_tax: '6.00',
	total: '56.00',
	total_tax: '6.00',
	customer_id: 0,
	order_key: 'wc_order_GFHWVmAiamh0B',
	billing: {
		first_name: 'Billing First Name',
		last_name: 'Billing Last Name',
		company: 'Billing Company',
		address_1: 'Billing Address 1',
		address_2: 'Billing Address 2',
		city: 'Billing City',
		state: 'Billing State',
		postcode: 'Billing Postcode',
		country: 'Billing Country',
		email: 'Billing Email',
		phone: 'Billing Phone',
	},
	shipping: {
		first_name: 'Shipping First Name',
		last_name: 'Shipping Last Name',
		company: 'Shipping Company',
		address_1: 'Shipping Address 1',
		address_2: 'Shipping Address 2',
		city: 'Shipping City',
		state: 'Shipping State',
		postcode: 'Shipping Postcode',
		country: 'Shipping Country',
		phone: 'Shipping Phone',
	},
	payment_method: 'Foo Payment Method',
	payment_method_title: 'Foo Payment Method Title',
	transaction_id: 'Foo Transaction ID',
	customer_ip_address: 'Foo Customer IP Address',
	customer_user_agent: 'Foo Customer User Agent',
	created_via: 'admin',
	customer_note: 'Foo Customer Note',
	date_completed: 'Foo Date Completed',
	date_paid: 'Foo Date Paid',
	cart_hash: 'Foo Cart Hash',
	number: '1218',
	meta_data: [
		{
			id: 123,
			key: 'Foo Metadata Key 1',
			value: 'Foo Metadata Value 1',
		},
	],
	line_items: [
		{
			id: 6137,
			name: 'Belt',
			product_id: 16,
			variation_id: 0,
			quantity: 1,
			tax_class: '',
			subtotal: '55.00',
			subtotal_tax: '6.60',
			total: '50.00',
			total_tax: '6.00',
			taxes: [ { id: 1, total: '6', subtotal: '6.6' } ],
			meta_data: [],
			sku: 'woo-belt',
			price: 50,
			parent_name: null,
		},
	],
	tax_lines: [
		{
			id: 6139,
			rate_code: 'US-TAX-1',
			rate_id: 1,
			label: 'Tax',
			compound: false,
			tax_total: '6.00',
			shipping_tax_total: '0.00',
			rate_percent: 12,
			meta_data: [],
		},
	],
	shipping_lines: [
		{
			id: 123,
			method_title: 'Foo Method Title',
			method_id: 456,
			total: '5.00',
			total_taxes: '10.00',
			taxes: [ { id: 1, total: '6', subtotal: '6.6' } ],
			meta_data: [],
		},
	],
	fee_lines: [
		{
			id: 123,
			name: 'Foo Name',
			tax_class: 456,
			total: '5.00',
			total_taxes: '10.00',
			taxes: [ { id: 1, total: '6', subtotal: '6.6' } ],
			meta_data: [],
		},
	],
	coupon_lines: [
		{
			id: 6138,
			code: 'save5',
			discount: '5',
			discount_tax: '0.6',
			meta_data: [
				{
					id: 57112,
					key: 'coupon_data',
					value: {
						id: 171,
						code: 'save5',
						amount: '5',
						date_created: {
							date: '2021-05-19 04:28:31.000000',
							timezone_type: 3,
							timezone: 'Pacific/Auckland',
						},
						date_modified: {
							date: '2021-05-19 04:28:31.000000',
							timezone_type: 3,
							timezone: 'Pacific/Auckland',
						},
						date_expires: null,
						discount_type: 'fixed_cart',
						description: '',
						usage_count: 3,
						individual_use: false,
						product_ids: [],
						excluded_product_ids: [],
						usage_limit: 0,
						usage_limit_per_user: 0,
						limit_usage_to_x_items: null,
						free_shipping: false,
						product_categories: [],
						excluded_product_categories: [],
						exclude_sale_items: false,
						minimum_amount: '',
						maximum_amount: '',
						email_restrictions: [],
						virtual: false,
						meta_data: [],
					},
				},
			],
		},
	],
	refunds: [
		{
			id: 123,
			reason: 'Foo Reason',
			total: '5.00',
		},
	],
	date_created_gmt: '2021-11-30T16:43:38',
	date_modified_gmt: '2021-11-30T18:31:05',
	date_completed_gmt: null,
	date_paid_gmt: null,
	currency_symbol: '$',
	_links: {
		self: [
			{
				href: 'http://local.wordpress.test/wp-json/wc/v3/orders/1218',
			},
		],
		collection: [
			{
				href: 'http://local.wordpress.test/wp-json/wc/v3/orders',
			},
		],
	},
} );

describe( 'OrderTransformer', () => {
	it( 'should transform an order JSON', () => {
		const order = createOrderTransformer().toModel(
			Order,
			JSON.parse( responseOrderJson )
		);
		const billing = createBillingAddressTransformer().toModel(
			BillingOrderAddress,
			JSON.parse( responseOrderJson ).billing
		);
		const shipping = createShippingAddressTransformer().toModel(
			ShippingOrderAddress,
			JSON.parse( responseOrderJson ).shipping
		);

		// Order
		expect( order ).toBeInstanceOf( Order );
		expect( order.id ).toStrictEqual( 1218 );
		expect( order.parentId ).toStrictEqual( 0 );
		expect( order.status ).toStrictEqual( 'pending' );
		expect( order.currency ).toStrictEqual( 'USD' );
		expect( order.version ).toStrictEqual( '6.1.0' );
		expect( order.pricesIncludeTax ).toStrictEqual( false );
		//expect( model.dateCreated ).toStrictEqual('2021-12-01T05:43:38');
		//expect( model.dateModified ).toStrictEqual('2021-12-01T07:31:05');
		expect( order.discountTotal ).toStrictEqual( '5.00' );
		expect( order.discountTax ).toStrictEqual( '0.60' );
		expect( order.shippingTotal ).toStrictEqual( '0.00' );
		expect( order.shippingTax ).toStrictEqual( '0.00' );
		expect( order.cartTax ).toStrictEqual( '6.00' );
		expect( order.total ).toStrictEqual( '56.00' );
		expect( order.totalTax ).toStrictEqual( '6.00' );
		expect( order.customerId ).toStrictEqual( 0 );
		//expect( model.orderKey ).toStrictEqual('wc_order_GFHWVmAiamh0B');
		expect( order.billing ).toStrictEqual( billing );
		expect( order.shipping ).toStrictEqual( shipping );
		expect( order.paymentMethod ).toStrictEqual( 'Foo Payment Method' );
		//expect( order.paymentMethodTitle ).toStrictEqual('Foo Payment Method Title');
		expect( order.transactionId ).toStrictEqual( 'Foo Transaction ID' );
		//expect( order.customerIpAddress ).toStrictEqual('Foo Customer IP Address');
		//expect( order.customerUserAgent ).toStrictEqual('Foo Customer User Agent');
		//expect( order.createdVia ).toStrictEqual('admin');
		expect( order.customerNote ).toStrictEqual( 'Foo Customer Note' );
		//expect( order.dateCompleted ).toStrictEqual('Foo Date Completed');
		//expect( order.datePaid ).toStrictEqual('Foo Date Paid');
		//expect( order.cartHash ).toStrictEqual('Foo Cart Hash');
		//expect( order.orderNumber ).toStrictEqual('1218');

		// Billing
		expect( billing.firstName ).toStrictEqual( 'Billing First Name' );
		expect( billing.lastName ).toStrictEqual( 'Billing Last Name' );
		expect( billing.company ).toStrictEqual( 'Billing Company' );
		expect( billing.address1 ).toStrictEqual( 'Billing Address 1' );
		expect( billing.address2 ).toStrictEqual( 'Billing Address 2' );
		expect( billing.city ).toStrictEqual( 'Billing City' );
		expect( billing.state ).toStrictEqual( 'Billing State' );
		expect( billing.postCode ).toStrictEqual( 'Billing Postcode' );
		expect( billing.country ).toStrictEqual( 'Billing Country' );
		expect( billing.email ).toStrictEqual( 'Billing Email' );
		expect( billing.phone ).toStrictEqual( 'Billing Phone' );
		expect(
			createBillingAddressTransformer().fromModel( billing )
		).toStrictEqual( JSON.parse( responseOrderJson ).billing );

		// Shipping
		expect( shipping.firstName ).toStrictEqual( 'Shipping First Name' );
		expect( shipping.lastName ).toStrictEqual( 'Shipping Last Name' );
		expect( shipping.company ).toStrictEqual( 'Shipping Company' );
		expect( shipping.address1 ).toStrictEqual( 'Shipping Address 1' );
		expect( shipping.address2 ).toStrictEqual( 'Shipping Address 2' );
		expect( shipping.city ).toStrictEqual( 'Shipping City' );
		expect( shipping.state ).toStrictEqual( 'Shipping State' );
		expect( shipping.postCode ).toStrictEqual( 'Shipping Postcode' );
		expect( shipping.country ).toStrictEqual( 'Shipping Country' );

		/*
		 * Shipping Address does not have e-mail or phone fields according to WooCommerce API
		 * @link https://woocommerce.github.io/woocommerce-rest-api-docs/#order-shipping-properties
		 */
		//expect(shipping.email).toStrictEqual('Shipping Email');
		//expect(shipping.phone).toStrictEqual('Shipping Phone');
		expect(
			createShippingAddressTransformer().fromModel( shipping )
		).toStrictEqual( JSON.parse( responseOrderJson ).shipping );

		// Metadata
		expect( order.metaData ).toHaveLength( 1 );
		//expect(order.metaData[0]['id']).toStrictEqual(123);
		expect( order.metaData[ 0 ].key ).toStrictEqual( 'Foo Metadata Key 1' );
		expect( order.metaData[ 0 ].value ).toStrictEqual(
			'Foo Metadata Value 1'
		);

		// Line Items
		expect( order.lineItems ).toHaveLength( 1 );
		expect( order.lineItems[ 0 ] ).toBeInstanceOf( OrderLineItem );
		expect( order.lineItems[ 0 ].id ).toStrictEqual( 6137 );
		expect( order.lineItems[ 0 ].name ).toStrictEqual( 'Belt' );
		expect( order.lineItems[ 0 ].productId ).toStrictEqual( 16 );
		expect( order.lineItems[ 0 ].variationId ).toStrictEqual( 0 );
		expect( order.lineItems[ 0 ].quantity ).toStrictEqual( 1 );
		expect( order.lineItems[ 0 ].taxClass ).toStrictEqual( '' );
		expect( order.lineItems[ 0 ].subtotal ).toStrictEqual( '55.00' );
		expect( order.lineItems[ 0 ].subtotalTax ).toStrictEqual( '6.60' );
		expect( order.lineItems[ 0 ].total ).toStrictEqual( '50.00' );
		expect( order.lineItems[ 0 ].totalTax ).toStrictEqual( '6.00' );
		//expect(order.lineItems[0].taxes).toStrictEqual([ { id: 1, total: '6', subtotal: '6.6' } ]);
		expect( order.lineItems[ 0 ].metaData ).toStrictEqual( [] );
		expect( order.lineItems[ 0 ].sku ).toStrictEqual( 'woo-belt' );
		expect( order.lineItems[ 0 ].price ).toStrictEqual( 50 );
		expect( order.lineItems[ 0 ].parentName ).toStrictEqual( null );

		// Tax Lines
		expect( order.taxLines ).toHaveLength( 1 );
		expect( order.taxLines[ 0 ] ).toBeInstanceOf( OrderTaxRate );
		//expect(order.taxLines[0].id).toStrictEqual(6139);
		expect( order.taxLines[ 0 ].rateCode ).toStrictEqual( 'US-TAX-1' );
		expect( order.taxLines[ 0 ].rateId ).toStrictEqual( 1 );
		expect( order.taxLines[ 0 ].label ).toStrictEqual( 'Tax' );
		//expect(order.taxLines[0].compound).toStrictEqual(false);
		expect( order.taxLines[ 0 ].taxTotal ).toStrictEqual( '6.00' );
		expect( order.taxLines[ 0 ].shippingTaxTotal ).toStrictEqual( '0.00' );
		expect( order.taxLines[ 0 ].ratePercent ).toStrictEqual( 12 );
		//expect(order.taxLines[0].metaData).toStrictEqual([]);

		// Shipping Lines
		expect( order.shippingLines ).toHaveLength( 1 );
		expect( order.shippingLines[ 0 ] ).toBeInstanceOf( OrderShippingLine );
		//expect(order.shippingLines[0].id).toStrictEqual(123);
		expect( order.shippingLines[ 0 ].methodTitle ).toStrictEqual(
			'Foo Method Title'
		);
		expect( order.shippingLines[ 0 ].methodId ).toStrictEqual( 456 );
		expect( order.shippingLines[ 0 ].total ).toStrictEqual( '5.00' );
		//expect(order.shippingLines[0].totalTaxes).toStrictEqual('5.00');
		//expect(order.shippingLines[0].totalTax).toStrictEqual('10.00');
		//expect(order.shippingLines[0].taxes).toStrictEqual([ { id: 1, total: '6', subtotal: '6.6' } ]);
		expect( order.shippingLines[ 0 ].metaData ).toStrictEqual( [] );

		// Fee Lines
		expect( order.feeLines ).toHaveLength( 1 );
		expect( order.feeLines[ 0 ] ).toBeInstanceOf( OrderFeeLine );
		//expect(order.feeLines[0].id).toStrictEqual(123);
		expect( order.feeLines[ 0 ].name ).toStrictEqual( 'Foo Name' );
		expect( order.feeLines[ 0 ].total ).toStrictEqual( '5.00' );
		//expect(order.feeLines[0].totalTaxes).toStrictEqual('5.00');
		//expect(order.feeLines[0].totalTax).toStrictEqual('10.00');
		//expect(order.feeLines[0].taxes).toStrictEqual([ { id: 1, total: '6', subtotal: '6.6' } ]);
		expect( order.feeLines[ 0 ].metaData ).toStrictEqual( [] );

		// Coupon Lines
		expect( order.couponLines ).toHaveLength( 1 );
		expect( order.couponLines[ 0 ] ).toBeInstanceOf( OrderCouponLine );
		//expect(order.couponLines[0].id).toStrictEqual(6138);
		expect( order.couponLines[ 0 ].code ).toStrictEqual( 'save5' );
		expect( order.couponLines[ 0 ].discount ).toStrictEqual( '5' );
		expect( order.couponLines[ 0 ].discountTax ).toStrictEqual( '0.6' );
		expect( order.couponLines[ 0 ].metaData ).toHaveLength( 1 );
		expect( order.couponLines[ 0 ].metaData[ 0 ] ).toBeInstanceOf(
			MetaData
		);
		//expect(order.couponLines[0].metaData[0].id).toStrictEqual(57112);
		expect( order.couponLines[ 0 ].metaData[ 0 ].key ).toStrictEqual(
			'coupon_data'
		);
		expect( order.couponLines[ 0 ].metaData[ 0 ].value ).toStrictEqual(
			JSON.parse( responseOrderJson ).coupon_lines[ 0 ].meta_data[ 0 ]
				.value
		);

		// Refunds
		expect( order.refunds ).toHaveLength( 1 );
		expect( order.refunds[ 0 ] ).toBeInstanceOf( OrderRefundLine );
		//expect(order.refunds[0].id).toStrictEqual(123);
		expect( order.refunds[ 0 ].reason ).toStrictEqual( 'Foo Reason' );
		expect( order.refunds[ 0 ].total ).toStrictEqual( '5.00' );
	} );
} );
