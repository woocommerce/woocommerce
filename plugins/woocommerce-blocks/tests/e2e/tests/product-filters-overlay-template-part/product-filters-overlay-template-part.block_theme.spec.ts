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
			path: '/wp_template_part/all',
		} );
	} );

	test( 'should be visible', async ( { page } ) => {
		const block = page.getByRole( 'button', { name: 'Filters Overlay 1' } );
		await expect( block ).toBeVisible();
	} );
} );
