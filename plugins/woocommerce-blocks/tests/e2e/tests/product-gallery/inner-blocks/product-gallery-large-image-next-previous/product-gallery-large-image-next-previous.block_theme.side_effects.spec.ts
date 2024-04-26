/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';
import { WC_TEMPLATES_SLUG } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { addBlock } from './utils';
import { ProductGalleryPage } from '../../product-gallery.page';

const blockData = {
	name: 'woocommerce/product-gallery-large-image-next-previous',
	mainClass:
		'.wp-block-woocommerce-product-gallery-large-image-next-previous',
	selectors: {
		frontend: {},
		editor: {
			leftArrow: {
				off: '.wc-block-product-gallery-large-image-next-previous-left--off',
				insideTheImage:
					'.wc-block-product-gallery-large-image-next-previous-left--inside-image',
				outsideTheImage:
					'.wc-block-product-gallery-large-image-next-previous-left--outside-image',
			},
			rightArrow: {
				off: '.wc-block-product-gallery-large-image-next-previous-right--off',
				insideTheImage:
					'.wc-block-product-gallery-large-image-next-previous-right--inside-image',
				outsideTheImage:
					'.wc-block-product-gallery-large-image-next-previous-right--outside-image',
			},
			noArrowsOption: 'button[data-value=off]',
			outsideTheImageOption: 'button[data-value=outsideTheImage]',
			insideTheImageOption: 'button[data-value=insideTheImage]',
		},
	},
	slug: 'single-product',
	productPage: '/product/logo-collection/',
};

const getBoundingClientRect = async ( {
	leftArrowSelector,
	rightArrowSelector,
	isFrontend,
	pageObject,
}: {
	pageObject: ProductGalleryPage;
	leftArrowSelector: string;
	rightArrowSelector: string;
	isFrontend: boolean;
} ) => {
	const page = isFrontend ? 'frontend' : 'editor';
	return {
		leftArrow: await (
			await pageObject.getNextPreviousButtonsBlock( {
				page,
			} )
		 )
			.locator( leftArrowSelector )
			.evaluate( ( el ) => el.getBoundingClientRect() ),
		rightArrow: await (
			await pageObject.getNextPreviousButtonsBlock( {
				page,
			} )
		 )
			.locator( rightArrowSelector )
			.evaluate( ( el ) => el.getBoundingClientRect() ),
		gallery: await (
			await pageObject.getMainImageBlock( {
				page,
			} )
		 ).evaluate( ( el ) => el.getBoundingClientRect() ),
	};
};

const test = base.extend< { pageObject: ProductGalleryPage } >( {
	pageObject: async ( { page, editor, frontendUtils, editorUtils }, use ) => {
		const pageObject = new ProductGalleryPage( {
			page,
			editor,
			frontendUtils,
			editorUtils,
		} );
		await use( pageObject );
	},
} );

test.describe( `${ blockData.name }`, () => {
	test.beforeEach( async ( { admin, editorUtils } ) => {
		await admin.visitSiteEditor( {
			postId: `${ WC_TEMPLATES_SLUG }//${ blockData.slug }`,
			postType: 'wp_template',
		} );
		await editorUtils.enterEditMode();
	} );

	// eslint-disable-next-line playwright/no-skipped-test
	test.skip( 'Renders Next/Previous Button block on the editor side', async ( {
		editor,
		pageObject,
	} ) => {
		await editor.insertBlock( {
			name: 'woocommerce/product-gallery',
		} );

		const block = await pageObject.getNextPreviousButtonsBlock( {
			page: 'editor',
		} );

		await expect( block ).toBeVisible();
	} );

	// eslint-disable-next-line playwright/no-skipped-test
	test.skip( 'Renders Next/Previous Button block on the frontend side', async ( {
		admin,
		editorUtils,
		editor,
		page,
		pageObject,
	} ) => {
		await addBlock( admin, editor, editorUtils );

		await editor.saveSiteEditorEntities();

		await page.goto( blockData.productPage );

		const block = await pageObject.getNextPreviousButtonsBlock( {
			page: 'frontend',
		} );

		await expect( block ).toBeVisible();
	} );

	test.describe( `${ blockData.name } Settings`, () => {
		// eslint-disable-next-line playwright/no-skipped-test
		test.skip( 'Hide correctly the arrows', async ( {
			page,
			editor,
			editorUtils,
			pageObject,
			admin,
		} ) => {
			await addBlock( admin, editor, editorUtils );
			await (
				await pageObject.getNextPreviousButtonsBlock( {
					page: 'editor',
				} )
			 ).click();
			await editor.openDocumentSettingsSidebar();

			await page
				.locator( blockData.selectors.editor.noArrowsOption )
				.click();

			const container = page.locator(
				'.wc-block-product-gallery-large-image-next-previous-container'
			);

			await expect( container ).toBeHidden();

			await editor.saveSiteEditorEntities();

			await page.goto( blockData.productPage );

			const leftArrow = page.locator(
				blockData.selectors.editor.leftArrow.off
			);

			const rightArrow = page.locator(
				blockData.selectors.editor.rightArrow.off
			);

			await expect( leftArrow ).toBeHidden();
			await expect( rightArrow ).toBeHidden();
		} );

		// eslint-disable-next-line playwright/no-skipped-test
		test.skip( 'Show button outside of the image', async ( {
			page,
			editor,
			editorUtils,
			pageObject,
		} ) => {
			// Currently we are adding the block under the related products block, but in the future we have to add replace the product gallery block with this block.
			const parentBlock = await editorUtils.getBlockByName(
				'woocommerce/product-image-gallery'
			);
			const clientId =
				// eslint-disable-next-line playwright/no-conditional-in-test
				( await parentBlock.getAttribute( 'data-block' ) ) ?? '';
			const parentClientId =
				// eslint-disable-next-line playwright/no-conditional-in-test
				( await editorUtils.getBlockRootClientId( clientId ) ) ?? '';

			await editor.selectBlocks( parentBlock );
			await editorUtils.insertBlock(
				{ name: 'woocommerce/product-gallery' },
				undefined,
				parentClientId
			);
			await (
				await pageObject.getNextPreviousButtonsBlock( {
					page: 'editor',
				} )
			 ).click();

			await editor.openDocumentSettingsSidebar();
			await page
				.locator( blockData.selectors.editor.outsideTheImageOption )
				.click();

			const editorBoundingClientRect = await getBoundingClientRect( {
				pageObject,
				leftArrowSelector:
					blockData.selectors.editor.leftArrow.outsideTheImage,
				rightArrowSelector:
					blockData.selectors.editor.rightArrow.outsideTheImage,
				isFrontend: false,
			} );

			expect( editorBoundingClientRect.leftArrow.left ).toBeLessThan(
				editorBoundingClientRect.gallery.left
			);

			expect( editorBoundingClientRect.rightArrow.right ).toBeGreaterThan(
				editorBoundingClientRect.gallery.right
			);

			await editor.saveSiteEditorEntities();

			await page.goto( blockData.productPage );

			const frontendBoundingClientRect = await getBoundingClientRect( {
				pageObject,
				leftArrowSelector:
					blockData.selectors.editor.leftArrow.outsideTheImage,
				rightArrowSelector:
					blockData.selectors.editor.rightArrow.outsideTheImage,
				isFrontend: true,
			} );

			expect( frontendBoundingClientRect.leftArrow.left ).toBeLessThan(
				frontendBoundingClientRect.gallery.left
			);

			expect(
				frontendBoundingClientRect.rightArrow.right
			).toBeGreaterThan( frontendBoundingClientRect.gallery.right );
		} );

		// eslint-disable-next-line playwright/no-skipped-test
		test.skip( 'Show button inside of the image', async ( {
			page,
			editor,
			editorUtils,
			pageObject,
		} ) => {
			// Currently we are adding the block under the related products block, but in the future we have to add replace the product gallery block with this block.
			const parentBlock = await editorUtils.getBlockByName(
				'woocommerce/product-image-gallery'
			);
			const clientId =
				// eslint-disable-next-line playwright/no-conditional-in-test
				( await parentBlock.getAttribute( 'data-block' ) ) ?? '';
			const parentClientId =
				// eslint-disable-next-line playwright/no-conditional-in-test
				( await editorUtils.getBlockRootClientId( clientId ) ) ?? '';

			await editor.selectBlocks( parentBlock );
			await editorUtils.insertBlock(
				{ name: 'woocommerce/product-gallery' },
				undefined,
				parentClientId
			);
			await (
				await pageObject.getNextPreviousButtonsBlock( {
					page: 'editor',
				} )
			 ).click();

			await editor.openDocumentSettingsSidebar();
			await page
				.locator( blockData.selectors.editor.insideTheImageOption )
				.click();

			const editorBoundingClientRect = await getBoundingClientRect( {
				pageObject,
				leftArrowSelector:
					blockData.selectors.editor.leftArrow.insideTheImage,
				rightArrowSelector:
					blockData.selectors.editor.rightArrow.insideTheImage,
				isFrontend: false,
			} );

			expect( editorBoundingClientRect.leftArrow.left ).toBeGreaterThan(
				editorBoundingClientRect.gallery.left
			);

			expect( editorBoundingClientRect.rightArrow.right ).toBeLessThan(
				editorBoundingClientRect.gallery.right
			);

			await editor.saveSiteEditorEntities();

			await page.goto( blockData.productPage );

			const frontendBoundingClientRect = await getBoundingClientRect( {
				pageObject,
				leftArrowSelector:
					blockData.selectors.editor.leftArrow.insideTheImage,
				rightArrowSelector:
					blockData.selectors.editor.rightArrow.insideTheImage,
				isFrontend: true,
			} );

			expect( frontendBoundingClientRect.leftArrow.left ).toBeGreaterThan(
				frontendBoundingClientRect.gallery.left
			);

			expect( frontendBoundingClientRect.rightArrow.right ).toBeLessThan(
				frontendBoundingClientRect.gallery.right
			);
		} );

		// eslint-disable-next-line playwright/no-skipped-test
		test.skip( 'Show buttons at the top of the image', async ( {
			page,
			editor,
			editorUtils,
			pageObject,
		} ) => {
			// Currently we are adding the block under the related products block, but in the future we have to add replace the product gallery block with this block.
			const parentBlock = await editorUtils.getBlockByName(
				'woocommerce/product-image-gallery'
			);
			const clientId =
				// eslint-disable-next-line playwright/no-conditional-in-test
				( await parentBlock.getAttribute( 'data-block' ) ) ?? '';
			const parentClientId =
				// eslint-disable-next-line playwright/no-conditional-in-test
				( await editorUtils.getBlockRootClientId( clientId ) ) ?? '';

			await editor.selectBlocks( parentBlock );
			await editorUtils.insertBlock(
				{ name: 'woocommerce/product-gallery' },
				undefined,
				parentClientId
			);
			await (
				await pageObject.getNextPreviousButtonsBlock( {
					page: 'editor',
				} )
			 ).click();

			await editorUtils.setLayoutOption( 'Align Top' );

			const block = await pageObject.getNextPreviousButtonsBlock( {
				page: 'editor',
			} );

			await expect( block ).toHaveCSS( 'align-items', 'flex-start' );

			await editor.saveSiteEditorEntities();

			await page.goto( blockData.productPage );

			const frontendBlock = await pageObject.getNextPreviousButtonsBlock(
				{
					page: 'frontend',
				}
			);

			await expect( frontendBlock ).toHaveCSS(
				'align-items',
				'flex-start'
			);
		} );

		// eslint-disable-next-line playwright/no-skipped-test
		test.skip( 'Show buttons at the middle of the image', async ( {
			page,
			editor,
			editorUtils,
			pageObject,
		} ) => {
			// Currently we are adding the block under the related products block, but in the future we have to add replace the product gallery block with this block.
			const parentBlock = await editorUtils.getBlockByName(
				'woocommerce/product-image-gallery'
			);
			const clientId =
				// eslint-disable-next-line playwright/no-conditional-in-test
				( await parentBlock.getAttribute( 'data-block' ) ) ?? '';
			const parentClientId =
				// eslint-disable-next-line playwright/no-conditional-in-test
				( await editorUtils.getBlockRootClientId( clientId ) ) ?? '';

			await editor.selectBlocks( parentBlock );
			await editorUtils.insertBlock(
				{ name: 'woocommerce/product-gallery' },
				undefined,
				parentClientId
			);
			await (
				await await pageObject.getNextPreviousButtonsBlock( {
					page: 'editor',
				} )
			 ).click();

			await editorUtils.setLayoutOption( 'Align Middle' );

			const block = await pageObject.getNextPreviousButtonsBlock( {
				page: 'editor',
			} );

			await expect( block ).toHaveCSS( 'align-items', 'center' );

			await editor.saveSiteEditorEntities();

			await page.goto( blockData.productPage );

			const frontendBlock = await pageObject.getNextPreviousButtonsBlock(
				{
					page: 'frontend',
				}
			);

			await expect( frontendBlock ).toHaveCSS( 'align-items', 'center' );
		} );

		// eslint-disable-next-line playwright/no-skipped-test
		test.skip( 'Show buttons at the bottom of the image by default', async ( {
			page,
			editor,
			editorUtils,
			pageObject,
		} ) => {
			// Currently we are adding the block under the related products block, but in the future we have to add replace the product gallery block with this block.
			const parentBlock = await editorUtils.getBlockByName(
				'woocommerce/product-image-gallery'
			);
			const clientId =
				// eslint-disable-next-line playwright/no-conditional-in-test
				( await parentBlock.getAttribute( 'data-block' ) ) ?? '';
			const parentClientId =
				// eslint-disable-next-line playwright/no-conditional-in-test
				( await editorUtils.getBlockRootClientId( clientId ) ) ?? '';

			await editor.selectBlocks( parentBlock );
			await editorUtils.insertBlock(
				{ name: 'woocommerce/product-gallery' },
				undefined,
				parentClientId
			);
			await (
				await pageObject.getNextPreviousButtonsBlock( {
					page: 'editor',
				} )
			 ).click();

			const block = await pageObject.getNextPreviousButtonsBlock( {
				page: 'editor',
			} );

			await expect( block ).toHaveCSS( 'align-items', 'flex-end' );

			await editor.saveSiteEditorEntities();

			await page.goto( blockData.productPage );

			const frontendBlock = await pageObject.getNextPreviousButtonsBlock(
				{
					page: 'frontend',
				}
			);

			await expect( frontendBlock ).toHaveCSS(
				'align-items',
				'flex-end'
			);
		} );
	} );
} );
