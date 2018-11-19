export default function getShortcode( props ) {
	const { rows, columns, categories, orderby } = props.attributes;

	const shortcodeAtts = new Map();
	shortcodeAtts.set( 'limit', rows * columns );
	shortcodeAtts.set( 'columns', columns );
	shortcodeAtts.set( 'category', categories.join( ',' ) );

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

	// Build the shortcode string out of the set shortcode attributes.
	let shortcode = '[products';
	for ( const [ key, value ] of shortcodeAtts ) {
		shortcode += ' ' + key + '="' + value + '"';
	}
	shortcode += ']';

	return shortcode;
}
