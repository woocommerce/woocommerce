/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */
import { addBlock } from './utils';

const blockData = {
	name: 'woocommerce/product-gallery-thumbnails',
	mainClass: '.wp-block-woocommerce-product-gallery-thumbnails',
	selectors: {
		frontend: {},
		editor: {
			thumbnails: '.wp-block-woocommerce-product-gallery-thumbnails',
			noThumbnailsOption: 'button[data-value=off]',
			leftPositionThumbnailsOption: 'button[data-value=left]',
			bottomPositionThumbnailsOption: 'button[data-value=bottom]',
			rightPositionThumbnailsOption: 'button[data-value=right]',
		},
	},
	slug: 'single-product',
	productPage: '/product/v-neck-t-shirt/',
};

test.describe( `${ blockData.name }`, () => {
	test.beforeEach( async ( { requestUtils, admin, editorUtils } ) => {
		await requestUtils.deleteAllTemplates( 'wp_template' );
		await requestUtils.deleteAllTemplates( 'wp_template_part' );
		await admin.visitSiteEditor( {
			postId: `woocommerce/woocommerce//${ blockData.slug }`,
			postType: 'wp_template',
		} );
		await editorUtils.enterEditMode();
	} );

	test( 'Renders Product Gallery Thumbnails block on the editor and frontend side', async ( {
		page,
		editor,
		editorUtils,
		frontendUtils,
	} ) => {
		await editor.insertBlock( {
			name: 'woocommerce/product-gallery',
		} );

		const block = await editorUtils.getBlockByName( blockData.name );

		await expect( block ).toBeVisible();

		await Promise.all( [
			editor.saveSiteEditorEntities(),
			page.waitForResponse( ( response ) =>
				response.url().includes( 'wp-json/wp/v2/templates/' )
			),
		] );

		await page.goto( blockData.productPage, {
			waitUntil: 'networkidle',
		} );

		const blockFrontend = await frontendUtils.getBlockByName(
			'woocommerce/product-gallery'
		);

		await expect( blockFrontend ).toBeVisible();
	} );

	test.describe( `${ blockData.name } Settings`, () => {
		test( 'Hide correctly the thumbnails', async ( {
			page,
			editor,
			editorUtils,
			admin,
		} ) => {
			await addBlock( admin, editor, editorUtils );
			await (
				await editorUtils.getBlockByName( blockData.name )
			 ).click();
			await editor.openDocumentSettingsSidebar();
			await page
				.locator( blockData.selectors.editor.noThumbnailsOption )
				.click();

			const isVisible = await page
				.locator( blockData.selectors.editor.thumbnails )
				.isVisible();

			expect( isVisible ).toBe( false );

			await editor.saveSiteEditorEntities();

			await page.goto( blockData.productPage, {
				waitUntil: 'networkidle',
			} );
		} );

		// We can test the left position of thumbnails by cross-checking:
		// - The Gallery block has the classes "is-layout-flex" and "is-nowrap".
		// - The Thumbnails block has a lower index than the Large Image block.
		test( 'Position thumbnails on the left of the large image', async ( {
			page,
			editor,
			editorUtils,
			frontendUtils,
		} ) => {
			// Currently we are adding the block under the legacy Product Image Gallery block, but in the future we have to add replace the product gallery block with this block.
			const parentBlock = await editorUtils.getBlockByName(
				'woocommerce/product-image-gallery'
			);
			const clientId =
				( await parentBlock.getAttribute( 'data-block' ) ) ?? '';
			const parentClientId =
				( await editorUtils.getBlockRootClientId( clientId ) ) ?? '';

			await editor.selectBlocks( parentBlock );
			await editorUtils.insertBlock(
				{ name: 'woocommerce/product-gallery' },
				undefined,
				parentClientId
			);
			await (
				await editorUtils.getBlockByName( blockData.name )
			 ).click();

			await editor.openDocumentSettingsSidebar();
			await page
				.locator(
					blockData.selectors.editor.leftPositionThumbnailsOption
				)
				.click();

			await page.waitForTimeout( 500 );

			const groupBlock = await editorUtils.getBlockByTypeWithParent(
				'core/group',
				'woocommerce/product-gallery'
			);

			const groupBlockClassAttribute = await groupBlock.getAttribute(
				'class'
			);
			expect( groupBlockClassAttribute ).toContain( 'is-layout-flex' );
			expect( groupBlockClassAttribute ).toContain( 'is-nowrap' );

			const isThumbnailsBlockEarlier =
				await editorUtils.isBlockEarlierThan(
					groupBlock,
					'woocommerce/product-gallery-thumbnails',
					'woocommerce/product-gallery-large-image'
				);

			expect( isThumbnailsBlockEarlier ).toBe( true );

			await Promise.all( [
				editor.saveSiteEditorEntities(),
				page.waitForResponse( ( response ) =>
					response.url().includes( 'wp-json/wp/v2/templates/' )
				),
			] );

			await page.goto( blockData.productPage, {
				waitUntil: 'networkidle',
			} );

			const groupBlockFrontend =
				await frontendUtils.getBlockByClassWithParent(
					'wp-block-group',
					'woocommerce/product-gallery'
				);

			const groupBlockFrontendClassAttribute =
				await groupBlockFrontend.getAttribute( 'class' );
			expect( groupBlockFrontendClassAttribute ).toContain(
				'is-layout-flex'
			);
			expect( groupBlockFrontendClassAttribute ).toContain( 'is-nowrap' );

			const isThumbnailsFrontendBlockEarlier =
				await frontendUtils.isBlockEarlierThan(
					groupBlockFrontend,
					'woocommerce/product-gallery-thumbnails',
					'woocommerce/product-gallery-large-image'
				);

			expect( isThumbnailsFrontendBlockEarlier ).toBe( true );
		} );

		// We can test the bottom position of thumbnails by cross-checking:
		// - The Gallery block has the classes "is-layout-flex" and "is-vertical".
		// - The Thumbnails block has a higher index than the Large Image block.
		test( 'Position thumbnails on the bottom of the large image', async ( {
			page,
			editor,
			editorUtils,
			frontendUtils,
		} ) => {
			// Currently we are adding the block under the legacy Product Image Gallery block, but in the future we have to add replace the product gallery block with this block.
			const parentBlock = await editorUtils.getBlockByName(
				'woocommerce/product-image-gallery'
			);
			const clientId =
				( await parentBlock.getAttribute( 'data-block' ) ) ?? '';
			const parentClientId =
				( await editorUtils.getBlockRootClientId( clientId ) ) ?? '';

			await editor.selectBlocks( parentBlock );
			await editorUtils.insertBlock(
				{ name: 'woocommerce/product-gallery' },
				undefined,
				parentClientId
			);
			await (
				await editorUtils.getBlockByName( blockData.name )
			 ).click();

			await editor.openDocumentSettingsSidebar();
			await page
				.locator(
					blockData.selectors.editor.bottomPositionThumbnailsOption
				)
				.click();

			await page.waitForTimeout( 500 );

			const groupBlock = await editorUtils.getBlockByTypeWithParent(
				'core/group',
				'woocommerce/product-gallery'
			);

			const groupBlockClassAttribute = await groupBlock.getAttribute(
				'class'
			);
			expect( groupBlockClassAttribute ).toContain( 'is-layout-flex' );
			expect( groupBlockClassAttribute ).toContain( 'is-vertical' );

			const isThumbnailsBlockEarlier =
				await editorUtils.isBlockEarlierThan(
					groupBlock,
					'woocommerce/product-gallery-thumbnails',
					'woocommerce/product-gallery-large-image'
				);

			expect( isThumbnailsBlockEarlier ).toBe( false );

			await Promise.all( [
				editor.saveSiteEditorEntities(),
				page.waitForResponse( ( response ) =>
					response.url().includes( 'wp-json/wp/v2/templates/' )
				),
			] );

			await page.goto( blockData.productPage, {
				waitUntil: 'networkidle',
			} );

			const groupBlockFrontend =
				await frontendUtils.getBlockByClassWithParent(
					'wp-block-group',
					'woocommerce/product-gallery'
				);

			const groupBlockFrontendClassAttribute =
				await groupBlockFrontend.getAttribute( 'class' );
			expect( groupBlockFrontendClassAttribute ).toContain(
				'is-layout-flex'
			);
			expect( groupBlockFrontendClassAttribute ).toContain(
				'is-vertical'
			);

			const isThumbnailsFrontendBlockEarlier =
				await frontendUtils.isBlockEarlierThan(
					groupBlockFrontend,
					'woocommerce/product-gallery-thumbnails',
					'woocommerce/product-gallery-large-image'
				);

			expect( isThumbnailsFrontendBlockEarlier ).toBe( false );
		} );

		// We can test the right position of thumbnails by cross-checking:
		// - The Gallery block has the classes "is-layout-flex" and "is-nowrap".
		// - The Thumbnails block has a higher index than the Large Image block.
		test( 'Position thumbnails on the right of the large image', async ( {
			page,
			editor,
			editorUtils,
			frontendUtils,
		} ) => {
			// Currently we are adding the block under the legacy Product Image Gallery block, but in the future we have to add replace the product gallery block with this block.
			const parentBlock = await editorUtils.getBlockByName(
				'woocommerce/product-image-gallery'
			);
			const clientId =
				( await parentBlock.getAttribute( 'data-block' ) ) ?? '';
			const parentClientId =
				( await editorUtils.getBlockRootClientId( clientId ) ) ?? '';

			await editor.selectBlocks( parentBlock );
			await editorUtils.insertBlock(
				{ name: 'woocommerce/product-gallery' },
				undefined,
				parentClientId
			);
			await (
				await editorUtils.getBlockByName( blockData.name )
			 ).click();

			await editor.openDocumentSettingsSidebar();
			await page
				.locator(
					blockData.selectors.editor.rightPositionThumbnailsOption
				)
				.click();

			await page.waitForTimeout( 500 );

			const groupBlock = await editorUtils.getBlockByTypeWithParent(
				'core/group',
				'woocommerce/product-gallery'
			);

			const groupBlockClassAttribute = await groupBlock.getAttribute(
				'class'
			);
			expect( groupBlockClassAttribute ).toContain( 'is-layout-flex' );
			expect( groupBlockClassAttribute ).toContain( 'is-nowrap' );

			const isThumbnailsBlockEarlier =
				await editorUtils.isBlockEarlierThan(
					groupBlock,
					'woocommerce/product-gallery-thumbnails',
					'woocommerce/product-gallery-large-image'
				);

			expect( isThumbnailsBlockEarlier ).toBe( false );

			await Promise.all( [
				editor.saveSiteEditorEntities(),
				page.waitForResponse( ( response ) =>
					response.url().includes( 'wp-json/wp/v2/templates/' )
				),
			] );

			await page.goto( blockData.productPage, {
				waitUntil: 'networkidle',
			} );

			const groupBlockFrontend =
				await frontendUtils.getBlockByClassWithParent(
					'wp-block-group',
					'woocommerce/product-gallery'
				);

			const groupBlockFrontendClassAttribute =
				await groupBlockFrontend.getAttribute( 'class' );
			expect( groupBlockFrontendClassAttribute ).toContain(
				'is-layout-flex'
			);
			expect( groupBlockFrontendClassAttribute ).toContain( 'is-nowrap' );

			const isThumbnailsFrontendBlockEarlier =
				await frontendUtils.isBlockEarlierThan(
					groupBlockFrontend,
					'woocommerce/product-gallery-thumbnails',
					'woocommerce/product-gallery-large-image'
				);

			expect( isThumbnailsFrontendBlockEarlier ).toBe( false );
		} );
	} );
} );
