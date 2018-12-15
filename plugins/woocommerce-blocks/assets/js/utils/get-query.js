export default function getQuery( attributes, name ) {
	const { categories, columns, orderby, rows } = attributes;

	const query = {
		status: 'publish',
		per_page: rows * columns,
	};

	if ( categories && categories.length ) {
		query.category = categories.join( ',' );
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

	// Toggle shortcode atts depending on block type.
	switch ( name ) {
		case 'woocommerce/product-best-sellers':
			query.orderby = 'popularity';
			break;
<<<<<<< HEAD
		case 'woocommerce/product-top-rated':
			query.orderby = 'rating';
=======
		case 'woocommerce/product-on-sale':
			query.on_sale = 1;
>>>>>>> 75e957b33c668e2ef98500fab68ecfe8ca705ac4
			break;
	}

	return query;
}
