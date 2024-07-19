/**
 * External dependencies
 */
import { test, expect, wpCLI } from '@woocommerce/e2e-utils';

test.describe( 'Shop page', () => {
	test( 'template selector is not visible in the Page editor', async ( {
		admin,
		page,
	} ) => {
		const cliOutput = await wpCLI( 'option get woocommerce_shop_page_id' );
		const shopPageId = cliOutput.stdout.match( /\d+/ )?.pop();
		if ( ! shopPageId ) {
			throw new Error( 'Shop page ID not found' );
		}

		await admin.editPost( shopPageId );

		await expect( page.getByText( 'Template' ) ).toBeHidden();
	} );
} );
