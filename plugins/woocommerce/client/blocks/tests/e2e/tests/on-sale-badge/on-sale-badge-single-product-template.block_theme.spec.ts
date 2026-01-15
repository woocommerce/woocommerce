/**
 * External dependencies
 */
import {
	test as base,
	expect,
	Editor,
	FrontendUtils,
	BLOCK_THEME_SLUG,
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
				postId: `${ BLOCK_THEME_SLUG }//${ blockData.slug }`,
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

		test( `should not render on the frontend when the product is not on sale`, async ( {
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
				.toEqual( 0 );

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
				.toEqual( 0 );
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
				.toEqual( 0 );

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
				.toEqual( 0 );
		} );
	} );
} );
