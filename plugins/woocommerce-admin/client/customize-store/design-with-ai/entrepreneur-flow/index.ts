/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';

export const ENTREPRENEUR_FLOW_QUERY_PARAM_VALUE = 'entrepreneur-signup';
export const ENTREPRENEUR_FLOW_QUERY_PARAM_KEY = 'ref';

addFilter(
	'woocommerce_admin_persisted_queries',
	'woocommerce_admin_customize_your_store',
	( params ) => {
		params.push( ENTREPRENEUR_FLOW_QUERY_PARAM_KEY );
		return params;
	}
);

export const isEntrepreneurFlow = () => {
	const urlParams = new URLSearchParams( window.location.search );
	const param = urlParams.get( ENTREPRENEUR_FLOW_QUERY_PARAM_KEY );
	return param === ENTREPRENEUR_FLOW_QUERY_PARAM_VALUE;
};
