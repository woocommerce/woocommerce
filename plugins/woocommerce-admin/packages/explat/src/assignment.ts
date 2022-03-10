/**
 * External dependencies
 */
import { stringify } from 'qs';
import { applyFilters } from '@wordpress/hooks';

const EXPLAT_VERSION = '0.1.0';

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
	const params = applyFilters( 'woocommerce_explat_request_args', {
		experiment_name: experimentName,
		anon_id: anonId ?? undefined,
		woo_country_code:
			window.wcSettings?.preloadSettings?.general
				?.woocommerce_default_country ||
			window.wcSettings?.admin?.preloadSettings?.general
				?.woocommerce_default_country,
	} );

	const response = await window.fetch(
		`https://public-api.wordpress.com/wpcom/v2/experiments/${ EXPLAT_VERSION }/assignments/woocommerce?${ stringify(
			params
		) }`
	);

	return await response.json();
};
