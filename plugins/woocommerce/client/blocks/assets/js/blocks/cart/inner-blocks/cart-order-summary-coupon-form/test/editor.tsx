/**
 * External dependencies
 */
import { screen, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import {
	initializeEditor,
	selectBlock,
} from '../../../../../../../tests/integration/helpers/integration-test-editor';
import '../index';
import '../../cart-order-summary-coupon-form-input';
import '../../cart-order-summary-coupon-form-button';

describe( 'Coupon form editor', () => {
	it( 'creates selectable input and button blocks', async () => {
		await initializeEditor( {
			name: 'woocommerce/cart-order-summary-coupon-form-block',
			attributes: {},
		} );
		await waitFor( () => {
			expect(
				screen.getByLabelText( 'Block: Coupon form input' )
			).toBeVisible();
			expect(
				screen.getByLabelText( 'Block: Coupon form button' )
			).toBeVisible();
		} );
		await selectBlock( 'Block: Coupon form button' );
		expect(
			screen.getByLabelText( 'Block: Coupon form button' )
		).toHaveClass( 'is-selected' );
	} );
} );
