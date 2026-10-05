import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';

/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useOptionDismiss } from '../use-option-dismiss';
vi.mock( '@wordpress/data', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/data' ) ),
		useSelect: vi.fn(),
		useDispatch: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const OPTION_NAME = 'woocommerce_test_recommendations_hidden';
const mockSelect = ( {
	option,
	hasResolved,
}: {
	option: string | boolean;
	hasResolved: boolean;
} ) => {
	( useSelect as Mock ).mockImplementation( ( fn ) =>
		fn( () => ( {
			getOption: () => option,
			hasFinishedResolution: () => hasResolved,
		} ) )
	);
};
describe( 'useOptionDismiss', () => {
	let updateOptions: Mock;
	beforeEach( () => {
		updateOptions = vi.fn();
		( useDispatch as Mock ).mockReturnValue( {
			updateOptions,
		} );
	} );
	it( 'treats an unresolved option as dismissed to avoid flashing the card', () => {
		mockSelect( {
			option: false,
			hasResolved: false,
		} );
		const { result } = renderHook( () => useOptionDismiss( OPTION_NAME ) );
		expect( result.current.isDismissed ).toBe( true );
		expect( result.current.hasResolved ).toBe( false );
	} );
	it( 'is dismissed when the resolved option is "yes"', () => {
		mockSelect( {
			option: 'yes',
			hasResolved: true,
		} );
		const { result } = renderHook( () => useOptionDismiss( OPTION_NAME ) );
		expect( result.current.isDismissed ).toBe( true );
		expect( result.current.hasResolved ).toBe( true );
	} );
	it( 'is not dismissed when the resolved option is not "yes"', () => {
		mockSelect( {
			option: false,
			hasResolved: true,
		} );
		const { result } = renderHook( () => useOptionDismiss( OPTION_NAME ) );
		expect( result.current.isDismissed ).toBe( false );
		expect( result.current.hasResolved ).toBe( true );
	} );
	it( 'persists the dismissal through updateOptions', () => {
		mockSelect( {
			option: false,
			hasResolved: true,
		} );
		const { result } = renderHook( () => useOptionDismiss( OPTION_NAME ) );
		act( () => {
			result.current.onDismiss();
		} );
		expect( updateOptions ).toHaveBeenCalledWith( {
			[ OPTION_NAME ]: 'yes',
		} );
	} );
} );
