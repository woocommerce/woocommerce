/**
 * Tests for the useCheckoutSubmit hook with payment method place order button support.
 */

import { renderHook } from '@testing-library/react';
import { useCheckoutSubmit } from '../use-checkout-submit';

// Mock the data stores
jest.mock( '@woocommerce/block-data', () => ( {
	checkoutStore: {
		select: jest.fn(),
	},
	paymentStore: {
		select: jest.fn(),
	},
} ) );

// Mock useSelect hook
jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
} ) );

// Mock the context providers
jest.mock( '../../providers', () => ( {
	useCheckoutEventsContext: jest.fn(),
} ) );

jest.mock( '../payment-methods/use-payment-methods', () => ( {
	usePaymentMethods: jest.fn(),
} ) );

import { useSelect } from '@wordpress/data';
import { useCheckoutEventsContext } from '../../providers';
import { usePaymentMethods } from '../payment-methods/use-payment-methods';

describe( 'useCheckoutSubmit', () => {
	const mockCheckoutStore = {
		isCalculating: jest.fn(),
		isBeforeProcessing: jest.fn(),
		isProcessing: jest.fn(),
		isAfterProcessing: jest.fn(),
		isComplete: jest.fn(),
		hasError: jest.fn(),
	};

	const mockPaymentStore = {
		getActivePaymentMethod: jest.fn(),
		isExpressPaymentMethodActive: jest.fn(),
	};

	const mockCheckoutEventsContext = {
		onSubmit: jest.fn(),
	};

	const mockPaymentMethods = {
		'test-payment-method': {
			name: 'test-payment-method',
			placeOrderButtonLabel: 'Pay with Test',
			placeOrderButton: jest.fn(),
		},
		'express-payment-method': {
			name: 'express-payment-method',
			placeOrderButtonLabel: 'Express Pay',
			placeOrderButton: null,
		},
	};

	beforeEach( () => {
		jest.clearAllMocks();

		// Setup default mock returns
		useSelect.mockImplementation( ( selector ) => {
			// First call returns checkout store data
			if ( selector.toString().includes( 'checkoutStore' ) ) {
				return {
					isCalculating: mockCheckoutStore.isCalculating(),
					isBeforeProcessing: mockCheckoutStore.isBeforeProcessing(),
					isProcessing: mockCheckoutStore.isProcessing(),
					isAfterProcessing: mockCheckoutStore.isAfterProcessing(),
					isComplete: mockCheckoutStore.isComplete(),
					hasError: mockCheckoutStore.hasError(),
				};
			}
			// Second call returns payment store data
			if ( selector.toString().includes( 'paymentStore' ) ) {
				return {
					activePaymentMethod: mockPaymentStore.getActivePaymentMethod(),
					isExpressPaymentMethodActive: mockPaymentStore.isExpressPaymentMethodActive(),
				};
			}
			return {};
		} );
		useCheckoutEventsContext.mockReturnValue( mockCheckoutEventsContext );
		usePaymentMethods.mockReturnValue( { paymentMethods: mockPaymentMethods } );

		// Default checkout store values
		mockCheckoutStore.isCalculating.mockReturnValue( false );
		mockCheckoutStore.isBeforeProcessing.mockReturnValue( false );
		mockCheckoutStore.isProcessing.mockReturnValue( false );
		mockCheckoutStore.isAfterProcessing.mockReturnValue( false );
		mockCheckoutStore.isComplete.mockReturnValue( false );
		mockCheckoutStore.hasError.mockReturnValue( false );

		// Default payment store values
		mockPaymentStore.getActivePaymentMethod.mockReturnValue( 'test-payment-method' );
		mockPaymentStore.isExpressPaymentMethodActive.mockReturnValue( false );
	} );

	it( 'returns correct checkout status values', () => {
		mockCheckoutStore.isCalculating.mockReturnValue( true );
		mockCheckoutStore.isProcessing.mockReturnValue( true );
		mockCheckoutStore.isComplete.mockReturnValue( true );

		const { result } = renderHook( () => useCheckoutSubmit() );

		expect( result.current.isCalculating ).toBe( true );
		expect( result.current.waitingForProcessing ).toBe( true );
		expect( result.current.waitingForRedirect ).toBe( true );
	} );

	it( 'returns payment method button label from active payment method', () => {
		mockPaymentStore.getActivePaymentMethod.mockReturnValue( 'test-payment-method' );

		const { result } = renderHook( () => useCheckoutSubmit() );

		expect( result.current.paymentMethodButtonLabel ).toBe( 'Pay with Test' );
	} );

	it( 'returns null for payment method button label when no active payment method', () => {
		mockPaymentStore.getActivePaymentMethod.mockReturnValue( null );

		const { result } = renderHook( () => useCheckoutSubmit() );

		expect( result.current.paymentMethodButtonLabel ).toBeUndefined();
	} );

	it( 'returns payment method place order button from active payment method', () => {
		mockPaymentStore.getActivePaymentMethod.mockReturnValue( 'test-payment-method' );

		const { result } = renderHook( () => useCheckoutSubmit() );

		expect( result.current.paymentMethodPlaceOrderButton ).toBe( mockPaymentMethods[ 'test-payment-method' ].placeOrderButton );
	} );

	it( 'returns null for payment method place order button when not provided', () => {
		mockPaymentStore.getActivePaymentMethod.mockReturnValue( 'express-payment-method' );

		const { result } = renderHook( () => useCheckoutSubmit() );

		expect( result.current.paymentMethodPlaceOrderButton ).toBeNull();
	} );

	it( 'returns null for payment method place order button when no active payment method', () => {
		mockPaymentStore.getActivePaymentMethod.mockReturnValue( null );

		const { result } = renderHook( () => useCheckoutSubmit() );

		expect( result.current.paymentMethodPlaceOrderButton ).toBeNull();
	} );

	it( 'returns onSubmit from checkout events context', () => {
		const { result } = renderHook( () => useCheckoutSubmit() );

		expect( result.current.onSubmit ).toBe( mockCheckoutEventsContext.onSubmit );
	} );

	it( 'calculates isDisabled correctly when processing', () => {
		mockCheckoutStore.isProcessing.mockReturnValue( true );

		const { result } = renderHook( () => useCheckoutSubmit() );

		expect( result.current.isDisabled ).toBe( true );
	} );

	it( 'calculates isDisabled correctly when express payment method is active', () => {
		mockPaymentStore.isExpressPaymentMethodActive.mockReturnValue( true );

		const { result } = renderHook( () => useCheckoutSubmit() );

		expect( result.current.isDisabled ).toBe( true );
	} );

	it( 'calculates waitingForProcessing correctly', () => {
		mockCheckoutStore.isProcessing.mockReturnValue( true );
		mockCheckoutStore.isAfterProcessing.mockReturnValue( false );
		mockCheckoutStore.isBeforeProcessing.mockReturnValue( false );

		const { result } = renderHook( () => useCheckoutSubmit() );

		expect( result.current.waitingForProcessing ).toBe( true );
	} );

	it( 'calculates waitingForRedirect correctly when complete and no error', () => {
		mockCheckoutStore.isComplete.mockReturnValue( true );
		mockCheckoutStore.hasError.mockReturnValue( false );

		const { result } = renderHook( () => useCheckoutSubmit() );

		expect( result.current.waitingForRedirect ).toBe( true );
	} );

	it( 'calculates waitingForRedirect correctly when complete but has error', () => {
		mockCheckoutStore.isComplete.mockReturnValue( true );
		mockCheckoutStore.hasError.mockReturnValue( true );

		const { result } = renderHook( () => useCheckoutSubmit() );

		expect( result.current.waitingForRedirect ).toBe( false );
	} );

	it( 'handles empty payment methods object', () => {
		usePaymentMethods.mockReturnValue( { paymentMethods: {} } );
		mockPaymentStore.getActivePaymentMethod.mockReturnValue( 'non-existent-method' );

		const { result } = renderHook( () => useCheckoutSubmit() );

		expect( result.current.paymentMethodButtonLabel ).toBeUndefined();
		expect( result.current.paymentMethodPlaceOrderButton ).toBeNull();
	} );

	it( 'handles undefined payment methods', () => {
		usePaymentMethods.mockReturnValue( {} );
		mockPaymentStore.getActivePaymentMethod.mockReturnValue( 'test-payment-method' );

		const { result } = renderHook( () => useCheckoutSubmit() );

		expect( result.current.paymentMethodButtonLabel ).toBeUndefined();
		expect( result.current.paymentMethodPlaceOrderButton ).toBeNull();
	} );

	it( 'handles payment method without placeOrderButtonLabel', () => {
		const paymentMethodsWithoutLabel = {
			'test-payment-method': {
				name: 'test-payment-method',
				placeOrderButton: jest.fn(),
			},
		};

		usePaymentMethods.mockReturnValue( { paymentMethods: paymentMethodsWithoutLabel } );
		mockPaymentStore.getActivePaymentMethod.mockReturnValue( 'test-payment-method' );

		const { result } = renderHook( () => useCheckoutSubmit() );

		expect( result.current.paymentMethodButtonLabel ).toBeUndefined();
		expect( result.current.paymentMethodPlaceOrderButton ).toBe( paymentMethodsWithoutLabel[ 'test-payment-method' ].placeOrderButton );
	} );

	it( 'handles payment method without placeOrderButton', () => {
		const paymentMethodsWithoutButton = {
			'test-payment-method': {
				name: 'test-payment-method',
				placeOrderButtonLabel: 'Pay with Test',
			},
		};

		usePaymentMethods.mockReturnValue( { paymentMethods: paymentMethodsWithoutButton } );
		mockPaymentStore.getActivePaymentMethod.mockReturnValue( 'test-payment-method' );

		const { result } = renderHook( () => useCheckoutSubmit() );

		expect( result.current.paymentMethodButtonLabel ).toBe( 'Pay with Test' );
		expect( result.current.paymentMethodPlaceOrderButton ).toBeNull();
	} );
} );
