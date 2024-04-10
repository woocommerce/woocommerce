/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import { cli } from '@woocommerce/e2e-utils';

test.describe( 'Shop page', async () => {
	test( 'template selector is not visible in the Page editor', async ( {
		admin,
		page,
	} ) => {
		// Get Shop page ID.
		const cliOutput = await cli(
			`npm run wp-env run tests-cli -- wp option get woocommerce_shop_page_id`
		);
		const lines = cliOutput.stdout.split( '\n' );
		const shopPageId = lines.reverse().find( ( line ) => line !== '' );

		// eslint-disable-next-line playwright/no-conditional-in-test
		if ( ! shopPageId ) {
			throw new Error( 'Shop page ID not found' );
		}

		await admin.editPost( shopPageId );

		await expect( page.getByText( 'Template' ) ).toHaveCount( 0 );
	} );
} );
