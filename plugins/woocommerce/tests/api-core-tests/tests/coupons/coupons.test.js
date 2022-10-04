const { test, expect } = require( '@playwright/test' );
const { coupon, order } = require( '../../data' );

/**
 * Tests for the WooCommerce Coupons API.
 *
 * @group api
 * @group coupons
 */
test.describe( 'Coupons API tests', () => {
	//create variable to store the coupon id we will be using
	let couponId;

	test( 'can create a coupon', async ( { request } ) => {
		//create testCoupon
		const testCoupon = {
			...coupon,
			code: `${ coupon.code }-${ Date.now() }`,
		};

		//call API to create coupon with testCoupon data
		const response = await request.post( '/wp-json/wc/v3/coupons', {
			data: testCoupon,
		} );

		//obtain the coupon id from the response
		couponId = ( await response.json() ).id;

		//validate the response code and coupon id
		expect( response.status() ).toEqual( 201 );
		expect( couponId ).toBeDefined();

		//validate the response data
		expect( await response.json() ).toEqual(
			expect.objectContaining( {
				code: testCoupon.code,
				amount: Number( coupon.amount ).toFixed( 2 ),
				discount_type: coupon.discount_type,
			} )
		);
	} );

	test( 'can retrieve a coupon', async ( { request } ) => {
		//call API to get previously created coupon
		const response = await request.get(
			`/wp-json/wc/v3/coupons/${ couponId }`
		);

		//validate response
		expect( response.status() ).toEqual( 200 );
		expect( ( await response.json() ).id ).toEqual( couponId );
	} );

	test( 'can update a coupon', async ( { request } ) => {
		//data used to update coupon
		const updatedCouponDetails = {
			description: '10% off storewide',
			maximum_amount: '500.00',
			usage_limit_per_user: 1,
			free_shipping: true,
		};

		//call API to update previously created coupon
		const response = await request.post(
			`/wp-json/wc/v3/coupons/${ couponId }`,
			{
				data: updatedCouponDetails,
			}
		);

		//validate response
		expect( response.status() ).toEqual( 200 );
		expect( await response.json() ).toEqual(
			expect.objectContaining( updatedCouponDetails )
		);
	} );

	test( 'can permanently delete a coupon', async ( { request } ) => {
		//call API to delete previously created coupon
		const response = await request.delete(
			`/wp-json/wc/v3/coupons/${ couponId }`,
			{
				data: { force: true },
			}
		);

		//validate response
		expect( response.status() ).toEqual( 200 );

		//call API to retrieve previously deleted coupon
		const getCouponResponse = await request.get(
			`/wp-json/wc/v3/coupons/${ couponId }`
		);

		//validate response
		expect( getCouponResponse.status() ).toEqual( 404 );
	} );
} );

test.describe( 'Batch update coupons', () => {
	/**
	 * Coupons to be created, updated, and deleted.
	 */
	const expectedCoupons = [
		{
			code: `batchcoupon-${ Date.now() }`,
			discount_type: 'percent',
			amount: '10',
			free_shipping: false,
		},
		{
			code: `batchcoupon-${ Date.now() + 1 }`,
			discount_type: 'percent',
			amount: '20',
		},
	];

	test( 'can batch create coupons', async ( { request } ) => {
		// Batch create 2 new coupons.
		const batchCreatePayload = {
			create: expectedCoupons,
		};

		// call API to batch create coupons
		const batchCreateResponse = await request.post(
			'wp-json/wc/v3/coupons/batch',
			{
				data: batchCreatePayload,
			}
		);
		const batchCreateResponseJSON = await batchCreateResponse.json();
		expect( batchCreateResponse.status() ).toEqual( 200 );

		// Verify that the 2 new coupons were created
		const actualCoupons = batchCreateResponseJSON.create;
		expect( actualCoupons ).toHaveLength( expectedCoupons.length );

		for ( let i = 0; i < actualCoupons.length; i++ ) {
			const { id, code } = actualCoupons[ i ];
			const expectedCouponCode = expectedCoupons[ i ].code;

			expect( id ).toBeDefined();
			expect( code ).toEqual( expectedCouponCode );

			// Save the coupon id
			expectedCoupons[ i ].id = id;
		}
	} );

	test( 'can batch update coupons', async ( { request } ) => {
		// Update the 1st coupon to free shipping.
		// Update the amount of the 2nd coupon to 25.
		const batchUpdatePayload = {
			update: [
				{
					id: expectedCoupons[ 0 ].id,
					free_shipping: true,
				},
				{
					id: expectedCoupons[ 1 ].id,
					amount: '25.00',
				},
			],
		};

		// Call API to update the coupons
		const batchUpdateResponse = await request.post(
			'wp-json/wc/v3/coupons/batch',
			{
				data: batchUpdatePayload,
			}
		);
		const batchUpdateResponseJSON = await batchUpdateResponse.json();

		// Verify the response code and the number of coupons that were updated.
		const updatedCoupons = batchUpdateResponseJSON.update;
		expect( batchUpdateResponse.status() ).toEqual( 200 );
		expect( updatedCoupons ).toHaveLength( expectedCoupons.length );

		// Verify that the 1st coupon was updated to free shipping.
		expect( updatedCoupons[ 0 ].id ).toEqual( expectedCoupons[ 0 ].id );
		expect( updatedCoupons[ 0 ].free_shipping ).toEqual( true );

		// Verify that the amount of the 2nd coupon was updated to 25.
		expect( updatedCoupons[ 1 ].id ).toEqual( expectedCoupons[ 1 ].id );
		expect( updatedCoupons[ 1 ].amount ).toEqual( '25.00' );
	} );

	test( 'can batch delete coupons', async ( { request } ) => {
		// Batch delete the 2 coupons.
		const couponIdsToDelete = expectedCoupons.map( ( { id } ) => id );
		const batchDeletePayload = {
			delete: couponIdsToDelete,
		};

		//Call API to batch delete the coupons
		const batchDeleteResponse = await request.post(
			'wp-json/wc/v3/coupons/batch',
			{
				data: batchDeletePayload,
			}
		);
		const batchDeletePayloadJSON = await batchDeleteResponse.json();

		// Verify that the response shows the 2 coupons.
		const deletedCouponIds = batchDeletePayloadJSON.delete.map(
			( { id } ) => id
		);
		expect( batchDeleteResponse.status() ).toEqual( 200 );
		expect( deletedCouponIds ).toEqual( couponIdsToDelete );

		// Verify that the 2 deleted coupons cannot be retrieved.
		for ( const couponId of couponIdsToDelete ) {
			//Call the API to attempte to retrieve the coupons
			const response = await request.get(
				`wp-json/wc/v3/coupons/${ couponId }`
			);
			expect( response.status() ).toEqual( 404 );
		}
	} );
} );

test.describe( 'List coupons', () => {
	const allCoupons = [
		{
			...coupon,
			code: `listcoupons-01-${ Date.now() }`,
			description: `description-01-${ Date.now() }`,
		},
		{
			...coupon,
			code: `listcoupons-02-${ Date.now() }`,
			description: `description-02-${ Date.now() }`,
		},
		{
			...coupon,
			code: `listcoupons-03-${ Date.now() }`,
			description: `description-03-${ Date.now() }`,
		},
	];

	test.beforeAll( async ( { request } ) => {
		// Call the API to Create list of coupons for testing.
		const response = await request.post( '/wp-json/wc/v3/coupons/batch', {
			data: { create: allCoupons },
		} );
		const responseJSON = await response.json();
		const actualCreatedCoupons = responseJSON.create;

		// Save their coupon ID's
		for ( const thisCoupon of allCoupons ) {
			//set id based on the equivalent coupon code
			const { id } = actualCreatedCoupons.find(
				( { code } ) => thisCoupon.code === code
			);
			//Set the id on the current coupon
			thisCoupon.id = id;
		}
	} );

	test.afterAll( async ( { request } ) => {
		// Clean up created coupons
		const batchDeletePayload = {
			delete: allCoupons.map( ( { id } ) => id ),
		};

		// call API to batch delete the coupons
		await request.post( '/wp-json/wc/v3/coupons/batch', {
			data: batchDeletePayload,
		} );
	} );

	test( 'can list all coupons by default', async ( { request } ) => {
		// call API to get all coupons
		const response = await request.get( '/wp-json/wc/v3/coupons' );
		const responseJSON = await response.json();

		const listedCoupons = responseJSON;
		const actualCouponIdsList = listedCoupons.map( ( { id } ) => id );
		const expectedCouponIdsList = allCoupons.map( ( { id } ) => id );

		expect( response.status() ).toEqual( 200 );
		expect( actualCouponIdsList ).toEqual(
			expect.arrayContaining( expectedCouponIdsList )
		);
	} );

	test( 'can limit result set to matching code', async ( { request } ) => {
		const matchingCoupon = allCoupons[ 1 ];
		const payload = {
			code: matchingCoupon.code,
		};

		// call API to get all coupons with the specified code
		const response = await request.get( '/wp-json/wc/v3/coupons', {
			params: payload,
		} );
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( responseJSON ).toHaveLength( 1 );
		expect( responseJSON[ 0 ].id ).toEqual( matchingCoupon.id );
	} );

	test( 'can paginate results', async ( { request } ) => {
		const pageSize = 2;
		const payload = {
			page: 1,
			per_page: pageSize,
		};

		// call API to get coupons based on the specified page
		const response = await request.get( '/wp-json/wc/v3/coupons/', {
			params: payload,
		} );
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( responseJSON ).toHaveLength( pageSize );
	} );

	test( 'can limit results to matching string', async ( { request } ) => {
		// Search by description
		const matchingCoupon = allCoupons[ 2 ];
		const matchingString = matchingCoupon.description;
		const payload = {
			search: matchingString,
		};

		//call API to return coupon based on passed in string
		const response = await request.get( '/wp-json/wc/v3/coupons/', {
			params: payload,
		} );
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( responseJSON ).toHaveLength( 1 );
		expect( responseJSON[ 0 ].id ).toEqual( matchingCoupon.id );
	} );
} );

test.describe( 'Add coupon to order', () => {
	const testCoupon = {
		code: `coupon-${ Date.now() }`,
		discount_type: 'percent',
		amount: '10',
	};
	let orderId;

	test.beforeAll( async ( { request } ) => {
		// Create a coupon
		const createCouponResponse = await request.post(
			'/wp-json/wc/v3/coupons/',
			{
				data: testCoupon,
			}
		);
		const createCouponResponseJSON = await createCouponResponse.json();
		testCoupon.id = createCouponResponseJSON.id;
	} );

	// Clean up created coupon and order
	test.afterAll( async ( { request } ) => {
		await request.delete( `/wp-json/wc/v3/coupons/${ testCoupon.id }`, {
			data: { force: true },
		} );
		await request.delete( `/wp-json/wc/v3/orders/${ orderId }`, {
			data: { force: true },
		} );
	} );

	test( 'can add coupon to an order', async ( { request } ) => {
		const orderWithCoupon = {
			...order,
			coupon_lines: [ { code: testCoupon.code } ],
		};

		const response = await request.post( '/wp-json/wc/v3/orders', {
			data: orderWithCoupon,
		} );
		const responseJSON = await response.json();
		orderId = responseJSON.id;

		expect( response.status() ).toEqual( 201 );
		expect( responseJSON.coupon_lines ).toHaveLength( 1 );
		expect( responseJSON.coupon_lines[ 0 ].code ).toEqual(
			testCoupon.code
		);
		// Test that the coupon meta data exists.
		// See: https://github.com/woocommerce/woocommerce/issues/28166.
		expect( responseJSON.coupon_lines[ 0 ].meta_data ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					key: 'coupon_data',
					value: expect.objectContaining( {
						code: testCoupon.code,
					} ),
				} ),
			] )
		);
	} );
} );
