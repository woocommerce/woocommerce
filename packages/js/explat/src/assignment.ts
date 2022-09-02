/**
 * External dependencies
 */
import { stringify } from 'qs';
import { applyFilters } from '@wordpress/hooks';
import apiFetch from '@wordpress/api-fetch';

const EXPLAT_VERSION = '0.1.0';

type QueryParams = {
	[ key: string ]: unknown;
} & {
	experiment_name: string;
	anon_id: string | null;
	woo_country_code: string;
	woo_wcadmin_install_timestamp: string;
};

const isValidQueryParams = (
	queryParams: unknown
): queryParams is QueryParams => {
	return (
		( queryParams as QueryParams ).hasOwnProperty( 'experiment_name' ) &&
		( queryParams as QueryParams ).hasOwnProperty( 'woo_country_code' ) &&
		( queryParams as QueryParams ).hasOwnProperty(
			'woo_wcadmin_install_timestamp'
		)
	);
};

const getRequestQueryParams = ( {
	experimentName,
	anonId,
}: {
	experimentName: string;
	anonId: string | null;
} ): QueryParams => {
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
	 */
	const queryParams = applyFilters( 'woocommerce_explat_request_args', {
		experiment_name: experimentName,
		anon_id: anonId ?? undefined,
		woo_country_code:
			window.wcSettings?.preloadSettings?.general
				?.woocommerce_default_country ||
			window.wcSettings?.admin?.preloadSettings?.general
				?.woocommerce_default_country,
		woo_wcadmin_install_timestamp:
			window.wcSettings?.admin?.preloadOptions
				?.woocommerce_admin_install_timestamp,
	} );

	if ( ! isValidQueryParams( queryParams ) ) {
		throw new Error(
			`Invalid query Params: ${ JSON.stringify( queryParams ) }`
		);
	}

	// Make sure test name is a valid one.
	if ( ! /^[A-Za-z0-9_]+$/.test( queryParams.experiment_name ) ) {
		throw new Error(
			`Invalid A/B test name: ${ queryParams.experiment_name }`
		);
	}
	return queryParams;
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

	const queryParams = getRequestQueryParams( { experimentName, anonId } );
	if ( ! queryParams.anon_id ) {
		throw new Error(
			`Can't fetch experiment assignment without an anonId or auth, please initialize anonId first or use fetchExperimentAssignmentWithAuth instead.`
		);
	}

	const response = await window.fetch(
		`https://public-api.wordpress.com/wpcom/v2/experiments/${ EXPLAT_VERSION }/assignments/woocommerce?${ stringify(
			queryParams
		) }`
	);
	return await response.json();
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
		path: `/wc-admin/experiments/assignment?${ stringify(
			getRequestQueryParams( {
				experimentName,
				anonId,
			} )
		) }`,
	} );
};
