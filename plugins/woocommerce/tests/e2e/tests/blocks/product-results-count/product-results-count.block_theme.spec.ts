/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

const blockData = {
	name: 'Product Results Count',
	slug: 'woocommerce/product-results-count',
	class: '.wc-block-product-results-count',
};

test.describe( `${ blockData.slug } Block`, () => {
	test( 'block can be inserted in the Site Editor', async ( {
		admin,
		requestUtils,
		editor,
	} ) => {
		const template = await requestUtils.createTemplate( 'wp_template', {
			slug: 'sorter',
			title: 'Sorter',
			content: 'placeholder',
		} );

		await admin.visitSiteEditor( {
			postId: template.id,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await expect(
			editor.getCustomHtmlBlockContentLocator( 'placeholder' )
		).toBeVisible();
		await editor.insertBlock( {
			name: blockData.slug,
		} );

		const block = await editor.getBlockByName( blockData.slug );

		await expect( block ).toHaveText( 'Showing 1-X of X results' );
	} );

	test( 'announces the new count to screen readers after sorting', async ( {
		page,
	} ) => {
		await page.goto( '/shop/' );

		await page
			.getByRole( 'combobox', { name: 'Shop order' } )
			.selectOption( 'price' );

		await expect( page ).toHaveURL( /orderby=price/ );
		await expect( page.locator( '#a11y-speak-polite' ) ).toHaveText(
			/^Showing .+ results Sorted by price: low to high/
		);
	} );
} );
