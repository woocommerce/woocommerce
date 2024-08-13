/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

test.describe( 'Product Filters Template Part', () => {
	test.beforeEach( async ( { admin, requestUtils } ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-enable-experimental-features'
		);
		await admin.visitSiteEditor( {
			postType: 'wp_template_part',
			postId: 'woocommerce/woocommerce//product-filters',
			canvas: 'edit',
		} );
	} );

	test( 'should be visible in the templates part list', async ( {
		page,
		admin,
	} ) => {
		await admin.visitSiteEditor( {
			postType: 'wp_template_part',
		} );
		const templatePart = page
			.getByLabel( 'Patterns content' )
			.getByText( 'Product Filters (Experimental)', { exact: true } )
			.and( page.getByRole( 'button' ) );
		await expect( templatePart ).toBeVisible();
	} );

	test( 'should render the Product Filters block', async ( { editor } ) => {
		const productFiltersBlock = editor.canvas.getByLabel(
			'Block: Product Filters (Experimental)'
		);
		await expect( productFiltersBlock ).toBeVisible();
	} );
} );
