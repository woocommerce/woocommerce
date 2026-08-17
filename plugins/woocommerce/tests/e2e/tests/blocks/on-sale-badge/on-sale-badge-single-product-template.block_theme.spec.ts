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
};

type SaleBadgeAlignment = 'left' | 'center' | 'right';

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

	async getAlignmentDelta(
		alignment: SaleBadgeAlignment,
		isFrontend: boolean
	): Promise< number > {
		const { badge, badgeContainer } =
			await this.getSaleBadgeBoundingClientRect( isFrontend );

		if ( alignment === 'left' ) {
			return Math.abs( badge.left - badgeContainer.left );
		}

		if ( alignment === 'center' ) {
			const badgeMidpoint = ( badge.left + badge.right ) / 2;
			const containerMidpoint =
				( badgeContainer.left + badgeContainer.right ) / 2;
			return Math.abs( badgeMidpoint - containerMidpoint );
		}

		return Math.abs( badgeContainer.right - badge.right );
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

		test( 'renders and aligns the sale badge in editor and frontend', async ( {
			admin,
			editor,
			frontendUtils,
			page,
			pageObject,
			blockUtils,
		} ) => {
			try {
				await editor.openDocumentSettingsSidebar();
				await editor.insertBlock( {
					name: 'woocommerce/product-gallery',
				} );
				await pageObject.toggleFullScreenOnClickSetting( false );

				let block = await editor.getBlockByName( blockData.name );
				await expect( block ).toBeVisible();
				await expect
					.poll( () =>
						blockUtils.getAlignmentDelta( 'right', false )
					)
					.toBeLessThanOrEqual( 1 );

				await editor.saveSiteEditorEntities( {
					isOnlyCurrentEntityDirty: true,
				} );

				await page.goto( blockData.productPage );

				block = await frontendUtils.getBlockByName( blockData.name );
				await expect( block.first() ).toBeVisible();
				await expect
					.poll( () => blockUtils.getAlignmentDelta( 'right', true ) )
					.toBeLessThanOrEqual( 1 );

				for ( const alignment of [ 'left', 'center' ] as const ) {
					await admin.visitSiteEditor( {
						postId: `${ BLOCK_THEME_SLUG }//${ blockData.slug }`,
						postType: 'wp_template',
						canvas: 'edit',
					} );

					block = await editor.getBlockByName( blockData.name );
					await block.click();
					await page.getByRole( 'button', { name: 'Align' } ).click();
					await page
						.getByRole( 'menuitemradio', {
							name: new RegExp( `Align ${ alignment }`, 'i' ),
						} )
						.click();

					await expect
						.poll( () =>
							blockUtils.getAlignmentDelta( alignment, false )
						)
						.toBeLessThanOrEqual( 1 );

					await editor.saveSiteEditorEntities( {
						isOnlyCurrentEntityDirty: true,
					} );
					await page.goto( blockData.productPage );

					block = await frontendUtils.getBlockByName(
						blockData.name
					);
					await expect( block.first() ).toBeVisible();
					await expect
						.poll( () =>
							blockUtils.getAlignmentDelta( alignment, true )
						)
						.toBeLessThanOrEqual( 1 );
				}
			} finally {
				await admin.visitSiteEditor( {
					postType: 'wp_template',
				} );
				await editor.revertTemplate( {
					templateName: 'Single Product',
				} );
			}
		} );
	} );
} );
