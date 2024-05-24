/**
 * Internal dependencies
 */
import { cli } from './cli';

export const enableFeatureFlag = async ( featureFlag: string ) => {
	let featureFlagValues = {};

	try {
		const { stdout } = await cli(
			'npm run wp-env run tests-cli -- wp option get wc_admin_helper_feature_values --json'
		);

		featureFlagValues = JSON.parse( stdout );
		// wp option get will fail if the option does not exist, so we catch the error and ignore it.
	} catch ( error ) {}

	try {
		await cli(
			`npm run wp-env run tests-cli -- wp option update wc_admin_helper_feature_values '${ JSON.stringify(
				{ ...featureFlagValues, [ featureFlag ]: 1 }
			) }'`
		);
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( 'Failed to enable feature flag:', error );
		return false;
	}

	return true;
};
