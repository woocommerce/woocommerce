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
			postId: 'woocommerce/woocommerce//product-filters-overlay',
			canvas: 'edit',
		} );
	} );

	test( 'should be visible in the template parts list', async ( {
		page,
		admin,
	} ) => {
		await admin.visitSiteEditor( {
			postType: 'wp_template_part',
		} );
		const block = page
			.getByLabel( 'Patterns content' )
			.getByText( 'Filters Overlay' )
			.and( page.getByRole( 'button' ) );
		await expect( block ).toBeVisible();
	} );

	test( 'should render the correct inner blocks', async ( { editor } ) => {
		const productFiltersTemplatePart = editor.canvas
			.locator( '[data-type="core/template-part"]' )
			.filter( {
				has: editor.canvas.getByLabel(
					'Block: Product Filters (Experimental)'
				),
			} );

		await expect( productFiltersTemplatePart ).toBeVisible();
	} );
} );
