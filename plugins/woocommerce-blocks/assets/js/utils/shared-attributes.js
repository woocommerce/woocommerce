export default {
	/**
	 * Alignment of product grid
	 */
	align: {
		type: 'string',
	},

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
	 * How to order the products: 'date', 'popularity', 'price_asc', 'price_desc' 'rating', 'title'.
	 */
	orderby: {
		type: 'string',
		default: 'date',
	},
};
