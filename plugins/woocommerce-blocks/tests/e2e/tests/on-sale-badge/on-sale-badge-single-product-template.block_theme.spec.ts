/**
 * External dependencies
 */
import {
	test as base,
	expect,
	Editor,
	FrontendUtils,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { ProductGalleryPage } from '../product-gallery/product-gallery.page';

const blockData = {
	name: 'woocommerce/product-sale-badge',
	mainClass: '.wp-block-woocommerce-product-sale-badge',
	selectors: {
		frontend: {
			badge: '.wc-block-components-product-sale-badge',
			badgeContainer: '.wp-block-woocommerce-product-sale-badge',
		},
		editor: {
			badge: '.wc-block-components-product-sale-badge',
			badgeContainer: '.wp-block-woocommerce-product-sale-badge',
		},
	},
	// This margin is applied via Block Styles to the product sale badge. It's necessary to take it into account when calculating the position of the badge. https://github.com/woocommerce/woocommerce-blocks/blob/445b9431ccba460f9badd41d52ed991958524e33/assets/js/blocks/product-gallery/edit.tsx/#L44-L53
	margin: 4,
	slug: 'single-product',
	productPage: '/product/hoodie/',
	productPageNotOnSale: '/product/album/',
};

class BlockUtils {
	editor: Editor;
	frontendUtils: FrontendUtils;

	constructor( {
		editor,
		frontendUtils,
	}: {
		editor: Editor;
		frontendUtils: FrontendUtils;
	} ) {
		this.editor = editor;
		this.frontendUtils = frontendUtils;
	}

	async getSaleBadgeBoundingClientRect( isFrontend: boolean ): Promise< {
		badge: DOMRect;
		badgeContainer: DOMRect;
	} > {
		const page = isFrontend ? this.frontendUtils.page : this.editor.canvas;
		return {
			badge: await page
				.locator(
					blockData.selectors[ isFrontend ? 'frontend' : 'editor' ]
						.badge
				)
				.first()
				.evaluate( ( el ) => el.getBoundingClientRect() ),
			badgeContainer: await page
				.locator(
					blockData.selectors[ isFrontend ? 'frontend' : 'editor' ]
						.badgeContainer
				)
				.first()
				.evaluate( ( el ) => el.getBoundingClientRect() ),
		};
	}
}

const test = base.extend< {
	pageObject: ProductGalleryPage;
	blockUtils: BlockUtils;
} >( {
	pageObject: async ( { page, editor, frontendUtils }, use ) => {
		await use(
			new ProductGalleryPage( {
				page,
				editor,
				frontendUtils,
			} )
		);
	},
	blockUtils: async ( { editor, frontendUtils }, use ) => {
		await use( new BlockUtils( { editor, frontendUtils } ) );
	},
} );

test.describe( `${ blockData.name }`, () => {
	test.describe( `On the Single Product Template`, () => {
		test.beforeEach( async ( { admin, editor } ) => {
			await admin.visitSiteEditor( {
				postId: `woocommerce/woocommerce//${ blockData.slug }`,
				postType: 'wp_template',
				canvas: 'edit',
			} );
			await editor.setContent( '' );
		} );

		test( 'should be rendered on the editor side', async ( { editor } ) => {
			await editor.insertBlock( {
				name: 'woocommerce/product-gallery',
			} );

			const block = await editor.getBlockByName( blockData.name );

			await expect( block ).toBeVisible();
		} );

		test( 'should be rendered on the frontend side', async ( {
			frontendUtils,
			editor,
			page,
			pageObject,
		} ) => {
			await editor.openDocumentSettingsSidebar();
			await editor.insertBlock( {
				name: 'woocommerce/product-gallery',
			} );

			await pageObject.toggleFullScreenOnClickSetting( false );

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( blockData.productPage );

			const block = await frontendUtils.getBlockByName( blockData.name );

			await expect( block.first() ).toBeVisible();
		} );

		test( `should be not rendered when the product isn't on sale the frontend side`, async ( {
			frontendUtils,
			editor,
			page,
			pageObject,
		} ) => {
			await editor.openDocumentSettingsSidebar();
			await editor.insertBlock( {
				name: 'woocommerce/product-gallery',
			} );

			await pageObject.toggleFullScreenOnClickSetting( false );

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( blockData.productPageNotOnSale );

			const block = await frontendUtils.getBlockByName( blockData.name );

			await expect( block ).toBeHidden();
		} );

		test( 'should be aligned to the left', async ( {
			editor,
			page,
			pageObject,
			blockUtils,
		} ) => {
			await editor.openDocumentSettingsSidebar();
			await editor.insertBlock( {
				name: 'woocommerce/product-gallery',
			} );

			await pageObject.toggleFullScreenOnClickSetting( false );

			const block = await editor.getBlockByName( blockData.name );

			await block.click();

			await page.locator( "button[aria-label='Align']" ).click();
			await page.getByText( 'Align Left' ).click();

			await expect
				.poll( async () => {
					const { badge, badgeContainer } =
						await blockUtils.getSaleBadgeBoundingClientRect(
							false
						);

					return badge.x - badgeContainer.x;
				} )
				.toEqual( blockData.margin );

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( blockData.productPage );

			await expect
				.poll( async () => {
					const { badge, badgeContainer } =
						await blockUtils.getSaleBadgeBoundingClientRect( true );

					return badge.x - badgeContainer.x;
				} )
				.toEqual( blockData.margin );
		} );

		test( 'should be aligned to the center', async ( {
			editor,
			page,
			pageObject,
			blockUtils,
		} ) => {
			await editor.openDocumentSettingsSidebar();
			await editor.insertBlock( {
				name: 'woocommerce/product-gallery',
			} );

			await pageObject.toggleFullScreenOnClickSetting( false );

			const block = await editor.getBlockByName( blockData.name );

			await block.click();

			await page.locator( "button[aria-label='Align']" ).click();
			await page.getByText( 'Align Center' ).click();

			await expect
				.poll( async () => {
					const { badge, badgeContainer } =
						await blockUtils.getSaleBadgeBoundingClientRect(
							false
						);

					return badge.right < badgeContainer.right;
				} )
				.toBe( true );

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( blockData.productPage );

			await expect
				.poll( async () => {
					const { badge, badgeContainer } =
						await blockUtils.getSaleBadgeBoundingClientRect( true );

					return badge.right < badgeContainer.right;
				} )
				.toBe( true );
		} );

		test( 'should be aligned to the right by default', async ( {
			editor,
			page,
			pageObject,
			blockUtils,
		} ) => {
			await editor.openDocumentSettingsSidebar();
			await editor.insertBlock( {
				name: 'woocommerce/product-gallery',
			} );
			await pageObject.toggleFullScreenOnClickSetting( false );

			await expect
				.poll( async () => {
					const { badge, badgeContainer } =
						await blockUtils.getSaleBadgeBoundingClientRect(
							false
						);

					return badgeContainer.right - badge.right;
				} )
				.toEqual( blockData.margin );

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( blockData.productPage );

			await expect
				.poll( async () => {
					const { badge, badgeContainer } =
						await blockUtils.getSaleBadgeBoundingClientRect( true );

					return badgeContainer.right - badge.right;
				} )
				.toEqual( blockData.margin );
		} );
	} );
} );
