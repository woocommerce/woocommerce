/**
 * External dependencies
 */
import { test, expect, Editor } from '@woocommerce/e2e-utils';

const insertSingleProductBlock = async (
	blockName: string,
	editor: Editor
) => {
	await editor.insertBlockUsingGlobalInserter( 'Single Product' );
	await editor.canvas.getByText( 'Album' ).click();
	await editor.canvas.getByText( 'Done' ).click();
	const singleProductBlock = await editor.getBlockByName(
		'woocommerce/single-product'
	);
	const singleProductClientId =
		( await singleProductBlock.getAttribute( 'data-block' ) ) ?? '';
	return singleProductClientId;
};

const insertInSingleProductTemplate = async (
	blockName: string,
	editor: Editor,
	admin: Admin
) => {
	await admin.visitSiteEditor( {
		postId: `woocommerce/woocommerce//single-product`,
		postType: 'wp_template',
		canvas: 'edit',
	} );
	await editor.setContent( '' );
	await editor.insertBlock( { name: blockName } );
};

test.describe( 'registerProductBlockType registers', () => {
	test( 'block available on posts, e.g. Product Price', async ( {
		admin,
		editor,
	} ) => {
		const blockName = 'woocommerce/product-price';

		await test.step( 'Unavailable in post globally', async () => {
			await admin.createNewPost();
			await editor.insertBlock( { name: blockName } );
			await expect(
				await editor.getBlockByName( blockName )
			).toHaveCount( 0 );
		} );

		await test.step( 'Available in post within Single Product block', async () => {
			const singleProductClientId = await insertSingleProductBlock(
				blockName,
				editor
			);
			// One from the global inserter, one from the single product block
			await editor.insertBlock(
				{ name: blockName },
				{ clientId: singleProductClientId }
			);
			await expect(
				await editor.getBlockByName( blockName )
			).toHaveCount( 2 );
		} );

		await test.step( 'Available in Single Product template globally', async () => {
			await insertInSingleProductTemplate( blockName, editor, admin );
			await expect(
				await editor.getBlockByName( blockName )
			).toHaveCount( 1 );
		} );
	} );

	test( 'blocks are registered correctly when switching templates via command palette', async ( {
		admin,
		editor,
		page,
	} ) => {
		const blockName = 'woocommerce/product-price';
		const blockTitle = 'Product Price';
		await test.step( 'Blocks not available in non-product template', async () => {
			// Visit site editor with a non-product template
			await admin.visitSiteEditor( {
				postId: 'woocommerce/woocommerce//coming-soon',
				postType: 'wp_template',
				canvas: 'edit',
			} );

			// Try to insert the block
			await editor.insertBlock( { name: blockName } );
			await expect(
				await editor.getBlockByName( blockName )
			).toHaveCount( 0 );
		} );

		await test.step( 'Switch to Single Product template via command palette', async () => {
			// Open command palette
			if ( process.platform === 'darwin' ) {
				await page.keyboard.press( 'Meta+K' );
			} else {
				await page.keyboard.press( 'Control+K' );
			}

			const searchInput = page.getByRole( 'combobox', {
				name: 'Search',
			} );
			await expect( searchInput ).toBeVisible();

			await searchInput.fill( 'Single Product' );
			const templateOption = page.getByRole( 'option', {
				name: /Single Product/i,
			} );
			await expect( templateOption ).toBeVisible();
			await templateOption.click();

			await expect(
				await editor.getBlockByName( 'core/post-title' )
			).toBeVisible();
		} );

		await test.step( 'Blocks available after switching to Single Product template', async () => {
			await editor.setContent( '' );

			// Product Price is available in the global inserter. For some reason, using await editor.insertBlock( { name: blockName } ); does not work here.
			await editor.insertBlockUsingGlobalInserter( blockTitle );

			await expect(
				await editor.getBlockByName( blockName )
			).toHaveCount( 1 );
		} );
	} );

	test( 'block unavailable on posts, e.g. Product Details', async ( {
		admin,
		editor,
	} ) => {
		const blockName = 'woocommerce/product-details';

		await test.step( 'Unavailable in post, also within Single Product block', async () => {
			await admin.createNewPost();
			const singleProductClientId = await insertSingleProductBlock(
				blockName,
				editor
			);
			await expect(
				editor.insertBlock(
					{ name: blockName },
					{ clientId: singleProductClientId }
				)
			).rejects.toThrow(
				new RegExp( `Block type '${ blockName }' is not registered.` )
			);
		} );

		await test.step( 'Available in Single Product template globally', async () => {
			await insertInSingleProductTemplate( blockName, editor, admin );
			await expect(
				await editor.getBlockByName( blockName )
			).toHaveCount( 1 );
		} );
	} );
} );
