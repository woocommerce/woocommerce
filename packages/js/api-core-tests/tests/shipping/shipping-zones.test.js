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
	it( 'cannot delete the default shipping zone "Locations not covered by your other zones"', async () => {
		// Delete all pre-existing shipping zones
		const { body } = await shippingZonesApi.listAll.shippingZones( {
			_fields: 'id',
		} );
		const ids = body.map( ( { id } ) => id );

		for ( const id of ids ) {
			await shippingZonesApi.delete.shippingZone( id, true );
		}

		// Verify that the default shipping zone remains
		const { body: remainingZones } =
			await shippingZonesApi.listAll.shippingZones( {
				_fields: 'id',
			} );

		expect( remainingZones ).toHaveLength( 1 );
		expect( remainingZones[ 0 ].id ).toEqual( 0 );
	} );

	it( 'cannot update the default shipping zone', async () => {
		const newZoneDetails = {
			name: 'Default shipping zone',
		};
		const { status, body } = await shippingZonesApi.update.shippingZone(
			0,
			newZoneDetails
		);

		expect( status ).toEqual( 403 );
		expect( body.code ).toEqual(
			'woocommerce_rest_shipping_zone_invalid_zone'
		);
		expect( body.message ).toEqual(
			'The "locations not covered by your other zones" zone cannot be updated.'
		);
	} );

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

		expect( body ).toHaveLength( 2 ); // the test shipping zone, and the default 'Locations not covered by your other zones'
		expect( status ).toEqual( shippingZonesApi.listAll.responseCode );
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

	it( 'can add country shipping regions to a shipping zone', async () => {
		const shippingZoneRegion = [ { code: 'GB' }, { code: 'US' } ];

		const { status, body } =
			await shippingZonesApi.updateRegion.shippingZone(
				shippingZone.id,
				shippingZoneRegion
			);

		expect( status ).toEqual( shippingZonesApi.updateRegion.responseCode );
		expect( body[ 0 ].code ).toEqual( 'GB' );
		expect( body[ 0 ].type ).toEqual( 'country' );
		expect( body[ 1 ].code ).toEqual( 'US' );
		expect( body[ 1 ].type ).toEqual( 'country' );
		expect( body ).toHaveLength( 2 );
	} );

	it( 'can update shipping regions to state only', async () => {
		const shippingZoneRegion = [
			{
				code: 'BR:SP',
				type: 'state',
			},
		];

		const { status, body } =
			await shippingZonesApi.updateRegion.shippingZone(
				shippingZone.id,
				shippingZoneRegion
			);

		expect( status ).toEqual( shippingZonesApi.updateRegion.responseCode );
		expect( body[ 0 ].code ).toEqual( 'BR:SP' );
		expect( body[ 0 ].type ).toEqual( 'state' );
		expect( body ).toHaveLength( 1 );
	} );

	it( 'can clear/delete a shipping regions on a shipping zone', async () => {
		const shippingZoneRegion = [];

		const { status, body } =
			await shippingZonesApi.updateRegion.shippingZone(
				shippingZone.id,
				shippingZoneRegion
			);

		expect( status ).toEqual( shippingZonesApi.updateRegion.responseCode );
		expect( body ).toHaveLength( 0 );
	} );

	it( 'can delete a shipping zone', async () => {
		const { status, body } = await shippingZonesApi.delete.shippingZone(
			shippingZone.id,
			true
		);

		expect( status ).toEqual( shippingZonesApi.delete.responseCode );
		expect( body.id ).toEqual( shippingZone.id );

		// Verify that the shipping zone can no longer be retrieved
		const { status: retrieveStatus } =
			await shippingZonesApi.retrieve.shippingZone( shippingZone.id );
		expect( retrieveStatus ).toEqual( 404 );
	} );
} );
