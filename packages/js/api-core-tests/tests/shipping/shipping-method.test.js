const { shippingMethodsApi } = require( '../../endpoints' );
const { getShippingMethodExample } = require( '../../data' );

/**
 * Shipping zone id for "Locations not covered by your other zones".
 */
const shippingZoneId = 0;

/**
 * Data table for shipping methods.
 */
const shippingMethods = [
	[ 'Flat rate', 'flat_rate', '10' ],
	[ 'Free shipping', 'free_shipping', undefined ],
	[ 'Local pickup', 'local_pickup', '30' ],
];

/**
 * Tests for the WooCommerce Shipping methods API.
 *
 * @group api
 * @group shipping-methods
 *
 */
describe( 'Shipping methods API tests', () => {
	it.each( shippingMethods )(
		"can add a '%s' shipping method",
		async ( methodTitle, methodId, cost ) => {
			const shippingMethod = getShippingMethodExample( methodId, cost );

			const { status, body } =
				await shippingMethodsApi.create.shippingMethod(
					shippingZoneId,
					shippingMethod
				);

			expect( status ).toEqual( shippingMethodsApi.create.responseCode );
			expect( typeof body.id ).toEqual( 'number' );
			expect( body.method_id ).toEqual( methodId );
			expect( body.method_title ).toEqual( methodTitle );
			expect( body.enabled ).toEqual( true );
			expect( body.settings.cost.value || '' ).toEqual( cost || '' );

			// Cleanup: Delete the shipping method
			await shippingMethodsApi.delete.shippingMethod(
				shippingZoneId,
				body.id,
				true
			);
		}
	);
} );
