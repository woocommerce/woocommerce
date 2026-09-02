/**
 * External dependencies
 */
import type { APIRequestContext } from '@playwright/test';

/**
 * Internal dependencies
 */
import { test, expect } from '../../../fixtures/api-tests-fixtures';

const positiveSafeInteger = ( value: unknown ): number => {
	expect( Number.isSafeInteger( value ) ).toBe( true );
	expect( value ).toEqual( expect.any( Number ) );
	expect( value ).toBeGreaterThan( 0 );
	return value as number;
};

const cleanupRefundFixtures = async (
	request: APIRequestContext,
	ids: { productId: number; orderId: number; refundId: number }
): Promise< unknown[] > => {
	const paths: string[] = [];
	if ( ids.refundId > 0 && ids.orderId > 0 ) {
		paths.push(
			`./wp-json/wc/v3/orders/${ ids.orderId }/refunds/${ ids.refundId }`
		);
	}
	if ( ids.orderId > 0 ) {
		paths.push( `./wp-json/wc/v3/orders/${ ids.orderId }` );
	}
	if ( ids.productId > 0 ) {
		paths.push( `./wp-json/wc/v3/products/${ ids.productId }` );
	}

	const errors: unknown[] = [];
	for ( const path of paths ) {
		try {
			const response = await request.delete( path, {
				data: { force: true },
			} );
			if ( ! [ 200, 404 ].includes( response.status() ) ) {
				throw new Error(
					`Cleanup failed with HTTP ${ response.status() } for ${ path }`
				);
			}
		} catch ( error ) {
			errors.push( error );
		}
	}
	return errors;
};

const throwLifecycleErrors = (
	primaryError: unknown,
	cleanupErrors: unknown[]
) => {
	if ( primaryError && cleanupErrors.length > 0 ) {
		throw new AggregateError(
			[ primaryError, ...cleanupErrors ],
			'Refund lifecycle and cleanup both failed.'
		);
	}
	if ( primaryError ) {
		throw primaryError;
	}
	if ( cleanupErrors.length > 0 ) {
		throw new AggregateError( cleanupErrors, 'Refund cleanup failed.' );
	}
};

test.describe( 'Refunds API tests', () => {
	test( 'can round-trip a refund through authenticated installed V3 HTTP', async ( {
		request,
	} ) => {
		let productId = 0;
		let orderId = 0;
		let refundId = 0;
		let primaryError: unknown;

		try {
			const productResponse = await request.post(
				'./wp-json/wc/v3/products',
				{
					data: {
						name: 'Refund lifecycle product',
						regular_price: '10.00',
					},
				}
			);
			const product = await productResponse.json();
			productId = positiveSafeInteger( product.id );

			expect( productResponse.status() ).toBe( 201 );

			const orderResponse = await request.post(
				'./wp-json/wc/v3/orders',
				{
					data: {
						status: 'completed',
						line_items: [
							{
								product_id: productId,
								quantity: 1,
							},
						],
					},
				}
			);
			const order = await orderResponse.json();
			orderId = positiveSafeInteger( order.id );

			expect( orderResponse.status() ).toBe( 201 );
			expect( order.line_items ).toHaveLength( 1 );
			const lineItemId = positiveSafeInteger( order.line_items[ 0 ].id );

			const nestedCollectionPath = `./wp-json/wc/v3/orders/${ orderId }/refunds`;
			const createRefundResponse = await request.post(
				nestedCollectionPath,
				{
					data: {
						amount: '1.00',
						reason: 'Late delivery refund.',
						api_refund: false,
						api_restock: false,
						line_items: [
							{
								id: lineItemId,
								quantity: 1,
								refund_total: 1,
							},
						],
					},
				}
			);
			const createdRefund = await createRefundResponse.json();
			refundId = positiveSafeInteger( createdRefund.id );

			expect( createRefundResponse.status() ).toBe( 201 );
			expect( createdRefund.amount ).toBe( '1.00' );
			expect( createdRefund.reason ).toBe( 'Late delivery refund.' );
			expect( createdRefund.line_items ).toHaveLength( 1 );
			expect( createdRefund.line_items[ 0 ] ).toMatchObject( {
				product_id: productId,
				total: '-1.00',
			} );

			const nestedItemPath = `${ nestedCollectionPath }/${ refundId }`;
			const nestedItemResponse = await request.get( nestedItemPath );
			expect( nestedItemResponse.status() ).toBe( 200 );

			const nestedItem = await nestedItemResponse.json();
			const { _links: nestedItemLinks } = nestedItem;

			expect( nestedItem ).toMatchObject( {
				id: refundId,
				amount: '1.00',
				reason: 'Late delivery refund.',
			} );
			expect( nestedItem.line_items[ 0 ].product_id ).toBe( productId );
			expect( nestedItemLinks.self[ 0 ].href ).toContain(
				`/wp-json/wc/v3/orders/${ orderId }/refunds/${ refundId }`
			);

			const nestedListResponse =
				await request.get( nestedCollectionPath );
			expect( nestedListResponse.status() ).toBe( 200 );

			const nestedList = await nestedListResponse.json();

			expect( nestedList.map( ( refund ) => refund.id ) ).toEqual( [
				refundId,
			] );

			const globalListResponse = await request.get(
				'./wp-json/wc/v3/refunds'
			);
			expect( globalListResponse.status() ).toBe( 200 );

			const globalList = await globalListResponse.json();
			const globalRefund = globalList.find(
				( candidate ) => candidate.id === refundId
			);

			expect( globalRefund ).toMatchObject( {
				id: refundId,
				parent_id: orderId,
				amount: '1.00',
				reason: 'Late delivery refund.',
			} );
			const { _links: globalRefundLinks } = globalRefund;
			expect( globalRefundLinks.collection[ 0 ].href ).toContain(
				`/wp-json/wc/v3/orders/${ orderId }/refunds`
			);

			const parentResponse = await request.get(
				`./wp-json/wc/v3/orders/${ orderId }`
			);
			expect( parentResponse.status() ).toBe( 200 );

			const parent = await parentResponse.json();

			expect( parent.refunds ).toEqual( [
				expect.objectContaining( {
					id: refundId,
					reason: 'Late delivery refund.',
					total: '-1.00',
				} ),
			] );

			const deleteResponse = await request.delete( nestedItemPath, {
				data: { force: true },
			} );
			expect( deleteResponse.status() ).toBe( 200 );

			const deletedRefund = await deleteResponse.json();

			expect( deletedRefund.id ).toBe( refundId );
			expect( ( await request.get( nestedItemPath ) ).status() ).toBe(
				404
			);

			const freshParentResponse = await request.get(
				`./wp-json/wc/v3/orders/${ orderId }`
			);
			expect( freshParentResponse.status() ).toBe( 200 );

			const freshParent = await freshParentResponse.json();

			expect( freshParent.refunds ).toEqual( [] );
		} catch ( error ) {
			primaryError = error;
		}

		const cleanupErrors = await cleanupRefundFixtures( request, {
			productId,
			orderId,
			refundId,
		} );
		throwLifecycleErrors( primaryError, cleanupErrors );
	} );
} );
