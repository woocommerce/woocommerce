/* eslint-disable */
const { test, expect } = require( '../../../fixtures/api-tests-fixtures' );
const { getShippingZoneExample } = require( '../../../data' );
const { BASE_URL } = process.env;
const shouldSkip = BASE_URL !== undefined;
/**
 * Tests for the WooCommerce Shipping zones API.
 * @group api
 * @group shipping
 */

test.describe.serial( 'Shipping zones API tests', () => {

	//Shipping zone to be created, retrieved, updated, and deleted by the tests.
	let shippingZone = getShippingZoneExample();

	test( 'cannot delete the default shipping zone "Locations not covered by your other zones"', async ({request}) => {
		// //call API to get all pre-existing shipping zones
		const response  = await request.get( '/wp-json/wc/v3/shipping/zones');

		//convert response to JSON array
		const responseJSON = await response.json();

		//loop through each item and create an array of the ids using map function
		const ids = responseJSON.map( (x)  => x.id) ;

		//for each id, delete the related shipping zone
		for ( const id of ids ) {
			//call API to delete each shipping zone
			const deleteResponse = await request.delete( `/wp-json/wc/v3/shipping/zones/${id}`,{
				data:{force:true}
			})
		}

		// Verify that the default shipping zone remains
		const remainingZonesJSON = (await (await request.get( '/wp-json/wc/v3/shipping/zones')).json());

		expect( remainingZonesJSON ).toHaveLength( 1 );
		expect( remainingZonesJSON[ 0 ].id ).toEqual( 0 );
	} );

	test( 'cannot update the default shipping zone', async ({request}) => {
		//setup newZone with name
		const newZoneDetails = {
			name: 'Default shipping zone',
		};

		//call API to update default shipping zone
		const response = await request.put( `/wp-json/wc/v3/shipping/zones/0`,{
			data:newZoneDetails
		})

		//validate response
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 403 );
		expect( responseJSON.code ).toEqual(
			'woocommerce_rest_shipping_zone_invalid_zone'
		);
		expect( responseJSON.message ).toEqual(
			'The "locations not covered by your other zones" zone cannot be updated.'
		);
	} );

	test( 'can create a shipping zone', async ({request}) => {

		//call API to create a shipping zone
		const response = await request.post( `/wp-json/wc/v3/shipping/zones`,{
			data:shippingZone
		})

		const responseJSON = await response.json();

		//validate response
		expect( response.status() ).toEqual( 201);
		expect( typeof responseJSON.id ).toEqual( 'number' );
		expect( responseJSON.name ).toEqual( shippingZone.name );

		// Save the shipping zone ID. It will be used by other tests.
		shippingZone.id = responseJSON.id;
	} );

	test( 'can retrieve a shipping zone', async ({request}) => {
		//call API to retrieve the created shipping zone
		const response = await request.get( `/wp-json/wc/v3/shipping/zones/${shippingZone.id}`);
		const responseJSON = await response.json();

		//validate response
		expect( response.status() ).toEqual( 200 );
		expect( responseJSON.id ).toEqual( shippingZone.id );
	} );

	test( 'can list all shipping zones', async ({request}) => {
		//call API to retrieve all the shipping zones
		const response = await request.get( 'wp-json/wc/v3/shipping/zones');
		const responseJSON = await response.json();

		expect( responseJSON ).toHaveLength( 2 ); // the test shipping zone, and the default 'Locations not covered by your other zones'
		expect( response.status() ).toEqual( 200 );
		//2nd shipping zone (0-based) will have the new shipping zone id
		expect( responseJSON[1].id ).toEqual(shippingZone.id);

	} );

	test( 'can update a shipping zone', async ({request}) => {
		const updatedShippingZone = {
			name: 'United States (Domestic)',
		};

		//call API to update the last created shipping zone
		const response = await request.put( `/wp-json/wc/v3/shipping/zones/${shippingZone.id}`,{
			data:updatedShippingZone
		});

		const responseJSON = await response.json();

		//validate response
		expect( response.status() ).toEqual( 200 );
		expect( responseJSON.id ).toEqual( shippingZone.id );
		expect( responseJSON.name ).toEqual( updatedShippingZone.name );
	} );

	test( 'can add a shipping region to a shipping zone', async ({request}) => {

		//call API to retrieve the locations of the last created shipping zone
		const response = await request.get( `/wp-json/wc/v3/shipping/zones/${shippingZone.id}/locations`);
		expect( response.status() ).toEqual( 200 );

		//no locations exist initially
		//update the locations of a shipping zone region to include GB (UK) and US
		const putResponse2Countries = await request.put( `/wp-json/wc/v3/shipping/zones/${shippingZone.id}/locations`,{
			data:[{code:'GB'},{code:'US'}]
		});

		const putResponse2CountriesJSON = await putResponse2Countries.json();
		expect( putResponse2Countries.status() ).toEqual( 200 );
		expect( putResponse2CountriesJSON[0].code ).toEqual( 'GB' );
		expect( putResponse2CountriesJSON[0].type ).toEqual( 'country' );
		expect( putResponse2CountriesJSON[1].code ).toEqual( 'US' );
		expect( putResponse2CountriesJSON[1].type ).toEqual( 'country' );
		expect( putResponse2CountriesJSON).toHaveLength(2);

	} );

	test( 'can update a shipping region on a shipping zone', async ({request}) => {

		//GB and US locations exist initially
		//update the locations of the shipping zone regions to contain an individual state
		const putResponseStateOnly = await request.put( `/wp-json/wc/v3/shipping/zones/${shippingZone.id}/locations`,{
			data:[{
				code: 'BR:SP',
				type: 'state'
			  }]
		});

		const putResponseStateOnlyJSON = await putResponseStateOnly.json();
		expect( putResponseStateOnly.status() ).toEqual( 200 );
		expect( putResponseStateOnlyJSON[0].code ).toEqual( 'BR:SP' );
		expect( putResponseStateOnlyJSON[0].type ).toEqual( 'state' );
		expect( putResponseStateOnlyJSON).toHaveLength(1);

	} );

	test( 'can clear/delete a shipping region on a shipping zone', async ({request}) => {

		//GB and US locations exist initially
		//update the locations of the shipping zone regions to contain an individual state
		const putResponseStateOnly = await request.put( `/wp-json/wc/v3/shipping/zones/${shippingZone.id}/locations`,{
			data:[]
		});

		const putResponseStateOnlyJSON = await putResponseStateOnly.json();
		await expect( putResponseStateOnly.status() ).toEqual( 200 );

		// running on external hosts, this can be 0 or 1
		expect([0, 1]).toContain(putResponseStateOnlyJSON.length);

	} );

	test( 'can delete a shipping zone', async ({request}) => {

		//call API to delete the last created shipping zone
		const deleteResponse = await request.delete( `/wp-json/wc/v3/shipping/zones/${shippingZone.id}`,{
			data:{force:true}
		})

		const deleteResponseJSON = await deleteResponse.json();

		//validate response
		await expect( deleteResponse.status() ).toEqual( 200 );
		await expect( deleteResponseJSON.id ).toEqual( shippingZone.id );

		// only run on wp-env because caching on external hosts makes unreliable
		if ( ! shouldSkip ) {
			//call API to attempt to retrieve the deleted shipping zone
			const response = await request.get( `/wp-json/wc/v3/shipping/zones/${shippingZone.id}`);
			//validate response
			await expect( response.status() ).toEqual( 404 );
		}
	} );


} );
