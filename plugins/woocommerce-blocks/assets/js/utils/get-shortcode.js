export default function getShortcode( { attributes }, name ) {
	const { rows, columns, categories, orderby } = attributes;

	const shortcodeAtts = new Map();
	shortcodeAtts.set( 'limit', rows * columns );
	shortcodeAtts.set( 'columns', columns );

	if ( categories && categories.length ) {
		shortcodeAtts.set( 'category', categories.join( ',' ) );
	}

	if ( orderby ) {
		if ( 'price_desc' === orderby ) {
			shortcodeAtts.set( 'orderby', 'price' );
			shortcodeAtts.set( 'order', 'DESC' );
		} else if ( 'price_asc' === orderby ) {
			shortcodeAtts.set( 'orderby', 'price' );
			shortcodeAtts.set( 'order', 'ASC' );
		} else if ( 'date' === orderby ) {
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
	}

	// Build the shortcode string out of the set shortcode attributes.
	let shortcode = '[products';
	for ( const [ key, value ] of shortcodeAtts ) {
		shortcode += ' ' + key + '="' + value + '"';
	}
	shortcode += ']';

	return shortcode;
}
