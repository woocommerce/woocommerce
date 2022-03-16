/**
 * External dependencies
 */
import { stringify } from 'qs';
import { applyFilters } from '@wordpress/hooks';
import apiFetch from '@wordpress/api-fetch';

const EXPLAT_VERSION = '0.1.0';

const getRequestQueryString = ( {
	experimentName,
	anonId,
}: {
	experimentName: string;
	anonId: string | null;
} ): string => {
	/**
	 * List of URL query parameters to be sent to the server.
	 *
	 * @filter woocommerce_explat_request_args
	 * @example
	 * addFilter(
	 * 	'woocommerce_explat_request_args',
	 * 	'woocommerce_explat_request_args',
	 * ( args ) => {
	 * 	args.experimentName = 'my-experiment';
	 * 	return args;
	 * });
	 *
	 */
	return stringify(
		applyFilters( 'woocommerce_explat_request_args', {
			experiment_name: experimentName,
			anon_id: anonId ?? undefined,
			woo_country_code:
				window.wcSettings?.preloadSettings?.general
					?.woocommerce_default_country ||
				window.wcSettings?.admin?.preloadSettings?.general
					?.woocommerce_default_country,
		} )
	);
};

export const fetchExperimentAssignment = async ( {
	experimentName,
	anonId,
}: {
	experimentName: string;
	anonId: string | null;
} ): Promise< unknown > => {
	if ( ! window.wcTracks?.isEnabled ) {
		throw new Error(
			`Tracking is disabled, can't fetch experimentAssignment`
		);
	}
	return await window.fetch(
		`https://public-api.wordpress.com/wpcom/v2/experiments/${ EXPLAT_VERSION }/assignments/woocommerce?${ getRequestQueryString(
			{
				experimentName,
				anonId,
			}
		) }`
	);
};

export const fetchExperimentAssignmentWithAuth = async ( {
	experimentName,
	anonId,
}: {
	experimentName: string;
	anonId: string | null;
} ): Promise< unknown > => {
	if ( ! window.wcTracks?.isEnabled ) {
		throw new Error(
			`Tracking is disabled, can't fetch experimentAssignment`
		);
	}
	// Use apiFetch to send request with credentials and nonce to our backend api to get the assignment with a user token via Jetpack.
	return await apiFetch( {
		path: `/wc-admin/experiments/assignment?${ getRequestQueryString( {
			experimentName,
			anonId,
		} ) }`,
	} );
};
