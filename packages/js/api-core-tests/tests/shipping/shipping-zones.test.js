const { shippingZonesApi } = require( '../../endpoints' );
const { getShippingZoneExample } = require( '../../data' );

/**
 * Shipping zone to be created, retrieved, updated, and deleted by the tests.
 */
const shippingZone = getShippingZoneExample();

/**
 * Tests for the WooCommerce Shipping zones API.
 *
 * @group api
 * @group shipping-zones
 *
 */
describe( 'Shipping zones API tests', () => {
	it( 'can create a shipping zone', async () => {
		const { status, body } = await shippingZonesApi.create.shippingZone(
			shippingZone
		);

		expect( status ).toEqual( shippingZonesApi.create.responseCode );
		expect( typeof body.id ).toEqual( 'number' );
		expect( body.name ).toEqual( shippingZone.name );

		// Save the shipping zone ID. It will be used by other tests.
		shippingZone.id = body.id;
	} );

	it( 'can retrieve a shipping zone', async () => {
		const { status, body } = await shippingZonesApi.retrieve.shippingZone(
			shippingZone.id
		);

		expect( status ).toEqual( shippingZonesApi.retrieve.responseCode );
		expect( body.id ).toEqual( shippingZone.id );
	} );

	it( 'can list all shipping zones', async () => {
		const param = {
			_fields: 'id',
		};
		const { status, body } = await shippingZonesApi.listAll.shippingZones(
			param
		);

		expect( status ).toEqual( shippingZonesApi.listAll.responseCode );
		expect( body ).toHaveLength( 2 ); // the test shipping zone, and the default 'Locations not covered by your other zones'
		expect( body ).toEqual(
			expect.arrayContaining( [ { id: shippingZone.id } ] )
		);
	} );

	it( 'can update a shipping zone', async () => {
		const updatedShippingZone = {
			name: 'United States (Domestic)',
		};

		const { status, body } = await shippingZonesApi.update.shippingZone(
			shippingZone.id,
			updatedShippingZone
		);

		expect( status ).toEqual( shippingZonesApi.retrieve.responseCode );
		expect( body.id ).toEqual( shippingZone.id );
		expect( body.name ).toEqual( updatedShippingZone.name );
	} );

	it( 'can delete a shipping zone', async () => {
		const { status, body } = await shippingZonesApi.delete.shippingZone(
			shippingZone.id,
			true
		);

		expect( status ).toEqual( shippingZonesApi.delete.responseCode );
		expect( body.id ).toEqual( shippingZone.id );

		// Verify that the shipping zone can no longer be retrieved
		const {
			status: retrieveStatus,
		} = await shippingZonesApi.retrieve.shippingZone( shippingZone.id );
		expect( retrieveStatus ).toEqual( 404 );
	} );
} );
