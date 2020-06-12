/**
 * @format
 */

/**
 * Internal dependencies
 */
import { StoreOwnerFlow } from '../../utils/flows';
import { verifyPublishAndTrash } from '../../utils';

describe( 'Add New Order Page', () => {
	beforeAll( async () => {
		await StoreOwnerFlow.login();
	} );

	it( 'can create new order', async () => {
		// Go to "add order" page
		await StoreOwnerFlow.openNewOrder();

		// Make sure we're on the add order page
		await expect( page.title() ).resolves.toMatch( 'Add new order' );

		// Set order data
		await expect( page ).toSelect( '#order_status', 'Processing' );
		await expect( page ).toFill( 'input[name=order_date]',  '2018-12-13' );
		await expect( page ).toFill( 'input[name=order_date_hour]',  '18' );
		await expect( page ).toFill( 'input[name=order_date_minute]',  '55' );

		// Create order, verify that it was created. Trash order, verify that it was trashed.
		await verifyPublishAndTrash(
			'.order_actions li .save_order',
			'#message',
			'Order updated.',
			'1 order moved to the Trash.'
		);
	} );
} );
