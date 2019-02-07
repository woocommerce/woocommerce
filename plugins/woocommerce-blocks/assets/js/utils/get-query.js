export default function getQuery( blockAttributes, name ) {
	const {
		attributes,
		attrOperator,
		categories,
		catOperator,
		orderby,
		products,
	} = blockAttributes;
	const columns = blockAttributes.columns || wc_product_block_data.default_columns;
	const rows = blockAttributes.rows || wc_product_block_data.default_rows;

	const query = {
		status: 'publish',
		per_page: rows * columns,
	};

	if ( categories && categories.length ) {
		query.category = categories.join( ',' );
		if ( catOperator && 'all' === catOperator ) {
			query.cat_operator = 'AND';
		}
	}

	if ( orderby ) {
		if ( 'price_desc' === orderby ) {
			query.orderby = 'price';
			query.order = 'desc';
		} else if ( 'price_asc' === orderby ) {
			query.orderby = 'price';
			query.order = 'asc';
		} else if ( 'title' === orderby ) {
			query.orderby = 'title';
			query.order = 'asc';
		} else if ( 'menu_order' === orderby ) {
			query.orderby = 'menu_order';
			query.order = 'asc';
		} else {
			query.orderby = orderby;
		}
	}

	if ( attributes && attributes.length > 0 ) {
		query.attribute_term = attributes.map( ( { id } ) => id ).join( ',' );
		query.attribute = attributes[ 0 ].attr_slug;

		if ( attrOperator ) {
			query.attr_operator = 'all' === attrOperator ? 'AND' : 'IN';
		}
	}

	// Toggle query parameters depending on block type.
	switch ( name ) {
		case 'woocommerce/product-best-sellers':
			query.orderby = 'popularity';
			break;
		case 'woocommerce/product-top-rated':
			query.orderby = 'rating';
			break;
		case 'woocommerce/product-on-sale':
			query.on_sale = 1;
			break;
		case 'woocommerce/product-new':
			query.orderby = 'date';
			break;
		case 'woocommerce/handpicked-products':
			query.include = products;
			query.per_page = products.length;
			break;
	}

	return query;
}
