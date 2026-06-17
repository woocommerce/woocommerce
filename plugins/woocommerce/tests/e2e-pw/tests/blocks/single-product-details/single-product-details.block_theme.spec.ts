/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */

const blockData = {
	name: 'Product Details',
	slug: 'woocommerce/product-details',
};

test.describe( `${ blockData.slug } Block`, () => {
	test( "block can't be inserted in Post Editor", async ( {
		editor,
		admin,
	} ) => {
		await admin.createNewPost();

		try {
			await editor.insertBlock( { name: blockData.slug } );
		} catch ( _error ) {
			// noop
		}

		await expect(
			await editor.getBlockByName( blockData.slug )
		).toBeHidden();
	} );

	test( 'block can be inserted in the Site Editor', async ( {
		admin,
		requestUtils,
		editor,
		wpCoreVersion,
	} ) => {
		const template = await requestUtils.createTemplate( 'wp_template', {
			// Single Product Details block is addable only in Single Product Templates
			slug: 'single-product-v-neck-t-shirt',
			title: 'Sorter',
			content: 'placeholder',
		} );

		await admin.visitSiteEditor( {
			postId: template.id,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		// TODO: WP 7.0 compat - Custom HTML block content is inside an iframe
		// since WP 7.0. Simplify when WP 7.0 is the minimum supported version.
		const placeholderLocator =
			wpCoreVersion >= 7
				? editor.canvas
						.frameLocator( 'iframe' )
						.getByText( 'placeholder' )
				: editor.canvas.getByText( 'placeholder' );
		await expect( placeholderLocator ).toBeVisible();

		await editor.insertBlock( {
			name: blockData.slug,
		} );

		const block = await editor.getBlockByName( blockData.slug );

		await expect( block ).toHaveText(
			/This block displays the product description. When viewing a product page, the description content will automatically appear here./
		);
	} );
} );
