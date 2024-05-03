/* eslint-disable playwright/no-standalone-expect */
/**
 * External dependencies
 */
import { BLOCK_THEME_SLUG, DB_EXPORT_FILE, cli } from '@woocommerce/e2e-utils';
import { test as setup, expect } from '@woocommerce/e2e-playwright-utils';

setup( 'Sets up the block theme', async () => {
	let cliOutput = await cli(
		`npm run wp-env run tests-cli -- wp theme install ${ BLOCK_THEME_SLUG } --activate`
	);

	expect(
		cliOutput.stdout,
		`Could not install and/or activate ${ BLOCK_THEME_SLUG }`
	).toContain( 'Success' );

	// Enable permalinks and perform a hard flush.
	cliOutput = await cli(
		`npm run wp-env run tests-cli -- wp rewrite structure /%postname%/ --hard`
	);

	expect( cliOutput.stdout ).toContain( 'Success: Rewrite structure set' );
	expect( cliOutput.stdout ).toContain( 'Success: Rewrite rules flushed' );

	cliOutput = await cli(
		`npm run wp-env run tests-cli wp db export ${ DB_EXPORT_FILE }`
	);
	expect( cliOutput.stdout ).toContain( 'Success: Exported' );
} );
