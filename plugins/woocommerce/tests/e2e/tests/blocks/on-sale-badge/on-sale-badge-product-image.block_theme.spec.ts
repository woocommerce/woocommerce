/**
 * External dependencies
 */
import type { FrameLocator, Page } from '@playwright/test';
import { test as base, expect } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import ProductCollectionPage from '../product-collection/product-collection.page';

const test = base.extend< { productCollectionPage: ProductCollectionPage } >( {
	productCollectionPage: async ( { page, admin, editor }, use ) => {
		const pageObject = new ProductCollectionPage( {
			page,
			admin,
			editor,
		} );
		await use( pageObject );
	},
} );

const productImageSelector = '.wp-block-woocommerce-product-image';
const badgeSelector = `${ productImageSelector } .wc-block-components-product-sale-badge`;

type BadgeOffset = {
	top: number;
	right: number;
};

// Distance between the badge and the top-right corner of its Product Image.
const getBadgeOffset = async (
	root: Page | FrameLocator
): Promise< BadgeOffset > =>
	root
		.locator( badgeSelector )
		.first()
		.evaluate( ( badge, selector ) => {
			const productImage = badge.closest( selector );

			if ( ! productImage ) {
				return {
					top: Number.POSITIVE_INFINITY,
					right: Number.POSITIVE_INFINITY,
				};
			}

			const badgeRect = badge.getBoundingClientRect();
			const productImageRect = productImage.getBoundingClientRect();

			return {
				top: badgeRect.top - productImageRect.top,
				right: productImageRect.right - badgeRect.right,
			};
		}, productImageSelector );

test.describe( 'woocommerce/product-sale-badge', () => {
	test.describe( 'Inside the Product Image block', () => {
		test( 'sits in the same place in the editor and on the frontend', async ( {
			editor,
			page,
			productCollectionPage,
		} ) => {
			await productCollectionPage.createNewPostAndInsertBlock(
				'productCatalog'
			);

			await expect(
				editor.canvas.locator( badgeSelector ).first()
			).toBeVisible();
			const editorOffset = await getBadgeOffset( editor.canvas );

			await productCollectionPage.publishAndGoToFrontend();

			await expect( page.locator( badgeSelector ).first() ).toBeVisible();
			await expect
				.poll( async () => {
					const frontendOffset = await getBadgeOffset( page );

					return Math.max(
						Math.abs( frontendOffset.top - editorOffset.top ),
						Math.abs( frontendOffset.right - editorOffset.right )
					);
				} )
				.toBeLessThanOrEqual( 1 );
		} );
	} );
} );
