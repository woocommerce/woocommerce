export default function getQuery( attributes ) {
	const { categories, columns, orderby, rows } = attributes;

	const query = {
		status: 'publish',
		per_page: rows * columns,
	};

	if ( categories ) {
		query.category = categories.join( ',' );
	}

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

	return query;
}
