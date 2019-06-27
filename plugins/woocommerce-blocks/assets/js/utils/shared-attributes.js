
export const sharedAttributeBlockTypes = [
	'woocommerce/product-best-sellers',
	'woocommerce/product-category',
	'woocommerce/product-new',
	'woocommerce/product-on-sale',
	'woocommerce/product-top-rated',
];

export default {
	/**
	 * Number of columns.
	 */
	columns: {
		type: 'number',
		default: wc_product_block_data.default_columns,
	},

	/**
	 * Number of rows.
	 */
	rows: {
		type: 'number',
		default: wc_product_block_data.default_rows,
	},

	/**
	 * How to align cart buttons.
	 */
	alignButtons: {
		type: 'boolean',
		default: false,
	},

	/**
	 * Product category, used to display only products in the given categories.
	 */
	categories: {
		type: 'array',
		default: [],
	},

	/**
	 * Product category operator, used to restrict to products in all or any selected categories.
	 */
	catOperator: {
		type: 'string',
		default: 'any',
	},

	/**
	 * Content visibility setting
	 */
	contentVisibility: {
		type: 'object',
		default: {
			title: true,
			price: true,
			rating: true,
			button: true,
		},
	},
};
