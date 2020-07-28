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
import { getTaxCode } from 'analytics/report/taxes/utils';
import { NAMESPACE } from 'wc-api/constants';

/**
 * Get a function that accepts ids as they are found in url parameter and
 * returns a promise with an optional method applied to results
 *
 * @param {string|Function} path - api path string or a function of the query returning api path string
 * @param {Function} [handleData] - function applied to each iteration of data
 * @return {Function} - a function of ids returning a promise
 */
export function getRequestByIdString( path, handleData = identity ) {
	return function ( queryString = '', query ) {
		const pathString = typeof path === 'function' ? path( query ) : path;
		const idList = getIdsFromQuery( queryString );
		if ( idList.length < 1 ) {
			return Promise.resolve( [] );
		}
		const payload = {
			include: idList.join( ',' ),
			per_page: idList.length,
		};
		return apiFetch( {
			path: addQueryArgs( pathString, payload ),
		} ).then( ( data ) => data.map( handleData ) );
	};
}

export const getCategoryLabels = getRequestByIdString(
	NAMESPACE + '/products/categories',
	( category ) => ( {
		key: category.id,
		label: category.name,
	} )
);

export const getCouponLabels = getRequestByIdString(
	NAMESPACE + '/coupons',
	( coupon ) => ( {
		key: coupon.id,
		label: coupon.code,
	} )
);

export const getCustomerLabels = getRequestByIdString(
	NAMESPACE + '/customers',
	( customer ) => ( {
		key: customer.id,
		label: customer.name,
	} )
);

export const getProductLabels = getRequestByIdString(
	NAMESPACE + '/products',
	( product ) => ( {
		key: product.id,
		label: product.name,
	} )
);

export const getTaxRateLabels = getRequestByIdString(
	NAMESPACE + '/taxes',
	( taxRate ) => ( {
		key: taxRate.id,
		label: getTaxCode( taxRate ),
	} )
);

export const getVariationLabels = getRequestByIdString(
	( query ) => NAMESPACE + `/products/${ query.products }/variations`,
	( variation ) => {
		return {
			key: variation.id,
			label: variation.attributes.reduce(
				( desc, attribute, index, arr ) =>
					desc +
					`${ attribute.option }${
						arr.length === index + 1 ? '' : ', '
					}`,
				''
			),
		};
	}
);
