/**
 * The default fixtures data is shaped according to WC REST API
 *
 * @see {@link https://woocommerce.github.io/woocommerce-rest-api-docs|WooCommerce REST API}
 */

/**
 * Product attributes fixture data, using the create attribute and batch create terms.
 *
 * @see {@link https://woocommerce.github.io/woocommerce-rest-api-docs/#create-a-product-attribute|Create a product attribute}
 * @see {@link https://woocommerce.github.io/woocommerce-rest-api-docs/#batch-update-attribute-terms|Batch update attribute terms}
 */
const Attributes = () => [
	{
		attribute: { name: 'Capacity' },
		terms: [
			{
				name: '32gb',
			},
			{
				name: '64gb',
			},
			{
				name: '128gb',
			},
		],
	},
	{
		attribute: { name: 'Shade' },
		terms: [
			{
				name: 'Red',
			},
			{
				name: 'Blue',
			},
			{
				name: 'Black',
			},
		],
	},
];

/**
 * Coupons fixture data, using the create batch endpoint
 *
 * @see {@link https://woocommerce.github.io/woocommerce-rest-api-docs/#batch-update-coupons|Batch update coupons}
 */
const Coupons = () => [
	{
		code: 'coupon',
		discount_type: 'fixed_cart',
		amount: '5',
	},
	{
		code: 'oldcoupon',
		discount_type: 'fixed_cart',
		amount: '5',
		date_expires: '2020-01-01',
	},
	{
		code: 'below100',
		discount_type: 'percent',
		amount: '20',
		maximum_amount: '100.00',
	},
	{
		code: 'above50',
		discount_type: 'percent',
		amount: '20',
		minimum_amount: '50.00',
	},
	{
		code: 'a12s',
		discount_type: 'percent',
		amount: '100',
		individual_use: true,
		email_restrictions: '*@automattic.com%2C *@a8c.com',
	},
	{
		code: 'freeshipping',
		discount_type: 'percent',
		amount: '0',
		free_shipping: true,
	},
];

/**
 * Reviews fixture data, using the create batch endpoint
 *
 * @see {@link https://woocommerce.github.io/woocommerce-rest-api-docs/#batch-update-product-reviews|Batch update product reviews}
 * @param {number} id Product ID to add reviews to.
 */
const ReviewsInProduct = ( id ) => [
	{
		product_id: id,
		review: 'Looks fine',
		reviewer: 'John Doe',
		reviewer_email: 'john.doe@example.com',
		rating: 4,
	},
	{
		product_id: id,
		review: 'I love this album',
		reviewer: 'John Doe',
		reviewer_email: 'john.doe@example.com',
		rating: 5,
	},
	{
		product_id: id,
		review: 'a fine review',
		reviewer: "John Doe' niece",
		reviewer_email: 'john.doe@example.com',
		rating: 5,
	},
];

/**
 * Product category fixture data, using the create batch endpoint
 *
 * @see {@link https://woocommerce.github.io/woocommerce-rest-api-docs/#batch-update-product-categories|Batch update product categories}
 */
const Categories = () => [
	{
		name: 'Music',
	},
];

/**
 * Product fixture data, using the create batch endpoint
 *
 * @see {@link https://woocommerce.github.io/woocommerce-rest-api-docs/#batch-update-products|Batch update products}
 */
const Products = () => [
	{
		name: 'Woo Single #1',
		type: 'simple',
		regular_price: '21.99',
		virtual: true,
		downloadable: true,
		downloads: [
			{
				name: 'Woo Single',
				file:
					'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/cd_4_angle.jpg',
			},
		],
		images: [
			{
				src:
					'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/cd_4_angle.jpg',
			},
		],
		categories: [ 'Music' ],
	},
	{
		name: '128GB USB Stick',
		type: 'simple',
		regular_price: '2.99',
		virtual: false,
		downloadable: false,
		images: [
			{
				src:
					'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/cd_4_angle.jpg',
			},
		],
		attributes: [
			{
				name: 'Capacity',
				position: 0,
				visible: true,
				options: [ '128gb' ],
			},
		],
	},
	{
		name: '32GB USB Stick',
		type: 'simple',
		regular_price: '1.99',
		virtual: false,
		downloadable: false,
		images: [
			{
				src:
					'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/cd_4_angle.jpg',
			},
		],
		attributes: [
			{
				name: 'Capacity',
				position: 0,
				visible: true,
				options: [ '32gb' ],
			},
		],
	},
	{
		name: 'Woo Single #2',
		type: 'simple',
		regular_price: '25.99',
		virtual: true,
		downloadable: true,
		downloads: [
			{
				name: 'Woo Single 2',
				file:
					'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/cd_4_angle.jpg',
			},
		],
		images: [
			{
				src:
					'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/cd_4_angle.jpg',
			},
		],
		categories: [ 'Music' ],
	},
];

/**
 * Settings fixture data, using the update batch endpoint.
 *
 * @see {@link https://woocommerce.github.io/woocommerce-rest-api-docs/#batch-update-setting-options|Batch update setting options}
 */
const Settings = () => [
	{
		id: 'woocommerce_store_address',
		value: '60 29th Street #343',
	},
	{
		id: 'woocommerce_store_city',
		value: 'San Francisco',
	},
	{
		id: 'woocommerce_store_country',
		value: 'US:CA',
	},
	{
		id: 'woocommerce_store_postcode',
		value: '94110',
	},
	{
		id: 'woocommerce_allowed_countries',
		value: 'specific',
	},
	{
		id: 'woocommerce_specific_allowed_countries',
		value: [ 'DZ', 'CA', 'NZ', 'ES', 'GB', 'US' ],
	},
	{
		id: 'woocommerce_ship_to_countries',
		value: 'specific',
	},
	{
		id: 'woocommerce_specific_ship_to_countries',
		value: [ 'DZ', 'CA', 'NZ', 'ES', 'GB', 'US' ],
	},
	{
		id: 'woocommerce_enable_coupons',
		value: 'yes',
	},
	{
		id: 'woocommerce_calc_taxes',
		value: 'yes',
	},
	{
		id: 'woocommerce_currency',
		value: 'USD',
	},
];

/**
 * Page settings fixture data, using the update batch endpoint.
 *
 * @param {Array} pages
 *
 * @see {@link https://woocommerce.github.io/woocommerce-rest-api-docs/#batch-update-setting-options|Batch update setting options}
 */
const PageSettings = ( pages = [] ) => {
	const cartPage = pages.find( ( page ) =>
		page.slug.includes( 'cart-block' )
	);
	const checkoutPage = pages.find( ( page ) =>
		page.slug.includes( 'checkout-block' )
	);

	return [
		{
			id: 'woocommerce_cart_page_id',
			value: cartPage?.id.toString() || '',
		},
		{
			id: 'woocommerce_checkout_page_id',
			value: checkoutPage?.id.toString() || '',
		},
	];
};

/**
 * Shipping Zones fixture data, using the shipping zone endpoint, shipping
 * location, and shipping method endpoint.
 *
 * @see {@link https://woocommerce.github.io/woocommerce-rest-api-docs/#create-a-shipping-zone|Create a shipping zone}
 *  * @see {@link https://woocommerce.github.io/woocommerce-rest-api-docs/#update-a-locations-of-a-shipping-zone|Update a locations of a shipping zone}
 *  * @see {@link https://woocommerce.github.io/woocommerce-rest-api-docs/#include-a-shipping-method-to-a-shipping-zone|Include a shipping method to a shipping zone}
 */
const Shipping = () => [
	{
		name: 'UK',
		locations: [
			{
				code: 'UK',
			},
		],
		methods: [
			{
				method_id: 'flat_rate',
				settings: {
					title: 'Normal Shipping',
					cost: '20.00',
				},
			},
			{
				method_id: 'free_shipping',
				settings: {
					title: 'Free Shipping',
					cost: '00.00',
					requires: 'coupon',
				},
			},
		],
	},
];

/**
 * Taxes rates fixture data, using the create batch endpoint.
 *
 * @see {@link https://woocommerce.github.io/woocommerce-rest-api-docs/#batch-update-tax-rates|Batch update tax rates}
 */
const Taxes = () => [
	{
		country: 'US',
		rate: '5.0000',
		name: 'State Tax',
		shipping: false,
		priority: 1,
	},
	{
		country: 'US',
		rate: '10.000',
		name: 'Sale Tax',
		shipping: false,
		priority: 2,
	},
	{
		country: 'UK',
		rate: '20.000',
		name: 'VAT',
		shipping: false,
	},
];

module.exports = {
	Attributes,
	Coupons,
	ReviewsInProduct,
	Categories,
	Products,
	Settings,
	PageSettings,
	Shipping,
	Taxes,
};
