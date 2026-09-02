/**
 * External dependencies
 */
import type { APIRequestContext } from '@playwright/test';

/**
 * Internal dependencies
 */
import { test, expect } from '../../../fixtures/api-tests-fixtures';

const requirePositiveProductId = (
	value: unknown,
	operation: string
): number => {
	if (
		typeof value !== 'number' ||
		! Number.isSafeInteger( value ) ||
		value <= 0
	) {
		throw new Error(
			`${ operation } returned an invalid product ID: ${ String(
				value
			) }`
		);
	}

	return value;
};

const cleanupProducts = async (
	request: APIRequestContext,
	productIds: number[]
) => {
	const cleanupErrors: unknown[] = [];

	for ( let index = productIds.length - 1; index >= 0; index-- ) {
		const productId = productIds[ index ];
		try {
			const response = await request.delete(
				`./wp-json/wc/v3/products/${ productId }`,
				{ data: { force: true } }
			);

			if ( ! [ 200, 404 ].includes( response.status() ) ) {
				throw new Error(
					`Product ${ productId } cleanup returned ${ response.status() }.`
				);
			}
		} catch ( error ) {
			cleanupErrors.push( error );
		}
	}

	return cleanupErrors;
};

test.describe( 'Products API tests: List All Products', () => {
	test.describe( 'orderby', () => {
		test( 'include', async ( { request } ) => {
			const createdProductIds: number[] = [];
			let primaryFailure: { error: unknown } | undefined;

			try {
				const suffix = Date.now();
				const createdProducts: Array< { id: number; name: string } > =
					[];

				for ( const label of [ 'First', 'Second', 'Third' ] ) {
					const name = `Slice 049 include ${ label } ${ suffix }`;
					const response = await request.post(
						'./wp-json/wc/v3/products',
						{
							data: {
								name,
								type: 'simple',
								regular_price: '12.49',
							},
						}
					);
					const product = await response.json();
					const productId = requirePositiveProductId(
						product.id,
						`${ label } product creation`
					);
					createdProductIds.push( productId );

					expect( response.status() ).toEqual( 201 );
					expect( product.name ).toEqual( name );
					createdProducts.push( { id: productId, name } );
				}

				const includedProducts = [
					createdProducts[ 2 ],
					createdProducts[ 0 ],
					createdProducts[ 1 ],
				];
				const includeIds = includedProducts.map( ( { id } ) => id );

				const assertIncludeOrder = async ( order: 'asc' | 'desc' ) => {
					const response = await request.get(
						'./wp-json/wc/v3/products',
						{
							params: {
								include: includeIds.join( ',' ),
								orderby: 'include',
								order,
								per_page: includeIds.length,
							},
						}
					);
					const products = await response.json();

					expect( response.status() ).toEqual( 200 );
					expect( products ).toHaveLength( includeIds.length );
					expect( products.map( ( { id } ) => id ) ).toEqual(
						includeIds
					);
					expect( products.map( ( { name } ) => name ) ).toEqual(
						includedProducts.map( ( { name } ) => name )
					);
				};

				await assertIncludeOrder( 'asc' );
				await assertIncludeOrder( 'desc' );

				const updatedName = `Slice 049 include refreshed ${ suffix }`;
				const updateResponse = await request.put(
					`./wp-json/wc/v3/products/${ includedProducts[ 1 ].id }`,
					{ data: { name: updatedName } }
				);
				const updatedProduct = await updateResponse.json();

				expect( updateResponse.status() ).toEqual( 200 );
				expect( updatedProduct.id ).toEqual( includedProducts[ 1 ].id );
				expect( updatedProduct.name ).toEqual( updatedName );
				includedProducts[ 1 ].name = updatedName;

				await assertIncludeOrder( 'desc' );
			} catch ( error ) {
				primaryFailure = { error };
			}

			const cleanupErrors = await cleanupProducts(
				request,
				createdProductIds
			);

			if ( primaryFailure && cleanupErrors.length > 0 ) {
				throw new AggregateError(
					[ primaryFailure.error, ...cleanupErrors ],
					'Product include-order lifecycle and cleanup both failed.'
				);
			}
			if ( primaryFailure ) {
				throw primaryFailure.error;
			}
			if ( cleanupErrors.length > 0 ) {
				throw new AggregateError(
					cleanupErrors,
					'Product include-order cleanup failed.'
				);
			}
		} );
	} );
} );
