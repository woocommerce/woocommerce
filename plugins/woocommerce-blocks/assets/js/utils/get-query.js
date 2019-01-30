export default function getQuery( blockAttributes, name ) {
	const {
		attributes,
		attrOperator,
		categories,
		catOperator,
		columns,
		orderby,
		products,
		rows,
	} = blockAttributes;

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

	if ( attributes ) {
		query.attributes = attributes.reduce( ( accumulator, { attr_slug, id } ) => { // eslint-disable-line camelcase
			if ( accumulator[ attr_slug ] ) {
				accumulator[ attr_slug ].push( id );
			} else {
				accumulator[ attr_slug ] = [ id ];
			}
			return accumulator;
		}, {} );

		if ( attrOperator ) {
			query.attr_operator = 'all' === attrOperator ? 'AND' : 'IN';
			query.tax_relation = 'all' === attrOperator ? 'AND' : 'OR';
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
