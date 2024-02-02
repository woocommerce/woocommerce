/**
 * External dependencies
 */
import { BLOCK_THEME_SLUG, cli } from '@woocommerce/e2e-utils';
import { test as setup } from '@woocommerce/e2e-playwright-utils';

setup( 'Sets up the block theme', async () => {
	await cli(
		`npm run wp-env run tests-cli -- wp theme install ${ BLOCK_THEME_SLUG } --activate`
	);
	// Enable permalinks.
	await cli(
		`npm run wp-env run tests-cli -- wp rewrite structure /%postname%/ --hard`
	);
	await cli( `npm run wp-env run tests-cli -- wp rewrite flush --hard` );
} );
