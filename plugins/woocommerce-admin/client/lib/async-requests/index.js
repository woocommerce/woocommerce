/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { identity } from 'lodash';
import { getIdsFromQuery } from '@woocommerce/navigation';
import { NAMESPACE } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { getTaxCode } from '../../analytics/report/taxes/utils';
import { getAdminSetting } from '~/utils/admin-settings';

/**
 * Get a function that accepts ids as they are found in url parameter and
 * returns a promise with an optional method applied to results
 *
 * @param {string|Function} path         - api path string or a function of the query returning api path string
 * @param {Function}        [handleData] - function applied to each iteration of data
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

export const getAttributeLabels = getRequestByIdString(
	NAMESPACE + '/products/attributes',
	( attribute ) => ( {
		key: attribute.id,
		label: attribute.name,
	} )
);

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

/**
 * Create a variation name by concatenating each of the variation's
 * attribute option strings.
 *
 * @param {Object} variation            - variation returned by the api
 * @param {Array}  variation.attributes - attribute objects, with option property.
 * @param {string} variation.name       - name of variation.
 * @return {string} - formatted variation name
 */
export function getVariationName( { attributes, name } ) {
	const separator = getAdminSetting(
		'variationTitleAttributesSeparator',
		' - '
	);

	if ( name && name.indexOf( separator ) > -1 ) {
		return name;
	}

	const attributeList = ( attributes || [] )
		.map( ( { name: attributeName, option } ) => {
			if ( ! option ) {
				attributeName =
					attributeName.charAt( 0 ).toUpperCase() +
					attributeName.slice( 1 );
				option = sprintf(
					// translators: %s: the attribute name.
					__( 'Any %s', 'woocommerce' ),
					attributeName
				);
			}
			return option;
		} )
		.join( ', ' );

	return attributeList ? name + separator + attributeList : name;
}

export const getVariationLabels = getRequestByIdString(
	( { products } ) => {
		// If a product was specified, get just its variations.
		if ( products ) {
			return NAMESPACE + `/products/${ products }/variations`;
		}

		return NAMESPACE + '/variations';
	},
	( variation ) => {
		return {
			key: variation.id,
			label: getVariationName( variation ),
		};
	}
);
