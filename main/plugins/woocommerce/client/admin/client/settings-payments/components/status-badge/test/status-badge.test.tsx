/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { StatusBadge } from '../status-badge';

describe( 'StatusBadge component', () => {
	it.each( [
		[ 'Enter', 'Enter' ],
		[ 'Space', ' ' ],
	] )(
		'allows %s on a popover link without preventing its default action',
		async ( _name, key ) => {
			render(
				<StatusBadge
					status="not_supported"
					popoverContent={
						<a href="https://example.test/">Learn more</a>
					}
				/>
			);
			fireEvent.click(
				screen.getByRole( 'button', { name: 'More information' } )
			);
			const link = await screen.findByRole( 'link', {
				name: 'Learn more',
			} );
			link.focus();
			const event = new window.KeyboardEvent( 'keydown', {
				key,
				bubbles: true,
				cancelable: true,
			} );

			fireEvent( link, event );

			expect( event.defaultPrevented ).toBe( false );
			expect( link ).toBeInTheDocument();
		}
	);

	it( 'opens with Enter and Space and returns focus to the trigger on Escape', async () => {
		render(
			<StatusBadge
				status="not_supported"
				popoverContent={
					<a href="https://example.test/">Learn more</a>
				}
			/>
		);
		const trigger = screen.getByRole( 'button', {
			name: 'More information',
		} );
		trigger.focus();
		fireEvent.keyDown( trigger, { key: 'Enter' } );
		const link = await screen.findByRole( 'link', { name: 'Learn more' } );
		link.focus();

		fireEvent.keyDown( link, { key: 'Escape' } );

		expect(
			screen.queryByRole( 'link', { name: 'Learn more' } )
		).not.toBeInTheDocument();
		expect( trigger ).toHaveFocus();
		fireEvent.keyDown( trigger, { key: ' ' } );
		expect(
			await screen.findByRole( 'link', { name: 'Learn more' } )
		).toBeInTheDocument();
	} );

	it( 'renders the correct message for active status', () => {
		const { getByText } = render( <StatusBadge status="active" /> );
		expect( getByText( 'Active' ) ).toBeInTheDocument();
	} );

	it( 'renders the correct message for inactive status', () => {
		const { getByText } = render( <StatusBadge status="inactive" /> );
		expect( getByText( 'Inactive' ) ).toBeInTheDocument();
	} );

	it( 'renders the correct message for needs_setup status', () => {
		const { getByText } = render( <StatusBadge status="needs_setup" /> );
		expect( getByText( 'Action needed' ) ).toBeInTheDocument();
	} );

	it( 'renders the correct message for test_mode status', () => {
		const { getByText } = render( <StatusBadge status="test_mode" /> );
		expect( getByText( 'Test mode' ) ).toBeInTheDocument();
	} );

	it( 'renders the correct message for recommended status', () => {
		const { getByText } = render( <StatusBadge status="recommended" /> );
		expect( getByText( 'Recommended' ) ).toBeInTheDocument();
	} );

	it( 'renders the correct message for has_incentive status', () => {
		const { getByText } = render(
			<StatusBadge status="has_incentive" message={ 'Custom message' } />
		);
		expect( getByText( 'Custom message' ) ).toBeInTheDocument();
	} );

	it( 'renders the correct message for not_supported status', () => {
		const { getByText } = render( <StatusBadge status="not_supported" /> );
		expect( getByText( 'Not supported' ) ).toBeInTheDocument();
	} );

	it( 'applies the correct class for success statuses', () => {
		const { container } = render( <StatusBadge status="active" /> );
		expect( container.firstChild ).toHaveClass(
			'woocommerce-status-badge--success'
		);
	} );

	it( 'applies the correct class for warning statuses', () => {
		const { container } = render( <StatusBadge status="needs_setup" /> );
		expect( container.firstChild ).toHaveClass(
			'woocommerce-status-badge--warning'
		);
	} );

	it( 'applies the correct class for not_supported status', () => {
		const { container } = render( <StatusBadge status="not_supported" /> );
		expect( container.firstChild ).toHaveClass(
			'woocommerce-status-badge--warning'
		);
	} );

	it( 'applies the correct class for info statuses', () => {
		const { container } = render( <StatusBadge status="recommended" /> );
		expect( container.firstChild ).toHaveClass(
			'woocommerce-status-badge--info'
		);
	} );

	it( 'renders the correct custom message when message prop is passed', () => {
		const customMessage = 'Custom message';
		const { getByText } = render(
			<StatusBadge status="active" message={ customMessage } />
		);
		expect( getByText( customMessage ) ).toBeInTheDocument();
	} );
} );
