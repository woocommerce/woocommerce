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

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	useDispatch: jest.fn(),
} ) );

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
	const isResolvingMock = jest.fn();
	const hasFinishedMock = jest.fn();
	const startResolutionMock = jest.fn();
	const receiveOptionsMock = jest.fn();
	beforeEach( () => {
		( useSelect as jest.Mock ).mockImplementation( ( callback ) => {
			return callback( () => ( {
				isResolving: isResolvingMock,
				hasFinishedResolution: hasFinishedMock,
			} ) );
		} );
		( useDispatch as jest.Mock ).mockImplementation( () => ( {
			startResolution: startResolutionMock,
			finishResolution: jest.fn(),
			receiveOptions: receiveOptionsMock,
		} ) );
	} );

	afterEach( () => {
		jest.clearAllMocks();
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
