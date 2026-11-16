/**
 * External dependencies
 */
import { select, dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { pushChanges } from '../push-changes';
import { STORE_KEY as CHECKOUT_STORE_KEY } from '../constants';
import { STORE_KEY as PAYMENT_STORE_KEY } from '../../payment/constants';

const originalSelect = jest.requireActual( '@wordpress/data' ).select;
const originalDispatch = jest.requireActual( '@wordpress/data' ).dispatch;

jest.mock( '@wordpress/data', () => {
	return {
		...jest.requireActual( '@wordpress/data' ),
		select: jest.fn(),
		dispatch: jest.fn(),
	};
} );

// pushChanges only runs on a page with the Checkout block.
jest.mock( '@woocommerce/settings', () => {
	const actual = jest.requireActual( '@woocommerce/settings' );
	return {
		...actual,
		getSetting: ( name: string, fallback: unknown ) =>
			name === 'isCheckoutBlock'
				? true
				: actual.getSetting( name, fallback ),
	};
} );

describe( 'pushChanges', () => {
	const updateDraftOrder = jest.fn().mockResolvedValue( undefined );
	const state = {
		orderNotes: '',
		activePaymentMethod: '',
	};

	const mockStores = () => {
		( select as jest.Mock ).mockImplementation( ( storeName ) => {
			const originalStore = originalSelect( storeName );
			if ( storeName === CHECKOUT_STORE_KEY ) {
				return {
					...originalStore,
					getOrderNotes: () => state.orderNotes,
					getAdditionalFields: () => ( {} ),
				};
			}
			if ( storeName === PAYMENT_STORE_KEY ) {
				return {
					...originalStore,
					getActivePaymentMethod: () => state.activePaymentMethod,
					isExpressPaymentStarted: () => false,
				};
			}
			return originalStore;
		} );
		( dispatch as jest.Mock ).mockImplementation( ( storeName ) => {
			const originalStore = originalDispatch( storeName );
			if ( storeName === CHECKOUT_STORE_KEY ) {
				return { ...originalStore, updateDraftOrder };
			}
			return originalStore;
		} );
	};

	beforeAll( () => {
		// pushChanges needs a cart session to push at all.
		document.cookie = 'woocommerce_cart_hash=abc';
		mockStores();
		// The first call only records the starting data.
		pushChanges( false );
	} );

	afterEach( () => {
		updateDraftOrder.mockClear();
	} );

	it( 'sends order notes while no payment method is selected, without a payment_method', () => {
		state.orderNotes = 'Leave at the door';

		pushChanges( false );

		expect( updateDraftOrder ).toHaveBeenCalledTimes( 1 );
		expect( updateDraftOrder ).toHaveBeenCalledWith( {
			order_notes: 'Leave at the door',
		} );
	} );

	it( 'sends nothing when nothing changed', () => {
		pushChanges( false );

		expect( updateDraftOrder ).not.toHaveBeenCalled();
	} );

	it( 'sends the payment method once the shopper selects one', () => {
		state.activePaymentMethod = 'cod';

		pushChanges( false );

		expect( updateDraftOrder ).toHaveBeenCalledWith( {
			payment_method: 'cod',
		} );
	} );
} );
