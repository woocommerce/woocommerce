/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

test.describe( 'Filters Overlay Template Part', () => {
	test.beforeEach( async ( { admin, requestUtils } ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-enable-experimental-features'
		);
		await admin.visitSiteEditor( {
			postType: 'wp_template_part',
		} );
	} );

	test( 'should be visible', async ( { page } ) => {
		const block = page
			.getByLabel( 'Patterns content' )
			.getByText( 'Filters Overlay' )
			.and( page.getByRole( 'button' ) );
		await expect( block ).toBeVisible();
	} );
} );
