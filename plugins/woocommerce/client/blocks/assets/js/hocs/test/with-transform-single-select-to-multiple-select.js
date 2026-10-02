/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import withTransformSingleSelectToMultipleSelect from '../with-transform-single-select-to-multiple-select';

const InnerComponent = jest.fn( () => null );
const TestComponent =
	withTransformSingleSelectToMultipleSelect( InnerComponent );

const getReceivedProps = () =>
	InnerComponent.mock.calls[ InnerComponent.mock.calls.length - 1 ][ 0 ];

describe( 'withTransformSingleSelectToMultipleSelect Component', () => {
	beforeEach( () => {
		InnerComponent.mockClear();
	} );

	describe( 'when the API returns an error', () => {
		it( 'converts the selected value into an array', () => {
			const selected = 123;
			render( <TestComponent selected={ selected } /> );
			expect( getReceivedProps().selected ).toEqual( [ selected ] );
		} );

		it( 'passes an empty array as the selected prop if selected was null', () => {
			render( <TestComponent selected={ null } /> );
			expect( getReceivedProps().selected ).toEqual( [] );
		} );
	} );
} );
