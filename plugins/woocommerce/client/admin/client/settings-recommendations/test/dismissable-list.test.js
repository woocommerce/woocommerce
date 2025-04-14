/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useDispatch, useSelect } from '@wordpress/data';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { DismissableList, DismissableListHeading } from '../dismissable-list';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	useDispatch: jest.fn(),
} ) );

const DismissableListMock = ( { children } ) => (
	<DismissableList dismissOptionName="dismissable_option_mock">
		{ children }
		<span>dismissible children</span>
	</DismissableList>
);

describe( 'DismissableList', () => {
	beforeEach( () => {
		useSelect.mockImplementation( ( fn ) =>
			fn( () => ( {
				getOption: () => false,
				hasFinishedResolution: () => true,
			} ) )
		);
		useDispatch.mockReturnValue( { updateOptions: () => null } );
	} );

	it( 'should not render its children when the option is not resolved', () => {
		useSelect.mockImplementation( ( fn ) =>
			fn( () => ( {
				getOption: () => false,
				hasFinishedResolution: () => false,
			} ) )
		);
		render( <DismissableListMock /> );

		expect(
			screen.queryByText( 'dismissible children' )
		).not.toBeInTheDocument();
	} );

	it( 'should not render its children when the option is dismissed', () => {
		useSelect.mockImplementation( ( fn ) =>
			fn( () => ( {
				getOption: () => 'yes',
				hasFinishedResolution: () => true,
			} ) )
		);
		render( <DismissableListMock /> );

		expect(
			screen.queryByText( 'dismissible children' )
		).not.toBeInTheDocument();
	} );

	it( 'render its children', () => {
		render( <DismissableListMock /> );

		expect(
			screen.queryByText( 'dismissible children' )
		).toBeInTheDocument();
	} );

	it( 'should allow dismissing the option through the DismissableListHeading component', () => {
		const handleDismissMock = jest.fn();
		const updateOptionsMock = jest.fn();
		useDispatch.mockReturnValue( { updateOptions: updateOptionsMock } );
		render(
			<DismissableListMock>
				<DismissableListHeading onDismiss={ handleDismissMock }>
					heading content mock
				</DismissableListHeading>
			</DismissableListMock>
		);

		expect(
			screen.queryByText( 'heading content mock' )
		).toBeInTheDocument();

		userEvent.click( screen.getByTitle( 'Task List Options' ) );
		userEvent.click( screen.getByText( 'Hide this' ) );

		expect( handleDismissMock ).toHaveBeenCalled();
		expect( updateOptionsMock ).toHaveBeenCalledWith(
			expect.objectContaining( {
				dismissable_option_mock: 'yes',
			} )
		);
	} );
} );
