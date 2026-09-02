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

const cleanupWebhook = async (
	request: APIRequestContext,
	webhookId: number
): Promise< unknown[] > => {
	if ( webhookId <= 0 ) {
		return [];
	}

	try {
		const response = await request.delete(
			`./wp-json/wc/v3/webhooks/${ webhookId }`,
			{
				data: { force: true },
			}
		);
		if ( ! [ 200, 404 ].includes( response.status() ) ) {
			throw new Error(
				`Webhook cleanup failed with HTTP ${ response.status() }.`
			);
		}
		return [];
	} catch ( error ) {
		return [ error ];
	}
};

const throwLifecycleErrors = (
	primaryError: unknown,
	cleanupErrors: unknown[]
) => {
	if ( primaryError && cleanupErrors.length > 0 ) {
		throw new AggregateError(
			[ primaryError, ...cleanupErrors ],
			'Webhook lifecycle and cleanup both failed.'
		);
	}
	if ( primaryError ) {
		throw primaryError;
	}
	if ( cleanupErrors.length > 0 ) {
		throw new AggregateError( cleanupErrors, 'Webhook cleanup failed.' );
	}
};

test.describe( 'Webhooks API tests', () => {
	test( 'can round-trip a webhook through authenticated installed V3 HTTP', async ( {
		request,
	} ) => {
		let webhookId = 0;
		let primaryError: unknown;
		const deliveryUrl = 'http://127.0.0.1:1/woocommerce-webhook';

		try {
			const createResponse = await request.post(
				'./wp-json/wc/v3/webhooks',
				{
					data: {
						name: 'Installed V3 order updates',
						topic: 'order.updated',
						delivery_url: deliveryUrl,
					},
				}
			);
			const createdWebhook = await createResponse.json();
			webhookId = positiveSafeInteger( createdWebhook.id );

			expect( createResponse.status() ).toBe( 201 );
			expect( createResponse.headers().location ).toContain(
				`/wp-json/wc/v3/webhooks/${ webhookId }`
			);
			expect( createdWebhook ).toMatchObject( {
				id: webhookId,
				name: 'Installed V3 order updates',
				status: 'active',
				topic: 'order.updated',
				delivery_url: deliveryUrl,
				hooks: [
					'woocommerce_update_order',
					'woocommerce_order_refunded',
				],
			} );

			const itemPath = `./wp-json/wc/v3/webhooks/${ webhookId }`;
			const itemResponse = await request.get( itemPath );
			expect( itemResponse.status() ).toBe( 200 );

			const item = await itemResponse.json();
			expect( item ).toMatchObject( {
				id: webhookId,
				name: 'Installed V3 order updates',
				status: 'active',
				topic: 'order.updated',
				delivery_url: deliveryUrl,
			} );

			const updateResponse = await request.put( itemPath, {
				data: {
					name: 'Installed V3 paused order updates',
					status: 'paused',
				},
			} );
			expect( updateResponse.status() ).toBe( 200 );

			const updatedWebhook = await updateResponse.json();
			expect( updatedWebhook ).toMatchObject( {
				id: webhookId,
				name: 'Installed V3 paused order updates',
				status: 'paused',
			} );

			const freshItemResponse = await request.get( itemPath );
			expect( freshItemResponse.status() ).toBe( 200 );

			const freshItem = await freshItemResponse.json();
			expect( freshItem ).toMatchObject( {
				id: webhookId,
				name: 'Installed V3 paused order updates',
				status: 'paused',
			} );

			const deleteResponse = await request.delete( itemPath, {
				data: { force: true },
			} );
			expect( deleteResponse.status() ).toBe( 200 );

			const deletedWebhook = await deleteResponse.json();
			expect( deletedWebhook.id ).toBe( webhookId );
			expect( ( await request.get( itemPath ) ).status() ).toBe( 404 );
		} catch ( error ) {
			primaryError = error;
		}

		const cleanupErrors = await cleanupWebhook( request, webhookId );
		throwLifecycleErrors( primaryError, cleanupErrors );
	} );
} );
