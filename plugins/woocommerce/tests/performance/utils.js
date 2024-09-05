/**
 * External dependencies
 */
import { check } from 'k6';

const checkResponse = (
	response,
	expectedStatus,
	expectedTitle,
	expectedBody,
	expectedFooter
) => {
	check( response, {
		'is status 200': ( r ) => r.status === expectedStatus,
		[ `title is: "${ expectedTitle }"` ]: ( r ) =>
			r.html().find( 'head title' ).text() === expectedTitle,
		[ `body contains: ${ expectedBody }` ]: ( r ) =>
			r.body.includes( expectedBody ) ||
			( console.log( r.body ) && false ),
		[ `footer contains: ${ expectedFooter }` ]: ( r ) =>
			r.html().find( 'body footer' ).text().includes( expectedFooter ),
	} );
};

export { checkResponse };
