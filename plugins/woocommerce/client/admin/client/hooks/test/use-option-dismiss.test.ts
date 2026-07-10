/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react-hooks/dom';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useOptionDismiss } from '../use-option-dismiss';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	useDispatch: jest.fn(),
} ) );

const OPTION_NAME = 'woocommerce_test_recommendations_hidden';

const mockSelect = ( {
	option,
	hasResolved,
}: {
	option: string | boolean;
	hasResolved: boolean;
} ) => {
	( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
		fn( () => ( {
			getOption: () => option,
			hasFinishedResolution: () => hasResolved,
		} ) )
	);
};

describe( 'useOptionDismiss', () => {
	let updateOptions: jest.Mock;

	beforeEach( () => {
		updateOptions = jest.fn();
		( useDispatch as jest.Mock ).mockReturnValue( { updateOptions } );
	} );

	it( 'treats an unresolved option as dismissed to avoid flashing the card', () => {
		mockSelect( { option: false, hasResolved: false } );

		const { result } = renderHook( () => useOptionDismiss( OPTION_NAME ) );

		expect( result.current.isDismissed ).toBe( true );
	} );

	it( 'is dismissed when the resolved option is "yes"', () => {
		mockSelect( { option: 'yes', hasResolved: true } );

		const { result } = renderHook( () => useOptionDismiss( OPTION_NAME ) );

		expect( result.current.isDismissed ).toBe( true );
	} );

	it( 'is not dismissed when the resolved option is not "yes"', () => {
		mockSelect( { option: false, hasResolved: true } );

		const { result } = renderHook( () => useOptionDismiss( OPTION_NAME ) );

		expect( result.current.isDismissed ).toBe( false );
	} );

	it( 'persists the dismissal through updateOptions', () => {
		mockSelect( { option: false, hasResolved: true } );

		const { result } = renderHook( () => useOptionDismiss( OPTION_NAME ) );

		act( () => {
			result.current.onDismiss();
		} );

		expect( updateOptions ).toHaveBeenCalledWith( {
			[ OPTION_NAME ]: 'yes',
		} );
	} );
} );
