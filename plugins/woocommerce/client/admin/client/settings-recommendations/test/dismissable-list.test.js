/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { speak } from '@wordpress/a11y';

/**
 * Internal dependencies
 */
import { DismissableList, DismissableListHeading } from '../dismissable-list';

jest.mock( '@wordpress/a11y', () => ( {
	speak: jest.fn(),
} ) );

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

	describe( 'dismissal accessibility', () => {
		beforeEach( () => {
			speak.mockClear();
		} );

		it( 'announces and moves focus to a stable element when dismissed', () => {
			const { container, rerender } = render(
				<DismissableList isDismissed={ false }>
					<span>dismissible children</span>
				</DismissableList>
			);

			const wrapper = container.querySelector(
				'.woocommerce-dismissable-list__wrapper'
			);

			rerender(
				<DismissableList isDismissed={ true }>
					<span>dismissible children</span>
				</DismissableList>
			);

			expect( speak ).toHaveBeenCalledWith(
				'Recommendation hidden.',
				'assertive'
			);
			// The wrapper stays mounted so focus has somewhere to land.
			expect( wrapper ).toBeInTheDocument();
			expect( wrapper ).toHaveFocus();
		} );

		it( 'does not announce or steal focus when dismissed on first render', () => {
			render(
				<DismissableList isDismissed={ true }>
					<span>dismissible children</span>
				</DismissableList>
			);

			expect( speak ).not.toHaveBeenCalled();
			expect( document.body ).toHaveFocus();
		} );
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
