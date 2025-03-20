/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-utils';

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
			leftArrow:
				'.wc-block-product-gallery-large-image-next-previous-left',
			rightArrow:
				'.wc-block-product-gallery-large-image-next-previous-right',
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
	test.beforeEach( async ( { admin } ) => {
		await admin.visitSiteEditor( {
			postId: `woocommerce/woocommerce//${ blockData.slug }`,
			postType: 'wp_template',
			canvas: 'edit',
		} );
	} );

	test( 'Renders Next/Previous Button block on the editor side', async ( {
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

	test( 'Renders Next/Previous Button block on the frontend side', async ( {
		admin,
		editor,
		page,
		pageObject,
	} ) => {
		await addBlock( admin, editor );

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( blockData.productPage );

		const block = await pageObject.getNextPreviousButtonsBlock( {
			page: 'frontend',
		} );

		await expect( block ).toBeVisible();
	} );

	test.describe( `${ blockData.name } Settings`, () => {
		test( 'Show button inside of the image', async ( {
			page,
			editor,
			pageObject,
		} ) => {
			// Currently we are adding the block under the related products block, but in the future we have to add replace the product gallery block with this block.
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

			const editorBoundingClientRect = await getBoundingClientRect( {
				pageObject,
				leftArrowSelector: blockData.selectors.editor.leftArrow,
				rightArrowSelector: blockData.selectors.editor.rightArrow,
				isFrontend: false,
			} );

			expect( editorBoundingClientRect.leftArrow.left ).toBeGreaterThan(
				editorBoundingClientRect.gallery.left
			);

			expect( editorBoundingClientRect.rightArrow.right ).toBeLessThan(
				editorBoundingClientRect.gallery.right
			);

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( blockData.productPage );

			const frontendBoundingClientRect = await getBoundingClientRect( {
				pageObject,
				leftArrowSelector: blockData.selectors.editor.leftArrow,
				rightArrowSelector: blockData.selectors.editor.rightArrow,
				isFrontend: true,
			} );

			expect( frontendBoundingClientRect.leftArrow.left ).toBeGreaterThan(
				frontendBoundingClientRect.gallery.left
			);

			expect( frontendBoundingClientRect.rightArrow.right ).toBeLessThan(
				frontendBoundingClientRect.gallery.right
			);
		} );

		test( 'Show buttons at the bottom of the image by default', async ( {
			page,
			editor,
			pageObject,
		} ) => {
			// Currently we are adding the block under the related products block, but in the future we have to add replace the product gallery block with this block.
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

			const block = await pageObject.getNextPreviousButtonsBlock( {
				page: 'editor',
			} );

			await expect( block ).toHaveCSS( 'align-items', 'flex-end' );

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

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
