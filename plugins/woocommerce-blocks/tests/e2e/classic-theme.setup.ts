/**
 * External dependencies
 */
import {
	CLASSIC_THEME_NAME,
	CLASSIC_THEME_SLUG,
	DB_EXPORT_FILE,
	cli,
} from '@woocommerce/e2e-utils';
import { test as setup, expect } from '@woocommerce/e2e-playwright-utils';

setup( 'Sets up the classic theme', async ( { admin } ) => {
	await cli(
		`npm run wp-env run tests-cli -- wp theme install ${ CLASSIC_THEME_SLUG } --activate`
	);
	await admin.page.goto( '/wp-admin/themes.php' );
	await expect(
		admin.page.getByText( `Active: ${ CLASSIC_THEME_NAME }` )
	).toBeVisible();

	const cliOutput = await cli(
		`npm run wp-env run tests-cli wp db export ${ DB_EXPORT_FILE }`
	);
	expect( cliOutput.stdout ).toContain( 'Success: Exported' );
} );
