/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { DismissableList, DismissableListHeading } from '../dismissable-list';

describe( 'DismissableList', () => {
	it( 'renders its children when isDismissed is false', () => {
		render(
			<DismissableList isDismissed={ false }>
				<span>dismissible children</span>
			</DismissableList>
		);

		expect(
			screen.queryByText( 'dismissible children' )
		).toBeInTheDocument();
	} );

	it( 'renders its children when isDismissed is omitted', () => {
		render(
			<DismissableList>
				<span>dismissible children</span>
			</DismissableList>
		);

		expect(
			screen.queryByText( 'dismissible children' )
		).toBeInTheDocument();
	} );

	it( 'renders nothing when isDismissed is true', () => {
		render(
			<DismissableList isDismissed={ true }>
				<span>dismissible children</span>
			</DismissableList>
		);

		expect(
			screen.queryByText( 'dismissible children' )
		).not.toBeInTheDocument();
	} );
} );

describe( 'DismissableListHeading', () => {
	it( 'renders its children', () => {
		render(
			<DismissableListHeading>
				<span>heading content</span>
			</DismissableListHeading>
		);

		expect( screen.queryByText( 'heading content' ) ).toBeInTheDocument();
	} );

	it( 'calls onDismiss when "Hide this" is clicked', () => {
		const onDismiss = jest.fn();
		render(
			<DismissableListHeading onDismiss={ onDismiss }>
				heading content
			</DismissableListHeading>
		);

		userEvent.click( screen.getByTitle( 'Task List Options' ) );
		userEvent.click( screen.getByText( 'Hide this' ) );

		expect( onDismiss ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'does not throw when "Hide this" is clicked without an onDismiss prop', () => {
		render(
			<DismissableListHeading>heading content</DismissableListHeading>
		);

		userEvent.click( screen.getByTitle( 'Task List Options' ) );

		expect( () =>
			userEvent.click( screen.getByText( 'Hide this' ) )
		).not.toThrow();
	} );
} );
