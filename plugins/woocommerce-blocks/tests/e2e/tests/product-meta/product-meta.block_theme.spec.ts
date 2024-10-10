/**
 * External dependencies
 */
import { Locator } from '@playwright/test';
import { expect, test } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */

const blockData = {
	name: 'Product Meta',
	slug: 'woocommerce/product-meta',
};

const selectProductForSingleProductBlock = async (
	singleProductBlock: Locator
) => {
	await singleProductBlock.locator( 'input[type="radio"]' ).nth( 0 ).click();

	await singleProductBlock.getByText( 'Done' ).click();
};

test.describe( `${ blockData.slug } Block`, () => {
	test( 'can be added in the Post Editor only as inner block of the Single Product Block', async ( {
		admin,
		editor,
	} ) => {
		// Add to Cart with Options in the Post Editor is only available as inner block of the Single Product Block.
		await admin.createNewPost();
		await editor.insertBlock( { name: 'woocommerce/single-product' } );

		const singleProductBlock = await editor.getBlockByName(
			'woocommerce/single-product'
		);

		await selectProductForSingleProductBlock( singleProductBlock );

		await expect(
			await editor.getBlockByName( blockData.slug )
		).toBeVisible();

		// When the block is registered as ancestor, the function doesn't throw an error, but the block is not added.
		// So we check that only one instance of the block is present.
		await editor.insertBlock( { name: blockData.slug } );
		await expect(
			await editor.getBlockByName( blockData.slug )
		).toBeVisible();
	} );

	test( 'can be added in the Site Editor only as inner block of the Single Product Block - Product Catalog Template', async ( {
		admin,
		editor,
		requestUtils,
	} ) => {
		// Add to Cart with Options in the Site Editor is only available as
		// inner block of the Single Product Block except for the Single Product
		// Template
		const template = await requestUtils.createTemplate( 'wp_template', {
			slug: 'product-catalog',
			title: 'Custom Product Catalog',
			content: 'placeholder',
		} );

		await admin.visitSiteEditor( {
			postId: template.id,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await expect( editor.canvas.getByText( 'placeholder' ) ).toBeVisible();

		await editor.insertBlock( { name: 'woocommerce/single-product' } );

		const singleProductBlock = await editor.getBlockByName(
			'woocommerce/single-product'
		);

		await selectProductForSingleProductBlock( singleProductBlock );

		await expect(
			await editor.getBlockByName( blockData.slug )
		).toBeVisible();

		// When the block is registered as ancestor, the function doesn't throw an error, but the block is not added.
		// So we check that only one instance of the block is present.
		await editor.insertBlock( { name: blockData.slug } );
		await expect(
			await editor.getBlockByName( blockData.slug )
		).toBeVisible();
	} );

	test( 'can be added in the Post Editor - Single Product Template', async ( {
		admin,
		editor,
		requestUtils,
	} ) => {
		const template = await requestUtils.createTemplate( 'wp_template', {
			slug: 'single-product',
			title: 'Custom Single Product',
			content: 'placeholder',
		} );

		await admin.visitSiteEditor( {
			postId: template.id,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await expect( editor.canvas.getByText( 'placeholder' ) ).toBeVisible();

		await editor.insertBlock( { name: blockData.slug } );

		await expect(
			await editor.getBlockByName( blockData.slug )
		).toBeVisible();
	} );

	test.describe( 'block registration', () => {
		test( 'should be registered on the Single Product template', async ( {
			page,
			editor,
			requestUtils,
			admin,
		} ) => {
			const template = await requestUtils.createTemplate( 'wp_template', {
				slug: 'single-product',
				title: 'Custom Single Product',
				content: 'placeholder',
			} );

			await admin.visitSiteEditor( {
				postId: template.id,
				postType: 'wp_template',
				canvas: 'edit',
			} );
			await editor.openGlobalBlockInserter();
			await page.getByRole( 'tab', { name: 'Blocks' } ).click();
			const blockOption = page
				.getByRole( 'listbox', { name: 'WooCommerce' } )
				.getByRole( 'option', { name: blockData.name } );

			await expect( blockOption ).toBeVisible();
		} );

		test( 'should be unregistered on the Post Editor', async ( {
			admin,
			page,
			editor,
		} ) => {
			await admin.createNewPost();
			await editor.openGlobalBlockInserter();
			const blockOption = page
				.getByRole( 'listbox', { name: 'WooCommerce' } )
				.getByRole( 'option', { name: blockData.name } );

			await expect( blockOption ).toBeHidden();
		} );

		test( 'should be unregistered on the Page Editor', async ( {
			admin,
			page,
			editor,
		} ) => {
			await admin.createNewPost( {
				postType: 'page',
				title: 'New Page',
			} );
			await editor.openGlobalBlockInserter();
			const blockOption = page
				.getByRole( 'listbox', { name: 'WooCommerce' } )
				.getByRole( 'option', { name: blockData.name } );

			await expect( blockOption ).toBeHidden();
		} );
	} );
} );
