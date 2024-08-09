/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

const blockData = {
	name: 'woocommerce/product-filters-overlay-navigation',
	title: 'Overlay Navigation (Experimental)',
	selectors: {
		frontend: {},
		editor: {
			settings: {},
		},
	},
	templateSlug: 'product-filters-overlay',
	templateType: 'wp_template_part',
};

test.describe( `Filters Overlay Navigation`, () => {
	test.beforeEach( async ( { admin, requestUtils } ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-enable-experimental-features'
		);
		await admin.visitSiteEditor( {
			postId: `woocommerce/woocommerce//${ blockData.templateSlug }`,
			postType: blockData.templateType,
			canvas: 'edit',
		} );
	} );

	test( 'should be included in the Filters Overlay template part', async ( {
		editor,
	} ) => {
		const block = editor.canvas.getByLabel( `Block: ${ blockData.title }` );
		await expect( block ).toBeVisible();
	} );

	test( 'should have settings and styles controls', async ( { editor } ) => {
		const block = editor.canvas.getByLabel( `Block: ${ blockData.title }` );
		await block.click();

		await editor.openDocumentSettingsSidebar();

		await expect(
			editor.page.getByRole( 'button', { name: 'Position' } )
		).toBeVisible();
		await editor.page.getByRole( 'tab', { name: 'Styles' } ).click();
		for ( const panel of [
			'Color',
			'Typography',
			'Dimensions',
			'Border',
		] ) {
			await expect(
				editor.page.getByRole( 'button', { name: panel } )
			).toBeVisible();
		}
		for ( const control of [
			'Label and icon',
			'ButtonLinkFillOutline',
			'Icon size',
		] ) {
			await expect( editor.page.getByText( control ) ).toBeVisible();
		}
	} );
} );
