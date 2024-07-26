const { test, expect } = require( '../../../fixtures/api-tests-fixtures' );

test.describe( 'Webhooks API tests', () => {
	let webhookId;

	test.describe( 'Create a webhook', () => {
		test( 'can create a webhook', async ( { request } ) => {
			// call API to create a webhook
			const response = await request.post( '/wp-json/wc/v3/webhooks', {
				data: {
					name: 'Order updated',
					topic: 'order.updated',
					delivery_url: 'http://requestb.in/1g0sxmo1',
				},
			} );
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 201 );
			expect( typeof responseJSON.id ).toEqual( 'number' );
			expect( responseJSON.name ).toEqual( 'Order updated' );
			expect( responseJSON.status ).toEqual( 'active' );
			expect( responseJSON.topic ).toEqual( 'order.updated' );
			expect( responseJSON.delivery_url ).toEqual(
				'http://requestb.in/1g0sxmo1'
			);
			expect( responseJSON.hooks ).toEqual(
				expect.arrayContaining( [
					'woocommerce_update_order',
					'woocommerce_order_refunded',
				] )
			);

			webhookId = responseJSON.id;
		} );
	} );

	test.describe( 'Retrieve after create', () => {
		test( 'can retrieve a webhook', async ( { request } ) => {
			// call API to retrieve the previously saved webhook
			const response = await request.get(
				`/wp-json/wc/v3/webhooks/${ webhookId }`
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( false );
			expect( typeof responseJSON.id ).toEqual( 'number' );
			expect( responseJSON.name ).toEqual( 'Order updated' );
			expect( responseJSON.status ).toEqual( 'active' );
			expect( responseJSON.topic ).toEqual( 'order.updated' );
			expect( responseJSON.delivery_url ).toEqual(
				'http://requestb.in/1g0sxmo1'
			);
			expect( responseJSON.hooks ).toEqual(
				expect.arrayContaining( [
					'woocommerce_update_order',
					'woocommerce_order_refunded',
				] )
			);
		} );

		test( 'can retrieve all webhooks', async ( { request } ) => {
			// call API to retrieve all webhooks
			const response = await request.get( '/wp-json/wc/v3/webhooks' );
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( true );
			expect( responseJSON.length ).toBeGreaterThan( 0 );
		} );
	} );

	test.describe( 'Update a webhook', () => {
		test( `can update a web hook`, async ( { request } ) => {
			// update webhook
			const response = await request.put(
				`/wp-json/wc/v3/webhooks/${ webhookId }`,
				{
					data: {
						status: 'paused',
					},
				}
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( responseJSON.status ).toEqual( 'paused' );
		} );
	} );

	test.describe( 'Delete a webhook', () => {
		test( 'can permanently delete a webhook', async ( { request } ) => {
			// Delete the webhook
			const response = await request.delete(
				`/wp-json/wc/v3/webhooks/${ webhookId }`,
				{
					data: {
						force: true,
					},
				}
			);
			expect( response.status() ).toEqual( 200 );

			// Verify that the webhook can no longer be retrieved
			const getDeletedWebhookResponse = await request.get(
				`/wp-json/wc/v3/webhooks/${ webhookId }`
			);

			expect( getDeletedWebhookResponse.status() ).toEqual( 404 );
		} );
	} );

	test.describe( 'Batch webhook operations', () => {
		let webhookId1;
		let webhookId2;
		let webhookId3;
		test( 'can batch create webhooks', async ( { request } ) => {
			// Batch create webhooks
			// call API to batch create a webhook
			const response = await request.post(
				'wp-json/wc/v3/webhooks/batch',
				{
					data: {
						create: [
							{
								name: 'Round toe',
								topic: 'coupon.created',
								delivery_url: 'http://requestb.in/1g0sxmo1',
							},
							{
								name: 'Customer deleted',
								topic: 'customer.deleted',
								delivery_url: 'http://requestb.in/1g0sxmo1',
							},
						],
					},
				}
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );

			// Verify that the new webhooks were created
			const webhooks = responseJSON.create;
			expect( webhooks ).toHaveLength( 2 );
			webhookId1 = webhooks[ 0 ].id;
			webhookId2 = webhooks[ 1 ].id;
			expect( webhookId1 ).toBeDefined();
			expect( webhookId2 ).toBeDefined();
			expect( webhooks[ 0 ].name ).toEqual( 'Round toe' );
			expect( webhooks[ 1 ].name ).toEqual( 'Customer deleted' );
		} );

		test( 'can batch update webhooks', async ( { request } ) => {
			// set payload to create, update and delete webhooks
			const batchUpdatePayload = {
				create: [
					{
						name: 'Order Created',
						topic: 'order.created',
						delivery_url: 'http://requestb.in/1g0sxmo1',
					},
				],
				update: [
					{
						id: webhookId1,
						name: 'Square toe',
					},
				],
				delete: [ webhookId2 ],
			};

			// Call API to batch update the webhooks
			const response = await request.post(
				'wp-json/wc/v3/webhooks/batch',
				{
					data: batchUpdatePayload,
				}
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( responseJSON.create ).toHaveLength( 1 );

			webhookId3 = responseJSON.create[ 0 ].id;
			expect( webhookId3 ).toBeDefined();
			expect( responseJSON.create[ 0 ].name ).toEqual( 'Order Created' );
			expect( responseJSON.create[ 0 ].topic ).toEqual( 'order.created' );
			expect( responseJSON.create[ 0 ].delivery_url ).toEqual(
				'http://requestb.in/1g0sxmo1'
			);

			expect( responseJSON.update ).toHaveLength( 1 );
			expect( responseJSON.update[ 0 ].id ).toEqual( webhookId1 );
			expect( responseJSON.update[ 0 ].name ).toEqual( 'Square toe' );

			// Verify that the deleted webhook can no longer be retrieved
			const getDeletedWebhookResponse = await request.get(
				`/wp-json/wc/v3/webhooks/${ webhookId2 }`
			);
			expect( getDeletedWebhookResponse.status() ).toEqual( 404 );
		} );

		test( 'can batch delete webhooks', async ( { request } ) => {
			// Batch delete the created webhooks
			const response = await request.post(
				'wp-json/wc/v3/webhooks/batch',
				{
					data: {
						delete: [ webhookId1, webhookId3 ],
					},
				}
			);
			await response.json();

			//Call the API to attempte to retrieve the deleted webhooks
			const deletedResponse1 = await request.get(
				`wp-json/wc/v3/webhooks/${ webhookId1 }`
			);
			const deletedResponse3 = await request.get(
				`wp-json/wc/v3/webhooks/${ webhookId3 }`
			);

			expect( deletedResponse1.status() ).toEqual( 404 );
			expect( deletedResponse3.status() ).toEqual( 404 );
		} );
	} );
} );
