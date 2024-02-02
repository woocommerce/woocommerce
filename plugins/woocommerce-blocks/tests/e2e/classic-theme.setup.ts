/**
 * External dependencies
 */
import { CLASSIC_THEME_SLUG, cli } from '@woocommerce/e2e-utils';
import { test as setup } from '@woocommerce/e2e-playwright-utils';

setup( 'Sets up the classic theme', async () => {
	await cli(
		`npm run wp-env run tests-cli -- wp theme install ${ CLASSIC_THEME_SLUG } --activate`
	);
} );
