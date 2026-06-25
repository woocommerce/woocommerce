/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';
import { responseTypes } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { useValidateCheckout } from '../use-validate-checkout';

type ValidationResult = Awaited<
	ReturnType< ReturnType< typeof useValidateCheckout > >
>;

const mockEmit = jest.fn();
jest.mock( '@woocommerce/blocks-checkout-events', () => ( {
	checkoutEventsEmitter: {
		emit: ( ...args: unknown[] ) => mockEmit( ...args ),
	},
	CHECKOUT_EVENTS: {
		CHECKOUT_VALIDATION: 'checkout_validation',
	},
} ) );

const mockShowAllValidationErrors = jest.fn();
const mockSetValidationErrors = jest.fn();
const mockHasValidationErrors = jest.fn();

jest.mock( '@woocommerce/block-data', () => ( {
	validationStore: 'wc/store/validation',
} ) );

jest.mock( '@wordpress/data', () => ( {
	useDispatch: () => ( {
		showAllValidationErrors: mockShowAllValidationErrors,
		setValidationErrors: mockSetValidationErrors,
	} ),
	select: () => ( {
		hasValidationErrors: mockHasValidationErrors,
	} ),
} ) );

const mockScrollIntoView = jest.fn();
const mockFocus = jest.fn();

describe( 'useValidateCheckout', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		jest.useFakeTimers();
		mockEmit.mockResolvedValue( [] );
		mockHasValidationErrors.mockReturnValue( false );
		document.body.innerHTML = '';
	} );

	afterEach( () => {
		jest.useRealTimers();
	} );

	it( 'returns hasError: false when validation passes', async () => {
		mockEmit.mockResolvedValue( [ { type: responseTypes.SUCCESS } ] );

		const { result } = renderHook( () => useValidateCheckout() );

		let validationResult: ValidationResult | undefined;
		await act( async () => {
			validationResult = await result.current();
		} );

		expect( mockEmit ).toHaveBeenCalledWith( 'checkout_validation' );
		expect( validationResult ).toEqual( { hasError: false } );
		expect( mockShowAllValidationErrors ).not.toHaveBeenCalled();
	} );

	it.each( [
		[ 'callback returns error response', responseTypes.ERROR, false ],
		[ 'callback returns fail response', responseTypes.FAIL, false ],
		[ 'callback returns non-success response', 'unknown', false ],
		[ 'validation store has errors', responseTypes.SUCCESS, true ],
	] )(
		'returns hasError: true when %s',
		async ( _, responseType, storeHasErrors ) => {
			mockEmit.mockResolvedValue( [ { type: responseType } ] );
			mockHasValidationErrors.mockReturnValue( storeHasErrors );

			const { result } = renderHook( () => useValidateCheckout() );

			let validationResult: ValidationResult | undefined;
			await act( async () => {
				validationResult = await result.current();
			} );

			expect( validationResult ).toEqual( { hasError: true } );
			expect( mockShowAllValidationErrors ).toHaveBeenCalledTimes( 1 );
		}
	);

	it( 'propagates validation errors and scrolls to first error element on failure', async () => {
		const container = document.createElement( 'div' );
		container.className = 'has-error';
		const input = document.createElement( 'input' );
		input.scrollIntoView = mockScrollIntoView;
		input.focus = mockFocus;
		container.appendChild( input );
		document.body.appendChild( container );

		const validationErrors = {
			billing_email: { message: 'Email is required', hidden: false },
		};
		mockEmit.mockResolvedValue( [
			{ type: responseTypes.ERROR, validationErrors },
		] );

		const { result } = renderHook( () => useValidateCheckout() );

		await act( async () => {
			await result.current();
		} );

		expect( mockSetValidationErrors ).toHaveBeenCalledWith(
			validationErrors
		);

		act( () => {
			jest.advanceTimersByTime( 50 );
		} );

		expect( mockScrollIntoView ).toHaveBeenCalledWith( {
			block: 'center',
		} );
		expect( mockFocus ).toHaveBeenCalled();
	} );
} );
