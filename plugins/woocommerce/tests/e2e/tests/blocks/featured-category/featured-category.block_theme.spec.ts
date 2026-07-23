/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

const blockData = {
	slug: 'woocommerce/featured-category',
};

test.describe( `${ blockData.slug } Block`, () => {
	test( 'covers frontend rendering and category image editing', async ( {
		editor,
		admin,
		frontendUtils,
		requestUtils,
	} ) => {
		await test.step( 'can be inserted in Post Editor and it is visible on the frontend', async () => {
			await admin.createNewPost();
			await editor.insertBlock( { name: blockData.slug } );
			const blockLocator = await editor.getBlockByName( blockData.slug );
			await blockLocator.getByText( 'Music' ).click();
			await blockLocator.getByText( 'Done' ).click();
			await editor.publishAndVisitPost();
			const blockLocatorFrontend = await frontendUtils.getBlockByName(
				blockData.slug
			);
			await expect( blockLocatorFrontend ).toBeVisible();
			await expect(
				blockLocatorFrontend.getByText( 'Music' )
			).toBeVisible();
			await expect(
				blockLocatorFrontend.getByText( 'Shop now' )
			).toBeVisible();
		} );

		await test.step( 'image can be edited', async () => {
			// Preserve the original nested setup diagnostic within the combined journey.
			// eslint-disable-next-line playwright/no-nested-step
			await test.step( 'Create a product category with an image', async () => {
				type ProductResponse = {
					id: number;
					name: string;
					slug: string;
					type: string;
					status: string;
					catalog_visibility: string;
					sku: string;
					purchasable: boolean;
					in_stock: boolean;
					categories: Array< {
						id: number;
						name: string;
						slug: string;
					} >;
					images: Array< { id: number } >;
				};
				type ProductCategoryResponse = {
					id: number;
					name: string;
					slug: string;
					image: { id: number } | null;
				};
				const hasExpectedCapIdentity = ( product: ProductResponse ) =>
					Number.isInteger( product.id ) &&
					product.id > 0 &&
					product.name === 'Cap' &&
					product.slug === 'cap' &&
					product.type === 'simple' &&
					product.status === 'publish' &&
					product.catalog_visibility === 'visible' &&
					product.sku === 'woo-cap' &&
					product.purchasable === true &&
					product.in_stock === true &&
					Array.isArray( product.images ) &&
					product.images.length > 0 &&
					Number.isInteger( product.images[ 0 ].id ) &&
					product.images[ 0 ].id > 0;

				const capProducts = await requestUtils.rest<
					ProductResponse[]
				>( {
					path: 'wc/v2/products?slug=cap',
				} );
				const capProduct = Array.isArray( capProducts )
					? capProducts[ 0 ]
					: undefined;
				if (
					! Array.isArray( capProducts ) ||
					capProducts.length !== 1 ||
					! capProduct ||
					! hasExpectedCapIdentity( capProduct ) ||
					! Array.isArray( capProduct.categories ) ||
					capProduct.categories.length !== 1 ||
					capProduct.categories[ 0 ].id !== 14 ||
					capProduct.categories[ 0 ].name !== 'Accessories' ||
					capProduct.categories[ 0 ].slug !== 'accessories'
				) {
					throw new Error(
						`Failed to find the expected baseline Cap product through REST: ${ JSON.stringify(
							capProducts
						) }`
					);
				}
				const mediaId = capProduct.images[ 0 ].id;

				const createdCategory =
					await requestUtils.rest< ProductCategoryResponse >( {
						method: 'POST',
						path: 'wc/v2/products/categories',
						data: {
							name: 'Test Category',
							slug: 'test-category',
							image: { id: mediaId },
						},
					} );
				if (
					! Number.isInteger( createdCategory.id ) ||
					createdCategory.id <= 0 ||
					createdCategory.name !== 'Test Category' ||
					createdCategory.slug !== 'test-category' ||
					createdCategory.image?.id !== mediaId
				) {
					throw new Error(
						`Failed to create the expected Test Category through REST: ${ JSON.stringify(
							createdCategory
						) }`
					);
				}

				const updatedCapProduct =
					await requestUtils.rest< ProductResponse >( {
						method: 'POST',
						path: `wc/v2/products/${ capProduct.id }`,
						data: {
							categories: [ { id: createdCategory.id } ],
						},
					} );
				if (
					updatedCapProduct.id !== capProduct.id ||
					! hasExpectedCapIdentity( updatedCapProduct ) ||
					updatedCapProduct.images[ 0 ].id !== mediaId ||
					! Array.isArray( updatedCapProduct.categories ) ||
					updatedCapProduct.categories.length !== 1 ||
					updatedCapProduct.categories[ 0 ].id !==
						createdCategory.id ||
					updatedCapProduct.categories[ 0 ].name !==
						'Test Category' ||
					updatedCapProduct.categories[ 0 ].slug !== 'test-category'
				) {
					throw new Error(
						`Failed to assign the expected Test Category through REST: ${ JSON.stringify(
							updatedCapProduct
						) }`
					);
				}
			} );

			await admin.createNewPost();
			await editor.insertBlock( { name: blockData.slug } );
			const blockLocator = await editor.getBlockByName( blockData.slug );
			await blockLocator.getByText( 'Test Category' ).click();
			await blockLocator.getByText( 'Done' ).click();
			await editor.clickBlockToolbarButton( 'Edit category image' );
			await editor.clickBlockToolbarButton( 'Rotate' );
			await editor.page
				.getByRole( 'button', { name: 'Apply', exact: true } )
				.click();
			await expect(
				editor.canvas.locator(
					'img[alt="Test Category"][src*="-edited"]'
				)
			).toBeVisible();
		} );
	} );
} );
