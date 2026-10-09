/**
 * External dependencies
 */
import { test, expect, BLOCK_THEME_SLUG } from '@woocommerce/e2e-utils';

test.describe( 'registerProductBlockType registers', () => {
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
				postId: `${ BLOCK_THEME_SLUG }//coming-soon`,
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
			await editor.openGlobalBlockInserter();
			await page.getByPlaceholder( 'Search' ).fill( blockTitle );
			const productPriceOption = page
				.getByRole( 'option', { name: blockTitle, exact: true } )
				.first();
			await expect( productPriceOption ).toBeVisible();
			await productPriceOption.click();

			await expect(
				await editor.getBlockByName( blockName )
			).toHaveCount( 1 );
		} );
	} );

	test( 'blocks which are registered via the registerProductBlockType function are visible in the templates data views', async ( {
		admin,
		page,
		wpCoreVersion,
	} ) => {
		const productBlockTypes = [
			'woocommerce/product-price',
			'woocommerce/product-rating',
		];

		await admin.visitAdminPage(
			'site-editor.php?postType=wp_template&activeView=WooCommerce'
		);

		const singleProductTemplate =
			wpCoreVersion >= 6.8
				? page.getByLabel( 'Single Product' )
				: page.getByRole( 'button', { name: 'Single Product' } );

		await expect( singleProductTemplate ).toBeVisible();

		const previewCanvas = singleProductTemplate.frameLocator(
			'iframe[title="Editor canvas"]'
		);

		// Wait for the iframe to be fully loaded.
		await previewCanvas.locator( 'body' ).evaluate( () => {
			return document?.readyState === 'complete';
		} );

		for ( const blockType of productBlockTypes ) {
			const block = previewCanvas.locator(
				`[data-type="${ blockType }"]`
			);
			await expect( block.first() ).toBeAttached();
		}
	} );
} );
