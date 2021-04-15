/**
 * External dependencies
 */
import { stringify } from 'qs';

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

	const params = stringify( {
		experiment_name: experimentName,
		anon_id: anonId ?? undefined,
	} );

	const response = await window.fetch(
		`https://public-api.wordpress.com/wpcom/v2/experiments/${ EXPLAT_VERSION }/assignments/woocommerce?${ params }`
	);

	return await response.json();
};
