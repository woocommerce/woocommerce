import {
	afterEach,
	beforeEach,
	describe,
	expect,
	it,
	vi,
	type Mock,
} from 'vitest';

/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useDispatch, useSelect } from '@wordpress/data';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	useOptionsHydration,
	withOptionsHydration,
} from '../with-options-hydration';
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
const optionData = {
	option: 'val',
	option2: 'val2',
	option3: 'val3',
};
const TestHookComponent = () => {
	useOptionsHydration( optionData );
	return <div></div>;
};
const TestHigherOrderComponent = withOptionsHydration( optionData )( () => (
	<div></div>
) );
describe( 'withOptionsHydration', () => {
	const isResolvingMock = vi.fn();
	const hasFinishedMock = vi.fn();
	const startResolutionMock = vi.fn();
	const receiveOptionsMock = vi.fn();
	beforeEach( () => {
		( useSelect as Mock ).mockImplementation( ( callback ) => {
			return callback( () => ( {
				isResolving: isResolvingMock,
				hasFinishedResolution: hasFinishedMock,
			} ) );
		} );
		( useDispatch as Mock ).mockImplementation( () => ( {
			startResolution: startResolutionMock,
			finishResolution: vi.fn(),
			receiveOptions: receiveOptionsMock,
		} ) );
	} );
	afterEach( () => {
		vi.clearAllMocks();
	} );
	it.each( [
		[ 'useOptionsHydration', TestHookComponent ],
		[ 'withOptionsHydration', TestHigherOrderComponent ],
	] )(
		'%s should call receiveOptions and startResolution when options have not been received yet',
		( name, Comp ) => {
			isResolvingMock.mockReturnValue( false );
			hasFinishedMock.mockReturnValue( false );
			render( <Comp /> );
			expect( receiveOptionsMock ).toHaveBeenLastCalledWith( {
				option3: 'val3',
			} );
			expect( receiveOptionsMock ).toHaveBeenCalledTimes( 3 );
			expect( startResolutionMock ).toHaveBeenCalledTimes( 3 );
		}
	);
	it.each( [
		[ 'useOptionsHydration', TestHookComponent ],
		[ 'withOptionsHydration', TestHigherOrderComponent ],
	] )(
		'%s should not call receiveOptions and startResolution when options have been received',
		( name, Comp ) => {
			isResolvingMock.mockReturnValue( false );
			hasFinishedMock.mockReturnValue( true );
			render( <Comp /> );
			expect( receiveOptionsMock ).not.toHaveBeenLastCalledWith( {
				option3: 'val3',
			} );
			expect( receiveOptionsMock ).toHaveBeenCalledTimes( 0 );
			expect( startResolutionMock ).toHaveBeenCalledTimes( 0 );
		}
	);
} );
