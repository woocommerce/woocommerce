/** @format */
/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { identity } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { getIdsFromQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { NAMESPACE } from 'wc-api/constants';

/**
 * Get a function that accepts ids as they are found in url parameter and
 * returns a promise with an optional method applied to results
 *
 * @param {string|function} path - api path string or a function of the query returning api path string
 * @param {Function} [handleData] - function applied to each iteration of data
 * @returns {Function} - a function of ids returning a promise
 */
export function getRequestByIdString( path, handleData = identity ) {
	return function( queryString = '', query ) {
		const pathString = 'function' === typeof path ? path( query ) : path;
		const idList = getIdsFromQuery( queryString );
		if ( idList.length < 1 ) {
			return Promise.resolve( [] );
		}
		const payload = {
			include: idList.join( ',' ),
			per_page: idList.length,
		};
		return apiFetch( { path: addQueryArgs( pathString, payload ) } ).then( data =>
			data.map( handleData )
		);
	};
}

export const getCategoryLabels = getRequestByIdString(
	NAMESPACE + '/products/categories',
	category => ( {
		id: category.id,
		label: category.name,
	} )
);

export const getCouponLabels = getRequestByIdString( NAMESPACE + '/coupons', coupon => ( {
	id: coupon.id,
	label: coupon.code,
} ) );

export const getCustomerLabels = getRequestByIdString( NAMESPACE + '/customers', customer => ( {
	id: customer.id,
	label: customer.name,
} ) );

export const getProductLabels = getRequestByIdString( NAMESPACE + '/products', product => ( {
	id: product.id,
	label: product.name,
} ) );

export const getVariationLabels = getRequestByIdString(
	query => NAMESPACE + `/products/${ query.products }/variations`,
	variation => {
		return {
			id: variation.id,
			label: variation.attributes.reduce(
				( desc, attribute, index, arr ) =>
					desc + `${ attribute.option }${ arr.length === index + 1 ? '' : ', ' }`,
				''
			),
		};
	}
);
