/**
 * External dependencies
 */
import { BLOCK_THEME_SLUG, cli } from '@woocommerce/e2e-utils';
import { test as setup, expect } from '@woocommerce/e2e-playwright-utils';

setup( 'Sets up the block theme', async () => {
	const isActiveOutput = await cli(
		`npm run wp-env run tests-cli -- wp theme is-active ${ BLOCK_THEME_SLUG }`
	);

	if ( isActiveOutput.stderr.includes( 'Command failed' ) ) {
		const installOutput = await cli(
			`npm run wp-env run tests-cli -- wp theme install ${ BLOCK_THEME_SLUG } --activate`
		);

		expect(
			installOutput.stdout,
			`Could not install and/or activate ${ BLOCK_THEME_SLUG }`
		).toContain( 'Success' );

		await cli(
			`npm run wp-env run tests-cli -- wp rewrite structure /%postname%/ --hard`
		);
		await cli( `npm run wp-env run tests-cli -- wp rewrite flush --hard` );
	}
} );
