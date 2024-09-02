const { test, expect } = require( '../../../fixtures/api-tests-fixtures' );

test.describe( 'Tax Classes API tests: CRUD', () => {
	let taxClassSlug;

	test.describe( 'Create a tax class', () => {
		test( 'can enable tax calculations', async ( { request } ) => {
			// call API to enable taxes and calculations
			const response = await request.put(
				'/wp-json/wc/v3/settings/general/woocommerce_calc_taxes',
				{
					data: {
						value: 'yes',
					},
				}
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( typeof responseJSON.id ).toEqual( 'string' );
			expect( responseJSON.id ).toEqual( 'woocommerce_calc_taxes' );
			expect( responseJSON.label ).toEqual( 'Enable taxes' );
			expect( responseJSON.type ).toEqual( 'checkbox' );
			expect( responseJSON.value ).toEqual( 'yes' );
			expect( responseJSON.group_id ).toEqual( 'general' );
		} );

		test( 'can create a tax class', async ( { request } ) => {
			// call API to create a taxclass
			const response = await request.post(
				'/wp-json/wc/v3/taxes/classes',
				{
					data: {
						name: 'Test Tax Class',
					},
				}
			);
			const responseJSON = await response.json();
			taxClassSlug = responseJSON.slug;
			expect( response.status() ).toEqual( 201 );
			expect( responseJSON.name ).toEqual( 'Test Tax Class' );
			expect( taxClassSlug ).toEqual( 'test-tax-class' );
		} );
	} );

	test.describe( 'Retrieve after create', () => {
		test( 'can retrieve a tax class', async ( { request } ) => {
			// call API to retrieve the previously saved tax class by slug
			const response = await request.get(
				`/wp-json/wc/v3/taxes/classes/${ taxClassSlug }`
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( responseJSON.length ).toEqual( 1 );
			expect( responseJSON[ 0 ].name ).toEqual( 'Test Tax Class' );
		} );

		test( 'can retrieve all tax classes', async ( { request } ) => {
			// call API to retrieve all tax classes
			const response = await request.get(
				'/wp-json/wc/v3/taxes/classes'
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( true );
			expect( responseJSON.length ).toBeGreaterThan( 0 );
		} );
	} );

	test.describe( 'Update a tax class', () => {
		test( `cannot update a tax class`, async ( { request } ) => {
			// attempt to update tax class should fail
			const response = await request.put(
				`/wp-json/wc/v3/taxes/classes/${ taxClassSlug }`,
				{
					data: {
						name: 'Not able to update tax class',
					},
				}
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 404 );
			expect( responseJSON.code ).toEqual( 'rest_no_route' );
			expect( responseJSON.message ).toEqual(
				'No route was found matching the URL and request method.'
			);
		} );
	} );

	test.describe( 'Delete a tax class', () => {
		test( 'can permanently delete a tax class', async ( { request } ) => {
			// Delete the tax class.
			const response = await request.delete(
				`/wp-json/wc/v3/taxes/classes/${ taxClassSlug }`,
				{
					data: {
						force: true,
					},
				}
			);
			expect( response.status() ).toEqual( 200 );

			// Verify that the tax class can no longer be retrieved (empty array returned)
			const getDeletedTaxClassResponse = await request.get(
				`/wp-json/wc/v3/taxes/classes/${ taxClassSlug }`
			);
			const getDeletedTaxClassResponseJSON =
				await getDeletedTaxClassResponse.json();
			expect( getDeletedTaxClassResponse.status() ).toEqual( 404 );
			expect( getDeletedTaxClassResponseJSON.code ).toEqual(
				'woocommerce_rest_tax_class_invalid_slug'
			);
		} );
	} );
} );
