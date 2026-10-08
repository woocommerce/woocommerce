/**
 * Internal dependencies
 */
import '../../test-helper/global-mock';
import { getOrderPickupLocation } from '../order-utils';
import { getFulfillmentPickupLocation } from '../fulfillment-utils';

const orderWithShipping = ( shippingLines ) => ( {
	id: 1,
	line_items: [],
	shipping_lines: shippingLines,
} );

describe( 'getOrderPickupLocation', () => {
	it( 'returns null without an order', () => {
		expect( getOrderPickupLocation( null ) ).toBeNull();
	} );

	it( 'returns null for an order that ships', () => {
		expect(
			getOrderPickupLocation(
				orderWithShipping( [
					{
						method_id: 'flat_rate',
						method_title: 'Flat rate',
						meta_data: [],
					},
				] )
			)
		).toBeNull();
	} );

	it( 'reads the location the customer chose at checkout', () => {
		expect(
			getOrderPickupLocation(
				orderWithShipping( [
					{
						method_id: 'pickup_location',
						method_title: 'Pickup',
						meta_data: [
							{
								key: 'pickup_location',
								value: ' Main Street store ',
							},
							{
								key: 'pickup_address',
								value: '123 Main Street, Austin, TX 78701',
							},
							{
								key: 'pickup_details',
								value: 'Ask at the counter.',
							},
						],
					},
				] )
			)
		).toEqual( {
			name: 'Main Street store',
			address: '123 Main Street, Austin, TX 78701',
			details: 'Ask at the counter.',
		} );
	} );

	it( 'uses the method title for a shipping zone Local pickup', () => {
		expect(
			getOrderPickupLocation(
				orderWithShipping( [
					{
						method_id: 'local_pickup',
						method_title: 'Local pickup',
						meta_data: [],
					},
				] )
			)
		).toEqual( { name: 'Local pickup', address: '', details: '' } );
	} );

	it( 'recognises other pickup methods the store registers', () => {
		window.wcFulfillmentSettings.local_pickup_method_ids = [
			'my_store_pickup',
		];
		expect(
			getOrderPickupLocation(
				orderWithShipping( [
					{
						method_id: 'my_store_pickup',
						method_title: 'Collect in store',
						meta_data: [],
					},
				] )
			)?.name
		).toBe( 'Collect in store' );
		delete window.wcFulfillmentSettings.local_pickup_method_ids;
	} );
} );

describe( 'getFulfillmentPickupLocation', () => {
	it( 'returns null for a shipment', () => {
		expect(
			getFulfillmentPickupLocation( { id: 1, meta_data: [] } )
		).toBeNull();
	} );

	it( 'reads the pickup location stored on the fulfillment', () => {
		expect(
			getFulfillmentPickupLocation( {
				id: 1,
				meta_data: [
					{
						id: 1,
						key: '_pickup_location',
						value: 'Main Street store',
					},
					{
						id: 2,
						key: '_pickup_details',
						value: 'Ask at the counter.',
					},
				],
			} )
		).toEqual( {
			name: 'Main Street store',
			address: '',
			details: 'Ask at the counter.',
		} );
	} );
} );
