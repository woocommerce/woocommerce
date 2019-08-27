/**
 * External dependencies
 */
import { min } from 'lodash';
import { DEFAULT_COLUMNS, DEFAULT_ROWS } from '@woocommerce/block-settings';

export default function getQuery( blockAttributes, name ) {
	const {
		attributes,
		attrOperator,
		categories,
		catOperator,
		tags,
		tagOperator,
		orderby,
		products,
	} = blockAttributes;
	const columns = blockAttributes.columns || DEFAULT_COLUMNS;
	const rows = blockAttributes.rows || DEFAULT_ROWS;
	const apiMax = Math.floor( 100 / columns ) * columns; // Prevent uneven final row.

	const query = {
		status: 'publish',
		per_page: min( [ rows * columns, apiMax ] ),
		catalog_visibility: 'visible',
	};

	if ( categories && categories.length ) {
		query.category = categories.join( ',' );
		if ( catOperator && 'all' === catOperator ) {
			query.category_operator = 'and';
		}
	}

	if ( tags && tags.length > 0 ) {
		query.tag = tags.join( ',' );
		if ( tagOperator && 'all' === tagOperator ) {
			query.tag_operator = 'and';
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
			query.attribute_operator = 'all' === attrOperator ? 'and' : 'in';
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
