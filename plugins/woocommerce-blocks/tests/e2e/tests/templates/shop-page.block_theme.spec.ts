/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import { cli } from '@woocommerce/e2e-utils';

test.describe( 'Shop page', () => {
	test( 'template selector is not visible in the Page editor', async ( {
		admin,
		page,
	} ) => {
		// Get Shop page ID.
		const cliOutput = await cli(
			`npm run wp-env run tests-cli -- wp option get woocommerce_shop_page_id`
		);
		const numberMatch = cliOutput.stdout.match( /\d+/ );
		// eslint-disable-next-line playwright/no-conditional-in-test
		if ( numberMatch === null ) {
			throw new Error( 'Shop page ID not found' );
		}

		await admin.editPost( numberMatch[ 0 ] );

		await expect( page.getByText( 'Template' ) ).toBeHidden();
	} );
} );
