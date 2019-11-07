/**
 * External dependencies
 */
import { DEFAULT_COLUMNS, DEFAULT_ROWS } from '@woocommerce/block-settings';

export default function getShortcode( props, name ) {
	const blockAttributes = props.attributes;
	const {
		attributes,
		attrOperator,
		categories,
		catOperator,
		orderby,
		products,
	} = blockAttributes;
	const columns = blockAttributes.columns || DEFAULT_COLUMNS;
	const rows = blockAttributes.rows || DEFAULT_ROWS;

	const shortcodeAtts = new Map();
	shortcodeAtts.set( 'limit', rows * columns );
	shortcodeAtts.set( 'columns', columns );

	if ( categories && categories.length ) {
		shortcodeAtts.set( 'category', categories.join( ',' ) );
		if ( catOperator && catOperator === 'all' ) {
			shortcodeAtts.set( 'cat_operator', 'AND' );
		}
	}

	if ( attributes && attributes.length ) {
		shortcodeAtts.set(
			'terms',
			attributes.map( ( { id } ) => id ).join( ',' )
		);
		shortcodeAtts.set( 'attribute', attributes[ 0 ].attr_slug );
		if ( attrOperator && attrOperator === 'all' ) {
			shortcodeAtts.set( 'terms_operator', 'AND' );
		}
	}

	if ( orderby ) {
		if ( orderby === 'price_desc' ) {
			shortcodeAtts.set( 'orderby', 'price' );
			shortcodeAtts.set( 'order', 'DESC' );
		} else if ( orderby === 'price_asc' ) {
			shortcodeAtts.set( 'orderby', 'price' );
			shortcodeAtts.set( 'order', 'ASC' );
		} else if ( orderby === 'date' ) {
			shortcodeAtts.set( 'orderby', 'date' );
			shortcodeAtts.set( 'order', 'DESC' );
		} else {
			shortcodeAtts.set( 'orderby', orderby );
		}
	}

	// Toggle shortcode atts depending on block type.
	switch ( name ) {
		case 'woocommerce/product-best-sellers':
			shortcodeAtts.set( 'best_selling', '1' );
			break;
		case 'woocommerce/product-top-rated':
			shortcodeAtts.set( 'orderby', 'rating' );
			break;
		case 'woocommerce/product-on-sale':
			shortcodeAtts.set( 'on_sale', '1' );
			break;
		case 'woocommerce/product-new':
			shortcodeAtts.set( 'orderby', 'date' );
			shortcodeAtts.set( 'order', 'DESC' );
			break;
		case 'woocommerce/handpicked-products':
			if ( ! products.length ) {
				return '';
			}
			shortcodeAtts.set( 'ids', products.join( ',' ) );
			shortcodeAtts.set( 'limit', products.length );
			break;
		case 'woocommerce/product-category':
			if ( ! categories || ! categories.length ) {
				return '';
			}
			break;
		case 'woocommerce/products-by-attribute':
			if ( ! attributes || ! attributes.length ) {
				return '';
			}
			break;
	}

	// Build the shortcode string out of the set shortcode attributes.
	let shortcode = '[products';
	for ( const [ key, value ] of shortcodeAtts ) {
		/* eslint-disable-line */
		shortcode += ' ' + key + '="' + value + '"';
	}
	shortcode += ']';

	return shortcode;
}
