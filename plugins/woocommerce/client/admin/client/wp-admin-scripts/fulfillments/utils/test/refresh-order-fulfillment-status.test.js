/**
 * Internal dependencies
 */
import '../../test-helper/global-mock';
import { refreshOrderFulfillmentStatus } from '../fulfillment-utils';

const mockGetOrder = jest.fn();
jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	dispatch: () => ( { invalidateResolution: jest.fn() } ),
	resolveSelect: () => ( { getOrder: mockGetOrder } ),
} ) );

describe( 'refreshOrderFulfillmentStatus', () => {
	beforeEach( () => {
		mockGetOrder.mockResolvedValue( {
			id: 42,
			status: 'completed',
			meta_data: [ { key: '_fulfillment_status', value: 'fulfilled' } ],
		} );
	} );

	afterEach( () => {
		document.body.innerHTML = '';
	} );

	it( 'updates the badges and the status select on the Edit Order page', async () => {
		document.body.innerHTML = `
			<input id="post_ID" value="42" />
			<select id="order_status">
				<option value="wc-processing" selected>Processing</option>
				<option value="wc-completed">Completed</option>
			</select>
			<div class="wc-order-fulfillment-badges">
				<mark class="order-status status-processing"><span>Processing</span></mark>
				<mark class="fulfillment-status fulfillments-trigger" data-order-id="42"><span>Unfulfilled</span></mark>
			</div>`;
		const select = document.querySelector( '#order_status' );
		const onChange = jest.fn();
		select.addEventListener( 'change', onChange );

		await refreshOrderFulfillmentStatus( 42 );

		expect(
			document.querySelector( 'mark.fulfillment-status' ).textContent
		).toBe( 'Fulfilled' );
		expect( select.value ).toBe( 'wc-completed' );
		expect( onChange ).toHaveBeenCalled();
		const orderBadge = document.querySelector( 'mark.order-status' );
		expect( orderBadge.className ).toBe( 'order-status status-completed' );
		expect( orderBadge.textContent ).toBe( 'Completed' );
	} );

	it( 'still updates the badge in the orders list', async () => {
		document.body.innerHTML = `
			<table><tr class="order-42">
				<td class="fulfillment_status"><mark class="fulfillment-status" data-order-id="42"><span>Unfulfilled</span></mark></td>
			</tr></table>`;

		await refreshOrderFulfillmentStatus( 42 );

		expect(
			document.querySelector( 'mark.fulfillment-status' ).textContent
		).toBe( 'Fulfilled' );
	} );

	it( 'leaves another order’s status select alone', async () => {
		document.body.innerHTML = `
			<input id="post_ID" value="7" />
			<select id="order_status">
				<option value="wc-processing" selected>Processing</option>
				<option value="wc-completed">Completed</option>
			</select>`;

		await refreshOrderFulfillmentStatus( 42 );

		expect( document.querySelector( '#order_status' ).value ).toBe(
			'wc-processing'
		);
	} );
} );
