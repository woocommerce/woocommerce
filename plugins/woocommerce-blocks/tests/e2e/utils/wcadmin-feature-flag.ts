/**
 * Internal dependencies
 */
import { cli } from './cli';

export const enableFeatureFlag = async ( featureFlag: string ) => {
	const { stdout } = await cli(
		'npm run wp-env run tests-cli -- wp option get wc_admin_helper_feature_values'
	);

	try {
		const featureFlagValues = JSON.parse( stdout );

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
