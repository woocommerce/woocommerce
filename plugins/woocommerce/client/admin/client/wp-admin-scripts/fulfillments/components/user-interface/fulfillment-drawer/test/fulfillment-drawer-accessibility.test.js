/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import '../../../../test-helper/global-mock';
import FulfillmentDrawer from '../fulfillment-drawer';

// Mock the drawer context and components that the drawer depends on
jest.mock( '../../../../context/drawer-context', () => ( {
	FulfillmentDrawerProvider: ( { children } ) => (
		<div data-testid="drawer-provider">{ children }</div>
	),
} ) );

jest.mock( '../fulfillment-drawer-header', () => {
	return function MockHeader( { onClose } ) {
		return (
			<div data-testid="drawer-header">
				<h2 id="fulfillment-drawer-header">Test Header</h2>
				<button
					onClick={ onClose }
					aria-label="Close fulfillment drawer"
				>
					×
				</button>
			</div>
		);
	};
} );

jest.mock( '../fulfillment-drawer-body', () => {
	return function MockBody( { children } ) {
		return <div data-testid="drawer-body">{ children }</div>;
	};
} );

jest.mock( '../../../fulfillments/new-fulfillment-form', () => {
	return function MockForm() {
		return (
			<div data-testid="new-fulfillment-form">
				<button data-testid="first-button">First</button>
				<input data-testid="middle-input" type="text" />
				<button data-testid="last-button">Last</button>
			</div>
		);
	};
} );

jest.mock( '../../../fulfillments/fulfillments-list', () => {
	return function MockList() {
		return <div data-testid="fulfillments-list">List</div>;
	};
} );

describe( 'FulfillmentDrawer Accessibility', () => {
	const defaultProps = {
		isOpen: true,
		onClose: jest.fn(),
		orderId: 123,
	};

	beforeEach( () => {
		jest.clearAllMocks();

		// Mock requestAnimationFrame for focus management tests
		let rafCounter = 0;
		jest.spyOn( window, 'requestAnimationFrame' ).mockImplementation(
			( cb ) => {
				const id = ++rafCounter;
				cb( 0 );
				return id;
			}
		);
		jest.spyOn( window, 'cancelAnimationFrame' ).mockImplementation(
			() => {}
		);
	} );

	afterEach( () => {
		window.requestAnimationFrame.mockRestore();
		window.cancelAnimationFrame.mockRestore();
	} );

	it( 'should have proper ARIA attributes when open', () => {
		render( <FulfillmentDrawer { ...defaultProps } /> );

		const dialog = screen.getByRole( 'dialog' );
		expect( dialog ).toHaveAttribute( 'aria-modal', 'true' );
		expect( dialog ).toHaveAttribute(
			'aria-labelledby',
			'fulfillment-drawer-header'
		);
		expect( dialog ).not.toHaveAttribute( 'aria-label' );
		expect( dialog ).toHaveAttribute( 'aria-hidden', 'false' );
	} );

	it( 'should be properly hidden when closed', () => {
		render( <FulfillmentDrawer { ...defaultProps } isOpen={ false } /> );

		const dialog = screen.getByRole( 'dialog', { hidden: true } );
		expect( dialog ).toHaveAttribute( 'aria-hidden', 'true' );
	} );

	it( 'should close on Escape key press', () => {
		const onClose = jest.fn();
		render( <FulfillmentDrawer { ...defaultProps } onClose={ onClose } /> );

		fireEvent.keyDown( document, { key: 'Escape' } );
		expect( onClose ).toHaveBeenCalled();
	} );

	it( 'should have close button with proper aria-label', () => {
		render( <FulfillmentDrawer { ...defaultProps } /> );

		const closeButton = screen.getByLabelText( 'Close fulfillment drawer' );
		expect( closeButton ).toBeInTheDocument();
	} );

	it( 'should have proper backdrop attributes', () => {
		render(
			<FulfillmentDrawer { ...defaultProps } hasBackdrop={ true } />
		);

		const backdrop = document.querySelector(
			'.woocommerce-fulfillment-drawer__backdrop'
		);
		expect( backdrop ).toHaveAttribute( 'role', 'presentation' );
		expect( backdrop ).toHaveAttribute( 'aria-hidden', 'false' );
	} );

	it( 'should allow background scrolling and clicking', () => {
		const originalBodyOverflow = document.body.style.overflow;

		// Render closed drawer
		const { rerender } = render(
			<FulfillmentDrawer { ...defaultProps } isOpen={ false } />
		);

		// Body should remain scrollable when drawer is closed
		expect( document.body.style.overflow ).toBe( '' );

		// Open the drawer
		rerender( <FulfillmentDrawer { ...defaultProps } isOpen={ true } /> );

		// Body should remain scrollable when drawer is open (allows background interaction)
		expect( document.body.style.overflow ).toBe( '' );

		// The drawer panel should be focusable and have proper attributes
		const dialog = screen.getByRole( 'dialog' );
		expect( dialog ).toHaveAttribute( 'tabindex', '-1' );
		expect( dialog ).toHaveClass( 'woocommerce-fulfillment-drawer__panel' );

		// Clean up
		document.body.style.overflow = originalBodyOverflow;
	} );

	describe( 'Focus trapping', () => {
		it( 'should wrap focus from last to first element on Tab', () => {
			render( <FulfillmentDrawer { ...defaultProps } /> );

			const lastButton = screen.getByTestId( 'last-button' );
			lastButton.focus();

			fireEvent.keyDown( document, { key: 'Tab' } );

			// The close button is the first focusable element in the drawer
			const closeButton = screen.getByLabelText(
				'Close fulfillment drawer'
			);
			expect( closeButton.ownerDocument.activeElement ).toBe(
				closeButton
			);
		} );

		it( 'should wrap focus from first to last element on Shift+Tab', () => {
			render( <FulfillmentDrawer { ...defaultProps } /> );

			const closeButton = screen.getByLabelText(
				'Close fulfillment drawer'
			);
			closeButton.focus();

			fireEvent.keyDown( document, { key: 'Tab', shiftKey: true } );

			const lastButton = screen.getByTestId( 'last-button' );
			expect( lastButton.ownerDocument.activeElement ).toBe( lastButton );
		} );

		it( 'should wrap focus from drawer panel to last element on Shift+Tab', () => {
			render( <FulfillmentDrawer { ...defaultProps } /> );

			// The drawer panel itself gets focus when opened
			const dialog = screen.getByRole( 'dialog' );
			dialog.focus();

			fireEvent.keyDown( document, { key: 'Tab', shiftKey: true } );

			const lastButton = screen.getByTestId( 'last-button' );
			expect( lastButton.ownerDocument.activeElement ).toBe( lastButton );
		} );
	} );

	describe( 'Focus restoration', () => {
		it( 'should restore focus to previously focused element when drawer closes', () => {
			const triggerButton = document.createElement( 'button' );
			triggerButton.textContent = 'Open Drawer';
			document.body.appendChild( triggerButton );
			triggerButton.focus();

			const { rerender } = render(
				<FulfillmentDrawer { ...defaultProps } isOpen={ true } />
			);

			// Close the drawer
			rerender(
				<FulfillmentDrawer { ...defaultProps } isOpen={ false } />
			);

			expect( triggerButton.ownerDocument.activeElement ).toBe(
				triggerButton
			);

			// Clean up
			document.body.removeChild( triggerButton );
		} );

		it( 'should not restore focus if previously focused element is disconnected', () => {
			const triggerButton = document.createElement( 'button' );
			triggerButton.textContent = 'Open Drawer';
			document.body.appendChild( triggerButton );
			triggerButton.focus();

			const { rerender } = render(
				<FulfillmentDrawer { ...defaultProps } isOpen={ true } />
			);

			// Remove the trigger button from DOM while drawer is open
			document.body.removeChild( triggerButton );

			// Close the drawer
			rerender(
				<FulfillmentDrawer { ...defaultProps } isOpen={ false } />
			);

			// Focus should NOT be on the disconnected element
			expect( triggerButton.ownerDocument.activeElement ).not.toBe(
				triggerButton
			);
		} );
	} );
} );
