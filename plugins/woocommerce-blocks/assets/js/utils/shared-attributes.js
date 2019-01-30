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
};
