/**
 * External dependencies
 */
import type { FrameLocator, Page } from '@playwright/test';
import { test, expect, BLOCK_THEME_SLUG } from '@woocommerce/e2e-utils';

const blockData = {
	name: 'woocommerce/product-sale-badge',
	slug: 'single-product',
	productPage: '/product/hoodie/',
};

const badgeSelector = '.wc-block-components-product-sale-badge';
const badgeContainerSelector = '.wp-block-woocommerce-product-sale-badge';

type SaleBadgeAlignment = 'left' | 'center' | 'right';

const getAlignmentDelta = async (
	root: Page | FrameLocator,
	alignment: SaleBadgeAlignment
): Promise< number > =>
	root
		.locator( badgeContainerSelector )
		.first()
		.evaluate(
			( container, evaluation ) => {
				const badge = container.querySelector(
					evaluation.badgeSelector
				);

				if ( ! badge ) {
					return Number.POSITIVE_INFINITY;
				}

				const badgeRect = badge.getBoundingClientRect();
				const containerRect = container.getBoundingClientRect();

				if ( evaluation.alignment === 'left' ) {
					return Math.abs( badgeRect.left - containerRect.left );
				}

				if ( evaluation.alignment === 'center' ) {
					const badgeMidpoint =
						( badgeRect.left + badgeRect.right ) / 2;
					const containerMidpoint =
						( containerRect.left + containerRect.right ) / 2;

					return Math.abs( badgeMidpoint - containerMidpoint );
				}

				return Math.abs( containerRect.right - badgeRect.right );
			},
			{ alignment, badgeSelector }
		);

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
			editor,
			page,
		} ) => {
			const context = page.context();
			const baselinePageCount = context.pages().length;
			const frontendPage = await context.newPage();

			try {
				expect( context.pages() ).toHaveLength( baselinePageCount + 1 );

				await editor.openDocumentSettingsSidebar();
				await editor.insertBlock( {
					name: 'woocommerce/product-gallery',
				} );
				await page
					.getByRole( 'checkbox', {
						name: 'Open pop-up when clicked',
						exact: true,
					} )
					.uncheck();

				let block = await editor.getBlockByName( blockData.name );
				await expect( block ).toBeVisible();
				await expect
					.poll( () => getAlignmentDelta( editor.canvas, 'right' ) )
					.toBeLessThanOrEqual( 1 );

				await editor.saveSiteEditorEntities( {
					isOnlyCurrentEntityDirty: true,
				} );

				await frontendPage.goto( blockData.productPage );
				await expect(
					frontendPage.locator( badgeSelector ).first()
				).toBeVisible();
				await expect
					.poll( () => getAlignmentDelta( frontendPage, 'right' ) )
					.toBeLessThanOrEqual( 1 );

				for ( const alignment of [ 'left', 'center' ] as const ) {
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
							getAlignmentDelta( editor.canvas, alignment )
						)
						.toBeLessThanOrEqual( 1 );

					await editor.saveSiteEditorEntities( {
						isOnlyCurrentEntityDirty: true,
					} );

					await frontendPage.goto( blockData.productPage );
					await expect(
						frontendPage.locator( badgeSelector ).first()
					).toBeVisible();
					await expect
						.poll( () =>
							getAlignmentDelta( frontendPage, alignment )
						)
						.toBeLessThanOrEqual( 1 );
				}
			} finally {
				await frontendPage.close();
				expect( context.pages() ).toHaveLength( baselinePageCount );
			}
		} );
	} );
} );
