const { shippingZonesApi } = require( '../../endpoints' );
const { getShippingZoneExample } = require( '../../data' );

/**
 * Shipping zone to be created.
 */
const shippingZone = getShippingZoneExample();

/**
 * Tests for the WooCommerce Shipping zones API.
 *
 * @group api
 * @group shipping-zone
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

		// Save the shipping zone ID
		shippingZone.id = body.id;
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
