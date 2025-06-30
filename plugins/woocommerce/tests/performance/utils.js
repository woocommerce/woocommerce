/**
 * External dependencies
 */
import { check } from 'k6';
import http from 'k6/http';
/**
 * Internal dependencies
 */
import { base_url, product_sku, product_url } from './config.js';
import {
	commonAPIGetRequestHeaders,
	commonNonStandardHeaders,
	commonRequestHeaders,
	jsonAPIRequestHeader,
} from './headers.js';

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

const getDefaultProduct = ( tag ) => {
	const requestHeaders = Object.assign(
		{},
		jsonAPIRequestHeader,
		commonAPIGetRequestHeaders,
		commonRequestHeaders,
		commonNonStandardHeaders
	);

	const response = http.get(
		`${ base_url }/wp-json/wc/store/v1/products/${ product_url }`,
		{
			requestHeaders,
			tags: { name: `${ tag } - Get default product data by url` },
		}
	);

	check( response, {
		'url query is status 200': ( r ) => r.status === 200,
		'url query response matches retrieved product data sku': ( r ) =>
			r.body.includes( `"sku":"${ product_sku }"` ),
	} );

	return response.json();
};

export { checkResponse, getDefaultProduct };
