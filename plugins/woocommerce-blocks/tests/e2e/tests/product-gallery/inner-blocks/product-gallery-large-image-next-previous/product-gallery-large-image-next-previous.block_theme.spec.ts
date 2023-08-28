/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import { EditorUtils, FrontendUtils } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { addBlock } from './utils';

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
	frontendUtils,
	editorUtils,
	leftArrowSelector,
	rightArrowSelector,
	isFrontend,
}: {
	frontendUtils: FrontendUtils;
	editorUtils: EditorUtils;
	leftArrowSelector: string;
	rightArrowSelector: string;
	isFrontend: boolean;
} ) => {
	const page = isFrontend ? frontendUtils.page : editorUtils.editor.canvas;
	return {
		leftArrow: await page
			.locator( leftArrowSelector )
			.evaluate( ( el ) => el.getBoundingClientRect() ),
		rightArrow: await page
			.locator( rightArrowSelector )
			.evaluate( ( el ) => el.getBoundingClientRect() ),
		gallery: await (
			await ( isFrontend ? frontendUtils : editorUtils ).getBlockByName(
				'woocommerce/product-gallery-large-image'
			)
		 ).evaluate( ( el ) => el.getBoundingClientRect() ),
	};
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

	test.afterEach( async ( { requestUtils } ) => {
		await requestUtils.deleteAllTemplates( 'wp_template' );
		await requestUtils.deleteAllTemplates( 'wp_template_part' );
	} );

	test( 'Renders Next/Previous Button block on the editor side', async ( {
		editorUtils,
		editor,
	} ) => {
		await editor.insertBlock( {
			name: 'woocommerce/product-gallery',
		} );

		const block = await editorUtils.getBlockByName( blockData.name );

		await expect( block ).toBeVisible();
	} );

	test( 'Renders Next/Previous Button block on the frontend side', async ( {
		admin,
		editorUtils,
		frontendUtils,
		editor,
		page,
	} ) => {
		await addBlock( admin, editor, editorUtils );

		await Promise.all( [
			editor.saveSiteEditorEntities(),
			page.waitForResponse( ( response ) =>
				response.url().includes( 'wp-json/wp/v2/templates/' )
			),
		] );

		await page.goto( blockData.productPage, {
			waitUntil: 'commit',
		} );

		const block = await frontendUtils.getBlockByName( blockData.name );

		await expect( block ).toBeVisible();
	} );

	test.describe( `${ blockData.name } Settings`, () => {
		test( 'Hide correctly the arrows', async ( {
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
				.locator( blockData.selectors.editor.noArrowsOption )
				.click();

			const isVisible = await page
				.locator(
					'.wc-block-product-gallery-large-image-next-previous-container'
				)
				.isVisible();

			expect( isVisible ).toBe( false );

			await editor.saveSiteEditorEntities();

			await page.goto( blockData.productPage, {
				waitUntil: 'commit',
			} );

			const leftArrow = await page
				.locator( blockData.selectors.editor.leftArrow.off )
				.isVisible();

			const rightArrow = await page
				.locator( blockData.selectors.editor.rightArrow.off )
				.isVisible();

			expect( leftArrow ).toBe( false );
			expect( rightArrow ).toBe( false );
		} );

		test( 'Show button outside of the image', async ( {
			page,
			editor,
			editorUtils,
			frontendUtils,
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
				await editorUtils.getBlockByName( blockData.name )
			 ).click();

			await editor.openDocumentSettingsSidebar();
			await page
				.locator( blockData.selectors.editor.outsideTheImageOption )
				.click();

			const editorBoundingClientRect = await getBoundingClientRect( {
				frontendUtils,
				editorUtils,
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

			await Promise.all( [
				editor.saveSiteEditorEntities(),
				page.waitForResponse( ( response ) =>
					response.url().includes( 'wp-json/wp/v2/templates/' )
				),
			] );

			await page.goto( blockData.productPage, {
				waitUntil: 'commit',
			} );

			const frontendBoundingClientRect = await getBoundingClientRect( {
				frontendUtils,
				editorUtils,
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

		test( 'Show button inside of the image', async ( {
			page,
			editor,
			editorUtils,
			frontendUtils,
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
				await editorUtils.getBlockByName( blockData.name )
			 ).click();

			await editor.openDocumentSettingsSidebar();
			await page
				.locator( blockData.selectors.editor.insideTheImageOption )
				.click();

			const editorBoundingClientRect = await getBoundingClientRect( {
				frontendUtils,
				editorUtils,
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

			await Promise.all( [
				editor.saveSiteEditorEntities(),
				page.waitForResponse( ( response ) =>
					response.url().includes( 'wp-json/wp/v2/templates/' )
				),
			] );

			await page.goto( blockData.productPage, {
				waitUntil: 'commit',
			} );

			const frontendBoundingClientRect = await getBoundingClientRect( {
				frontendUtils,
				editorUtils,
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

		test( 'Show buttons at the top of the image', async ( {
			page,
			editor,
			editorUtils,
			frontendUtils,
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
				await editorUtils.getBlockByName( blockData.name )
			 ).click();

			await editorUtils.setLayoutOption( 'Align Top' );

			const block = await editorUtils.getBlockByName( blockData.name );

			await expect( block ).toHaveCSS( 'align-items', 'flex-start' );

			await Promise.all( [
				editor.saveSiteEditorEntities(),
				page.waitForResponse( ( response ) =>
					response.url().includes( 'wp-json/wp/v2/templates/' )
				),
			] );

			await page.goto( blockData.productPage, {
				waitUntil: 'commit',
			} );

			const frontendBlock = await frontendUtils.getBlockByName(
				blockData.name
			);

			await expect( frontendBlock ).toHaveCSS(
				'align-items',
				'flex-start'
			);
		} );

		test( 'Show buttons at the middle of the image', async ( {
			page,
			editor,
			editorUtils,
			frontendUtils,
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
				await editorUtils.getBlockByName( blockData.name )
			 ).click();

			await editorUtils.setLayoutOption( 'Align Middle' );

			const block = await editorUtils.getBlockByName( blockData.name );

			await expect( block ).toHaveCSS( 'align-items', 'center' );

			await Promise.all( [
				editor.saveSiteEditorEntities(),
				page.waitForResponse( ( response ) =>
					response.url().includes( 'wp-json/wp/v2/templates/' )
				),
			] );

			await page.goto( blockData.productPage, {
				waitUntil: 'commit',
			} );

			const frontendBlock = await frontendUtils.getBlockByName(
				blockData.name
			);

			await expect( frontendBlock ).toHaveCSS( 'align-items', 'center' );
		} );

		test( 'Show buttons at the bottom of the image by default', async ( {
			page,
			editor,
			editorUtils,
			frontendUtils,
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
				await editorUtils.getBlockByName( blockData.name )
			 ).click();

			const block = await editorUtils.getBlockByName( blockData.name );

			await expect( block ).toHaveCSS( 'align-items', 'flex-end' );

			await Promise.all( [
				editor.saveSiteEditorEntities(),
				page.waitForResponse( ( response ) =>
					response.url().includes( 'wp-json/wp/v2/templates/' )
				),
			] );

			await page.goto( blockData.productPage, {
				waitUntil: 'commit',
			} );

			const frontendBlock = await frontendUtils.getBlockByName(
				blockData.name
			);

			await expect( frontendBlock ).toHaveCSS(
				'align-items',
				'flex-end'
			);
		} );
	} );
} );
