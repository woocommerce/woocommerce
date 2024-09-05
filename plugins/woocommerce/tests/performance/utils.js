/**
 * External dependencies
 */
import { check } from 'k6';

/**
 * Internal dependencies
 */

const checkResponse = (
	response,
	statusCode,
	page = {
		title: '',
		body: '',
		footer: '',
	}
) => {
	check( response, {
		[ `is status ${ statusCode }` ]: ( r ) => r.status === statusCode,
		[ `title is: "${ page.title }"` ]: ( r ) =>
			r.html().find( 'head title' ).text() === page.title,
		[ `body contains: ${ page.body }` ]: ( r ) =>
			r.body.includes( page.body ),
		[ `footer contains: ${ page.footer }` ]: ( r ) =>
			r.html().find( 'body footer' ).text().includes( page.footer ),
	} );
};

export { checkResponse };
