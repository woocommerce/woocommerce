const { test, expect } = require( '../../../fixtures/api-tests-fixtures' );
const { allUSTaxesExample } = require( '../../../data' );
const { BASE_URL } = process.env;
const shouldSkip = BASE_URL !== undefined;

test.describe.serial( 'Tax Rates API tests: CRUD', () => {
	let taxRateId;

	test.describe( 'Create a tax rate', () => {
		test( 'can create a tax rate', async ( { request } ) => {
			// call API to create a tax rate
			const response = await request.post( '/wp-json/wc/v3/taxes', {
				data: {
					country: 'US',
					state: 'AL',
					cities: [ 'Alpine', 'Brookside', 'Cardiff' ],
					postcodes: [ '35014', '35036', '35041' ],
					rate: '4',
					name: 'State Tax',
					shipping: false,
				},
			} );
			const responseJSON = await response.json();

			// Save the tax rate ID. It will be used by the retrieve, update, and delete tests.
			taxRateId = responseJSON.id;

			expect( response.status() ).toEqual( 201 );
			expect( typeof responseJSON.id ).toEqual( 'number' );
			// Verify that the tax rate class is 'standard'
			expect( responseJSON.class ).toEqual( 'standard' );
			expect( responseJSON.country ).toEqual( 'US' );
			expect( responseJSON.state ).toEqual( 'AL' );
			expect( responseJSON.city ).toEqual(
				expect.stringMatching( /ALPINE|BROOKSIDE|CARDIFF/ )
			);
			expect( responseJSON.postcode ).toEqual(
				expect.stringMatching( /35014|35036|35041/ )
			);
			expect( responseJSON.name ).toEqual( 'State Tax' );
			expect( responseJSON.priority ).toEqual( 1 );
			expect( responseJSON.compound ).toEqual( false );
			expect( responseJSON.shipping ).toEqual( false );
			expect( responseJSON.order ).toEqual( 0 );
			expect( responseJSON.rate ).toEqual( '4.0000' );
		} );
	} );

	test.describe( 'Retrieve after create', () => {
		test( 'can retrieve a tax rate', async ( { request } ) => {
			// call API to retrieve the previously saved tax rates
			const response = await request.get(
				`/wp-json/wc/v3/taxes/${ taxRateId }`
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( responseJSON.id ).toEqual( taxRateId );
			expect( typeof responseJSON.id ).toEqual( 'number' );
			// Verify that the tax rate class is 'standard'
			expect( responseJSON.class ).toEqual( 'standard' );
			expect( responseJSON.country ).toEqual( 'US' );
			expect( responseJSON.state ).toEqual( 'AL' );
			expect( responseJSON.city ).toEqual(
				expect.stringMatching( /ALPINE|BROOKSIDE|CARDIFF/ )
			);
			expect( responseJSON.postcode ).toEqual(
				expect.stringMatching( /35014|35036|35041/ )
			);
			expect( responseJSON.name ).toEqual( 'State Tax' );
			expect( responseJSON.priority ).toEqual( 1 );
			expect( responseJSON.compound ).toEqual( false );
			expect( responseJSON.shipping ).toEqual( false );
			expect( responseJSON.order ).toEqual( 0 );
			expect( responseJSON.rate ).toEqual( '4.0000' );
		} );

		test( 'can retrieve all tax rates', async ( { request } ) => {
			// call API to retrieve all tax rates
			const response = await request.get( '/wp-json/wc/v3/taxes' );
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( true );
			expect( responseJSON.length ).toBeGreaterThan( 0 );
		} );
	} );

	test.describe( 'Update a tax rate', () => {
		test( `can update a tax rate`, async ( { request } ) => {
			// update tax rate name
			const response = await request.put(
				`/wp-json/wc/v3/taxes/${ taxRateId }`,
				{
					data: {
						name: 'Not State Tax',
					},
				}
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( responseJSON.id ).toEqual( taxRateId );
			expect( responseJSON.name ).toEqual( 'Not State Tax' );
			expect( responseJSON.priority ).toEqual( 1 );
			expect( responseJSON.compound ).toEqual( false );
			expect( responseJSON.shipping ).toEqual( false );
			expect( responseJSON.order ).toEqual( 0 );
			expect( responseJSON.rate ).toEqual( '4.0000' );
		} );

		test( 'retrieve after update tax rate', async ( { request } ) => {
			// call API to retrieve all tax rates
			const response = await request.get(
				`/wp-json/wc/v3/taxes/${ taxRateId }`
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( responseJSON.id ).toEqual( taxRateId );
			expect( responseJSON.name ).toEqual( 'Not State Tax' );
		} );
	} );

	test.describe( 'Delete a tax rate', () => {
		test( 'can permanently delete a tax rate', async ( { request } ) => {
			// Delete the tax rate.
			const response = await request.delete(
				`/wp-json/wc/v3/taxes/${ taxRateId }`,
				{
					data: {
						force: true,
					},
				}
			);
			expect( response.status() ).toEqual( 200 );

			// only run this test on wp-env -- with external hosting there is caching
			// eslint-disable-next-line playwright/no-conditional-in-test
			if ( ! shouldSkip ) {
				// Verify that the tax rate can no longer be retrieved.
				const getDeletedTaxRateResponse = await request.get(
					`/wp-json/wc/v3/taxes/${ taxRateId }`
				);
				expect( getDeletedTaxRateResponse.status() ).toEqual( 404 );
			}
		} );
	} );

	/**
	 * 48 tax rates to be created in one batch.
	 */
	test.describe( 'Batch tax rate operations', () => {
		// set payload to use batch create: action
		const batchCreate48TaxRatesPayload = {
			create: allUSTaxesExample,
		};

		test( 'can batch create tax rates', async ( { request } ) => {
			// Batch create tax rates.
			// call API to batch create tax rates
			const response = await request.post( 'wp-json/wc/v3/taxes/batch', {
				data: batchCreate48TaxRatesPayload,
			} );
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );

			// Verify that the 48 new tax rates were created
			const actualTaxRates = responseJSON.create;
			expect( actualTaxRates ).toHaveLength( allUSTaxesExample.length );

			for ( let i = 0; i < actualTaxRates.length; i++ ) {
				const { id, rate } = actualTaxRates[ i ];

				expect( id ).toBeDefined();
				expect( rate ).toEqual( allUSTaxesExample[ i ].rate );

				// Save the tax rate id
				allUSTaxesExample[ i ].id = id;
			}
		} );

		test( 'can batch update tax rates', async ( { request } ) => {
			// set payload to use batch update: action
			const batchUpdatePayload = {
				update: [
					{
						id: allUSTaxesExample[ 0 ].id,
						rate: '4.1111',
					},
					{
						id: allUSTaxesExample[ 1 ].id,
						order: 49,
						rate: '5.6111',
					},
				],
			};

			// Call API to batch update the tax rates
			const response = await request.post( 'wp-json/wc/v3/taxes/batch', {
				data: batchUpdatePayload,
			} );
			const responseJSON = await response.json();

			// Verify the response code and the number of tax ratess that were updated.
			const updatedTaxRates = responseJSON.update;
			expect( response.status() ).toEqual( 200 );
			expect( updatedTaxRates ).toHaveLength( 2 );

			// Verify that the 1st tax rate was updated to have a new rate.
			expect( updatedTaxRates[ 0 ].id ).toEqual(
				allUSTaxesExample[ 0 ].id
			);
			expect( updatedTaxRates[ 0 ].rate ).toEqual( '4.1111' );

			// Verify that the 2nd tax rate has had the order and the rate updated
			expect( updatedTaxRates[ 1 ].id ).toEqual(
				allUSTaxesExample[ 1 ].id
			);
			expect( updatedTaxRates[ 1 ].order ).toEqual( 49 );
			expect( updatedTaxRates[ 1 ].rate ).toEqual( '5.6111' );
		} );

		test( 'can batch delete tax rates', async ( { request } ) => {
			// Batch delete the 48 tax rates
			const taxRateIdsToDelete = allUSTaxesExample.map(
				( { id } ) => id
			);
			const batchDeletePayload = {
				delete: taxRateIdsToDelete,
			};

			//Call API to batch delete the tax rates
			const response = await request.post( 'wp-json/wc/v3/taxes/batch', {
				data: batchDeletePayload,
			} );
			const responseJSON = await response.json();

			// Verify that the response shows the 48 tax rates.
			const deletedTaxRateIds = responseJSON.delete.map(
				( { id } ) => id
			);
			expect( response.status() ).toEqual( 200 );
			expect( deletedTaxRateIds ).toEqual( taxRateIdsToDelete );

			// only run this step on wp-env -- caching with external hosting makes unreliable
			// eslint-disable-next-line playwright/no-conditional-in-test
			if ( ! shouldSkip ) {
				// Verify that the deleted tax rates cannot be retrieved.
				for ( const id of taxRateIdsToDelete ) {
					// Call the API to attempt to retrieve the tax rates
					const r = await request.get(
						`wp-json/wc/v3/taxes/${ id }`
					);
					expect( r.status() ).toEqual( 404 );
				}
			}
		} );
	} );
} );
