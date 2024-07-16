/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import { useErrorHandler, WPError } from '../use-error-handler';

const mockNavigateTo = jest.fn();
const mockFocusByValidatorId = jest.fn();

jest.mock( '@woocommerce/navigation', () => ( {
	getNewPath: jest.fn().mockReturnValue( '/new-path' ),
	navigateTo: jest.fn( ( args ) => mockNavigateTo( args ) ),
} ) );

jest.mock( '@wordpress/i18n', () => ( {
	__: jest.fn( ( msg ) => msg ),
} ) );

jest.mock( '../../contexts/validation-context', () => ( {
	useValidations: jest.fn().mockReturnValue( {
		focusByValidatorId: jest.fn( ( args ) =>
			mockFocusByValidatorId( args )
		),
	} ),
} ) );

jest.mock( '@wordpress/data', () => ( {
	select: jest.fn().mockReturnValue( {
		getBlockParentsByBlockName: jest.fn().mockReturnValue( [ 'parent' ] ),
	} ),
} ) );

jest.mock( '../use-blocks-helper', () => ( {
	useBlocksHelper: jest.fn().mockReturnValue( {
		getParentTabId: jest.fn( ( context ) => context ),
	} ),
} ) );

describe( 'useErrorHandler', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should return the correct error message and props when exists and the field is visible', () => {
		const error = {
			code: 'product_invalid_sku',
			message: 'Invalid or duplicated SKU.',
		} as WPError;
		const visibleTab = 'inventory';

		const { result } = renderHook( () => useErrorHandler() );
		const { getProductErrorMessageAndProps } = result.current;

		const { message, errorProps } = getProductErrorMessageAndProps(
			error,
			visibleTab
		);

		expect( message ).toBe( 'Invalid or duplicated SKU.' );
		expect( errorProps ).toEqual( {} );
	} );

	it( 'should return the correct error message and props when exists and the field is not visible', () => {
		const error = {
			code: 'product_invalid_sku',
		} as WPError;
		const visibleTab = 'general';

		const { result } = renderHook( () => useErrorHandler() );
		const { getProductErrorMessageAndProps } = result.current;

		const { message, errorProps } = getProductErrorMessageAndProps(
			error,
			visibleTab
		);

		expect( message ).toBe( 'Invalid or duplicated SKU.' );
		expect( errorProps.explicitDismiss ).toBeTruthy();
	} );

	it( 'should call focusByValidatorId when errorProps action is triggered', () => {
		const error = {
			code: 'product_invalid_sku',
			validatorId: 'test-validator',
		} as WPError;
		const visibleTab = 'general';

		jest.useFakeTimers();

		const { result } = renderHook( () => useErrorHandler() );
		const { getProductErrorMessageAndProps } = result.current;

		const { errorProps } = getProductErrorMessageAndProps(
			error,
			visibleTab
		);

		expect( errorProps ).toBeDefined();
		expect( errorProps.actions ).toBeDefined();
		expect( errorProps.actions?.length ).toBeGreaterThan( 0 );

		// Trigger the action
		if ( errorProps.actions && errorProps.actions.length > 0 ) {
			errorProps.actions[ 0 ].onClick();
		}

		jest.runAllTimers(); // Run all pending timers (for setTimeout)

		expect( mockNavigateTo ).toHaveBeenCalledWith( { url: '/new-path' } );
		expect( mockFocusByValidatorId ).toHaveBeenCalledWith(
			'test-validator'
		);
	} );
} );
