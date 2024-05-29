/**
 * External dependencies
 */
import { Locator, Page } from '@playwright/test';
import { test as base, expect } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { ProductGalleryPage } from '../../product-gallery.page';

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

const changeNumberOfThumbnailsInputValue = async (
	page: Page,
	numberOfThumbnailInput: Locator,
	value: string
) => {
	await numberOfThumbnailInput.fill( value );
	await page.keyboard.press( 'Enter' );
};

const test = base.extend< { pageObject: ProductGalleryPage } >( {
	pageObject: async ( { page, editor, frontendUtils }, use ) => {
		const pageObject = new ProductGalleryPage( {
			page,
			editor,
			frontendUtils,
		} );
		await use( pageObject );
	},
} );
test.describe( `${ blockData.name }`, () => {
	test.beforeEach( async ( { admin, editor } ) => {
		await admin.visitSiteEditor( {
			postId: `woocommerce/woocommerce//${ blockData.slug }`,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await editor.canvas
			.locator( '[data-testid="product-image"]' )
			.first()
			.waitFor();
	} );

	test( 'Renders Product Gallery Thumbnails block on the editor and frontend side', async ( {
		page,
		editor,
		pageObject,
		frontendUtils,
	} ) => {
		await editor.insertBlock( {
			name: 'woocommerce/product-gallery',
		} );

		const thumbnailsBlock = await pageObject.getThumbnailsBlock( {
			page: 'editor',
		} );

		await expect( thumbnailsBlock ).toBeVisible();

		// Test the default (left) position of thumbnails by cross-checking:
		// - The Gallery block has the classes "is-layout-flex" and "is-nowrap".
		// - The Thumbnails block has a lower index than the Large Image block.
		const groupBlock = editor.canvas
			.locator( '[data-type="woocommerce/product-gallery"]' )
			.locator( '[data-type="core/group"]' )
			.first();

		const groupBlockClassAttribute = await groupBlock.getAttribute(
			'class'
		);
		expect( groupBlockClassAttribute ).toContain( 'is-layout-flex' );
		expect( groupBlockClassAttribute ).toContain( 'is-nowrap' );

		const isThumbnailsBlockEarlier = await editor.isBlockEarlierThan(
			groupBlock,
			'woocommerce/product-gallery-thumbnails',
			'core/group'
		);

		expect( isThumbnailsBlockEarlier ).toBe( true );

		await editor.saveSiteEditorEntities();

		await page.goto( blockData.productPage );

		const groupBlockFrontend = (
			await frontendUtils.getBlockByClassWithParent(
				'wp-block-group',
				'woocommerce/product-gallery'
			)
		 ).first();

		const groupBlockFrontendClassAttribute =
			await groupBlockFrontend.getAttribute( 'class' );
		expect( groupBlockFrontendClassAttribute ).toContain(
			'is-layout-flex'
		);
		expect( groupBlockFrontendClassAttribute ).toContain( 'is-nowrap' );

		const isThumbnailsFrontendBlockEarlier =
			await frontendUtils.isBlockEarlierThanGroupBlock(
				groupBlockFrontend,
				'woocommerce/product-gallery-thumbnails'
			);

		expect( isThumbnailsFrontendBlockEarlier ).toBe( true );
	} );

	test.describe( `${ blockData.name } Settings`, () => {
		test( 'Hide correctly the thumbnails', async ( { page, editor } ) => {
			await editor.insertBlock( {
				name: 'woocommerce/product-gallery',
			} );

			await editor.canvas
				.locator( `[data-type="${ blockData.name }"]` )
				.click();

			await editor.openDocumentSettingsSidebar();

			await page
				.locator( blockData.selectors.editor.noThumbnailsOption )
				.click();

			await expect(
				page.locator( blockData.selectors.editor.thumbnails )
			).toBeHidden();

			await editor.saveSiteEditorEntities();
		} );

		// We can test the left position of thumbnails by cross-checking:
		// - The Gallery block has the classes "is-layout-flex" and "is-nowrap".
		// - The Thumbnails block has a lower index than the Large Image block.
		test( 'Position thumbnails on the left of the large image', async ( {
			page,
			editor,
			frontendUtils,
		} ) => {
			// Currently we are adding the block under the legacy Product Image Gallery block, but in the future we have to add replace the product gallery block with this block.
			const parentBlock = await editor.getBlockByName(
				'woocommerce/product-image-gallery'
			);
			const clientId =
				( await parentBlock.getAttribute( 'data-block' ) ) ?? '';
			const parentClientId =
				( await editor.getBlockRootClientId( clientId ) ) ?? '';

			await editor.selectBlocks( parentBlock );
			await editor.insertBlock(
				{ name: 'woocommerce/product-gallery' },
				{ clientId: parentClientId }
			);
			await ( await editor.getBlockByName( blockData.name ) ).click();

			await editor.openDocumentSettingsSidebar();
			await page
				.locator(
					blockData.selectors.editor.leftPositionThumbnailsOption
				)
				.click();

			const groupBlock = editor.canvas
				.locator( '[data-type="woocommerce/product-gallery"]' )
				.locator( '[data-type="core/group"]' )
				.first();

			const groupBlockClassAttribute = await groupBlock.getAttribute(
				'class'
			);
			expect( groupBlockClassAttribute ).toContain( 'is-layout-flex' );
			expect( groupBlockClassAttribute ).toContain( 'is-nowrap' );

			const isThumbnailsBlockEarlier = await editor.isBlockEarlierThan(
				groupBlock,
				'woocommerce/product-gallery-thumbnails',
				'core/group'
			);

			expect( isThumbnailsBlockEarlier ).toBe( true );

			await editor.saveSiteEditorEntities();

			await page.goto( blockData.productPage );

			const groupBlockFrontend = (
				await frontendUtils.getBlockByClassWithParent(
					'wp-block-group',
					'woocommerce/product-gallery'
				)
			 ).first();

			const groupBlockFrontendClassAttribute =
				await groupBlockFrontend.getAttribute( 'class' );
			expect( groupBlockFrontendClassAttribute ).toContain(
				'is-layout-flex'
			);
			expect( groupBlockFrontendClassAttribute ).toContain( 'is-nowrap' );

			const isThumbnailsFrontendBlockEarlier =
				await frontendUtils.isBlockEarlierThanGroupBlock(
					groupBlockFrontend,
					'woocommerce/product-gallery-thumbnails'
				);

			expect( isThumbnailsFrontendBlockEarlier ).toBe( true );
		} );

		// We can test the bottom position of thumbnails by cross-checking:
		// - The Gallery block has the classes "is-layout-flex" and "is-vertical".
		// - The Thumbnails block has a higher index than the Large Image block.
		test( 'Position thumbnails on the bottom of the large image', async ( {
			page,
			editor,
			frontendUtils,
		} ) => {
			// Currently we are adding the block under the legacy Product Image Gallery block, but in the future we have to add replace the product gallery block with this block.
			const parentBlock = await editor.getBlockByName(
				'woocommerce/product-image-gallery'
			);
			const clientId =
				( await parentBlock.getAttribute( 'data-block' ) ) ?? '';
			const parentClientId =
				( await editor.getBlockRootClientId( clientId ) ) ?? '';

			await editor.selectBlocks( parentBlock );
			await editor.insertBlock(
				{ name: 'woocommerce/product-gallery' },
				{ clientId: parentClientId }
			);
			await ( await editor.getBlockByName( blockData.name ) ).click();

			await editor.openDocumentSettingsSidebar();
			await page
				.locator(
					blockData.selectors.editor.bottomPositionThumbnailsOption
				)
				.click();

			const groupBlock = editor.canvas
				.locator( '[data-type="woocommerce/product-gallery"]' )
				.locator( '[data-type="core/group"]' )
				.first();

			const groupBlockClassAttribute = await groupBlock.getAttribute(
				'class'
			);
			expect( groupBlockClassAttribute ).toContain( 'is-layout-flex' );
			expect( groupBlockClassAttribute ).toContain( 'is-vertical' );

			const isThumbnailsBlockEarlier = await editor.isBlockEarlierThan(
				groupBlock,
				'woocommerce/product-gallery-thumbnails',
				'core/group'
			);

			expect( isThumbnailsBlockEarlier ).toBe( false );

			await editor.saveSiteEditorEntities();
			await page.goto( blockData.productPage );

			const groupBlockFrontend = (
				await frontendUtils.getBlockByClassWithParent(
					'wp-block-group',
					'woocommerce/product-gallery'
				)
			 ).first();

			const groupBlockFrontendClassAttribute =
				await groupBlockFrontend.getAttribute( 'class' );
			expect( groupBlockFrontendClassAttribute ).toContain(
				'is-layout-flex'
			);
			expect( groupBlockFrontendClassAttribute ).toContain(
				'is-vertical'
			);

			const isThumbnailsFrontendBlockEarlier =
				await frontendUtils.isBlockEarlierThanGroupBlock(
					groupBlockFrontend,
					'woocommerce/product-gallery-thumbnails'
				);

			expect( isThumbnailsFrontendBlockEarlier ).toBe( false );
		} );

		// We can test the right position of thumbnails by cross-checking:
		// - The Gallery block has the classes "is-layout-flex" and "is-nowrap".
		// - The Thumbnails block has a higher index than the Large Image block.
		test( 'Position thumbnails on the right of the large image', async ( {
			page,
			editor,
			frontendUtils,
		} ) => {
			// Currently we are adding the block under the legacy Product Image Gallery block, but in the future we have to add replace the product gallery block with this block.
			const parentBlock = await editor.getBlockByName(
				'woocommerce/product-image-gallery'
			);
			const clientId =
				( await parentBlock.getAttribute( 'data-block' ) ) ?? '';
			const parentClientId =
				( await editor.getBlockRootClientId( clientId ) ) ?? '';

			await editor.selectBlocks( parentBlock );
			await editor.insertBlock(
				{ name: 'woocommerce/product-gallery' },
				{ clientId: parentClientId }
			);
			await ( await editor.getBlockByName( blockData.name ) ).click();

			await editor.openDocumentSettingsSidebar();
			await page
				.locator(
					blockData.selectors.editor.rightPositionThumbnailsOption
				)
				.click();

			const groupBlock = editor.canvas
				.locator( '[data-type="woocommerce/product-gallery"]' )
				.locator( '[data-type="core/group"]' )
				.first();

			const groupBlockClassAttribute = await groupBlock.getAttribute(
				'class'
			);
			expect( groupBlockClassAttribute ).toContain( 'is-layout-flex' );
			expect( groupBlockClassAttribute ).toContain( 'is-nowrap' );

			const isThumbnailsBlockEarlier = await editor.isBlockEarlierThan(
				groupBlock,
				'woocommerce/product-gallery-thumbnails',
				'core/group'
			);

			expect( isThumbnailsBlockEarlier ).toBe( false );

			await editor.saveSiteEditorEntities();
			await page.goto( blockData.productPage );

			const groupBlockFrontend = (
				await frontendUtils.getBlockByClassWithParent(
					'wp-block-group',
					'woocommerce/product-gallery'
				)
			 ).first();

			const groupBlockFrontendClassAttribute =
				await groupBlockFrontend.getAttribute( 'class' );
			expect( groupBlockFrontendClassAttribute ).toContain(
				'is-layout-flex'
			);
			expect( groupBlockFrontendClassAttribute ).toContain( 'is-nowrap' );

			const isThumbnailsFrontendBlockEarlier =
				await frontendUtils.isBlockEarlierThanGroupBlock(
					groupBlockFrontend,
					'woocommerce/product-gallery-thumbnails'
				);

			expect( isThumbnailsFrontendBlockEarlier ).toBe( false );
		} );

		test( 'Ensure entered Number of Thumbnails rounds to integer', async ( {
			page,
			editor,
		} ) => {
			await editor.insertBlock( {
				name: 'woocommerce/product-gallery',
			} );

			await editor.selectBlocks(
				'[data-type="woocommerce/product-gallery"]'
			);

			await editor.openDocumentSettingsSidebar();

			const numberOfThumbnailInput = page.getByRole( 'spinbutton', {
				name: 'Number of Thumbnails',
			} );

			await changeNumberOfThumbnailsInputValue(
				page,
				numberOfThumbnailInput,
				'4.2'
			);

			const numberOfThumbnailsOnScreen = editor.canvas.locator(
				'.wc-block-product-gallery-thumbnails__thumbnail'
			);

			await expect( numberOfThumbnailsOnScreen ).toHaveCount( 4 );

			await changeNumberOfThumbnailsInputValue(
				page,
				numberOfThumbnailInput,
				'4.7'
			);

			await expect( numberOfThumbnailsOnScreen ).toHaveCount( 5 );
		} );
	} );
} );
