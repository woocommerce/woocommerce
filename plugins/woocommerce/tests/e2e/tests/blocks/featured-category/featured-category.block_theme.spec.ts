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
					slug: string;
					categories?: Array< { id: number } >;
					images?: Array< { id: number } >;
				};
				type ProductCategoryResponse = {
					id: number;
					name: string;
					slug: string;
					image: { id: number } | null;
				};
				const isPositiveInteger = ( value: unknown ): value is number =>
					typeof value === 'number' &&
					Number.isInteger( value ) &&
					value > 0;

				const capProducts = await requestUtils.rest<
					ProductResponse[]
				>( {
					path: 'wc/v2/products?slug=cap',
				} );
				const capProduct = Array.isArray( capProducts )
					? capProducts.find(
							( product ) =>
								product.slug === 'cap' &&
								isPositiveInteger( product.id ) &&
								product.images?.some( ( image ) =>
									isPositiveInteger( image.id )
								)
					  )
					: undefined;
				const mediaId = capProduct?.images?.find( ( image ) =>
					isPositiveInteger( image.id )
				)?.id;
				if ( ! capProduct || mediaId === undefined ) {
					throw new Error(
						`Failed to find a usable Cap product through REST: ${ JSON.stringify(
							capProducts
						) }`
					);
				}

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
					! isPositiveInteger( createdCategory.id ) ||
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
					updatedCapProduct.slug !== capProduct.slug ||
					! updatedCapProduct.categories?.some(
						( category ) => category.id === createdCategory.id
					)
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
