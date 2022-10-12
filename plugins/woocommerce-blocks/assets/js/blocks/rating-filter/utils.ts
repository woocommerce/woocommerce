/**
 * External dependencies
 */
import { isString } from '@woocommerce/types';
import { getUrlParameter } from '@woocommerce/utils';

export const getActiveFilters = ( queryParamKey = 'filter_rating' ) => {
	const params = getUrlParameter( queryParamKey );

	if ( ! params ) {
		return [];
	}

	const parsedParams = isString( params )
		? params.split( ',' )
		: ( params as string[] );

	return parsedParams;
};

export const parseAttributes = ( data: Record< string, unknown > ) => {
	return {
		showFilterButton: data?.showFilterButton === 'true',
		showCounts: data?.showCounts !== 'false',
		isPreview: false,
	};
};
