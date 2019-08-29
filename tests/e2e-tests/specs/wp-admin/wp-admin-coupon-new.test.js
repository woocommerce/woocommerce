/**
 * @format
 */

/**
 * External dependencies
 */
import { activatePlugin } from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
import { StoreOwnerFlow } from '../../utils/flows';
import { clickTab, verifyPublishAndTrash } from "../../utils";

describe( 'Add New Coupon Page', () => {
	beforeAll( async () => {
		await activatePlugin( 'woocommerce' );
	} );

	it( 'can create new coupon', async () => {
		// Go to "add coupon" page
		await StoreOwnerFlow.openNewCoupon();

		// Make sure we're on the add coupon page
		await expect( page.title() ).resolves.toMatch( 'Add new coupon' );

		// Fill in coupon code and description
		await expect( page ).toFill( '#title', 'code-' + new Date().getTime().toString() );
		await expect( page ).toFill( '#woocommerce-coupon-description', 'test coupon' );

		// Set general coupon data
		await clickTab( 'General' );
		await expect( page ).toSelect( '#discount_type', 'Fixed cart discount' );
		await expect( page ).toFill( '#coupon_amount', '100' );

		// Publish coupon, verify that it was published. Trash coupon, verify that it was trashed.
		await verifyPublishAndTrash(
			'#publish',
			'#message',
			'Coupon updated.',
			'Move to Trash',
			'1 coupon moved to the Trash.'
		);

	} );
} );
