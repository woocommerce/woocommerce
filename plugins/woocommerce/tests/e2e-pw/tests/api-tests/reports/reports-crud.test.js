const { test, expect } = require( '../../../fixtures/api-tests-fixtures' );

test.describe( 'Reports API tests', () => {
	test( 'can view all reports', async ( { request } ) => {
		// call API to retrieve the reports
		const response = await request.get( '/wp-json/wc/v3/reports' );
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( Array.isArray( responseJSON ) ).toBe( true );

		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'sales',
					description: 'List of sales reports.',
				} ),
			] )
		);

		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'sales',
					description: 'List of sales reports.',
				} ),
			] )
		);

		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'sales',
					description: 'List of sales reports.',
				} ),
			] )
		);

		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'sales',
					description: 'List of sales reports.',
				} ),
			] )
		);

		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'sales',
					description: 'List of sales reports.',
				} ),
			] )
		);

		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'sales',
					description: 'List of sales reports.',
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'sales',
					description: 'List of sales reports.',
				} ),
			] )
		);

		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'sales',
					description: 'List of sales reports.',
				} ),
			] )
		);

		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'sales',
					description: 'List of sales reports.',
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'sales',
					description: 'List of sales reports.',
				} ),
			] )
		);

		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'sales',
					description: 'List of sales reports.',
				} ),
			] )
		);

		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'sales',
					description: 'List of sales reports.',
				} ),
			] )
		);
	} );

	test( 'can view sales reports', async ( { request } ) => {
		// call API to retrieve the sales reports
		const response = await request.get( '/wp-json/wc/v3/reports/sales' );
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( Array.isArray( responseJSON ) ).toBe( true );

		const today = new Date();
		const dd = String( today.getDate() ).padStart( 2, '0' );
		const mm = String( today.getMonth() + 1 ).padStart( 2, '0' ); //January is 0!
		const yyyy = today.getFullYear();
		const dateString = yyyy + '-' + mm + '-' + dd;

		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					total_sales: expect.any( String ),
					net_sales: expect.any( String ),
					average_sales: expect.any( String ),
					total_orders: expect.any( Number ),
					total_items: expect.any( Number ),
					total_tax: expect.any( String ),
					total_shipping: expect.any( String ),
					total_refunds: expect.any( Number ),
					total_discount: expect.any( String ),
					totals_grouped_by: 'day',
					totals: expect.objectContaining( {
						[ dateString ]: {
							sales: expect.any( String ),
							orders: expect.any( Number ),
							items: expect.any( Number ),
							tax: expect.any( String ),
							shipping: expect.any( String ),
							discount: expect.any( String ),
							customers: expect.any( Number ),
						},
					} ),
					total_customers: expect.any( Number ),
				} ),
			] )
		);
	} );

	test( 'can view top sellers reports', async ( { request } ) => {
		// call API to retrieve the top sellers
		const response = await request.get(
			'/wp-json/wc/v3/reports/top_sellers'
		);
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( Array.isArray( responseJSON ) ).toBe( true );

		expect( responseJSON ).toEqual( expect.arrayContaining( [] ) );
	} );

	test( 'can view coupons totals', async ( { request } ) => {
		// call API to retrieve the coupons totals
		const response = await request.get(
			'/wp-json/wc/v3/reports/coupons/totals'
		);
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( Array.isArray( responseJSON ) ).toBe( true );

		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'percent',
					name: 'Percentage discount',
					total: expect.any( Number ),
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'fixed_cart',
					name: 'Fixed cart discount',
					total: expect.any( Number ),
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'fixed_product',
					name: 'Fixed product discount',
					total: expect.any( Number ),
				} ),
			] )
		);
	} );

	test( 'can view customers totals', async ( { request } ) => {
		// call API to retrieve the customers totals
		const response = await request.get(
			'/wp-json/wc/v3/reports/customers/totals'
		);
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( Array.isArray( responseJSON ) ).toBe( true );

		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'paying',
					name: 'Paying customer',
					total: expect.any( Number ),
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'non_paying',
					name: 'Non-paying customer',
					total: expect.any( Number ),
				} ),
			] )
		);
	} );

	test( 'can view orders totals', async ( { request } ) => {
		// call API to retrieve the orders totals
		const response = await request.get(
			'/wp-json/wc/v3/reports/orders/totals'
		);
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( Array.isArray( responseJSON ) ).toBe( true );

		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'pending',
					name: 'Pending payment',
					total: expect.any( Number ),
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'processing',
					name: 'Processing',
					total: expect.any( Number ),
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'on-hold',
					name: 'On hold',
					total: expect.any( Number ),
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'completed',
					name: 'Completed',
					total: expect.any( Number ),
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'cancelled',
					name: 'Cancelled',
					total: expect.any( Number ),
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'refunded',
					name: 'Refunded',
					total: expect.any( Number ),
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'failed',
					name: 'Failed',
					total: expect.any( Number ),
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'checkout-draft',
					name: 'Draft',
					total: expect.any( Number ),
				} ),
			] )
		);
	} );

	test( 'can view products totals', async ( { request } ) => {
		// call API to retrieve the products totals
		const response = await request.get(
			'/wp-json/wc/v3/reports/products/totals'
		);
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( Array.isArray( responseJSON ) ).toBe( true );

		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'external',
					name: 'External/Affiliate product',
					total: expect.any( Number ),
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'grouped',
					name: 'Grouped product',
					total: expect.any( Number ),
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'simple',
					name: 'Simple product',
					total: expect.any( Number ),
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'variable',
					name: 'Variable product',
					total: expect.any( Number ),
				} ),
			] )
		);
	} );

	test( 'can view reviews totals', async ( { request } ) => {
		// call API to retrieve the reviews totals
		const response = await request.get(
			'/wp-json/wc/v3/reports/reviews/totals'
		);
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( Array.isArray( responseJSON ) ).toBe( true );

		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'rated_1_out_of_5',
					name: 'Rated 1 out of 5',
					total: expect.any( Number ),
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'rated_2_out_of_5',
					name: 'Rated 2 out of 5',
					total: expect.any( Number ),
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'rated_3_out_of_5',
					name: 'Rated 3 out of 5',
					total: expect.any( Number ),
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'rated_4_out_of_5',
					name: 'Rated 4 out of 5',
					total: expect.any( Number ),
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'rated_5_out_of_5',
					name: 'Rated 5 out of 5',
					total: expect.any( Number ),
				} ),
			] )
		);
	} );
} );
