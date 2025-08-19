/**
 * External dependencies
 */
import { render, fireEvent, screen } from '@testing-library/react';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { WooPaymentsUpdateRequiredModal } from '..';

// Mock getAdminLink
jest.mock( '@woocommerce/settings', () => ( {
	getAdminLink: jest.fn(),
} ) );

// Mock window.location
const mockLocation = {
	href: '',
};
Object.defineProperty( window, 'location', {
	value: mockLocation,
	writable: true,
} );

describe( 'WooPaymentsUpdateRequiredModal', () => {
	const defaultProps = {
		isOpen: true,
		onClose: jest.fn(),
	};

	beforeEach( () => {
		jest.clearAllMocks();
		( getAdminLink as jest.Mock ).mockReturnValue(
			'/wp-admin/plugins.php'
		);
		mockLocation.href = '';
	} );

	it( 'should render modal when isOpen is true', () => {
		render( <WooPaymentsUpdateRequiredModal { ...defaultProps } /> );

		expect(
			screen.getByRole( 'dialog', {
				name: 'An update to WooPayments is required',
			} )
		).toBeInTheDocument();
	} );

	it( 'should not render modal when isOpen is false', () => {
		render(
			<WooPaymentsUpdateRequiredModal
				{ ...defaultProps }
				isOpen={ false }
			/>
		);

		expect(
			screen.queryByRole( 'dialog', {
				name: 'An update to WooPayments is required',
			} )
		).not.toBeInTheDocument();
	} );

	it( 'should display correct modal title', () => {
		render( <WooPaymentsUpdateRequiredModal { ...defaultProps } /> );

		expect(
			screen.getByText( 'An update to WooPayments is required' )
		).toBeInTheDocument();
	} );

	it( 'should display correct modal content', () => {
		render( <WooPaymentsUpdateRequiredModal { ...defaultProps } /> );

		expect(
			screen.getByText(
				/To continue, please update your WooPayments plugin to the latest version/
			)
		).toBeInTheDocument();
	} );

	it( 'should render "Update WooPayments" and "Not now" buttons', () => {
		render( <WooPaymentsUpdateRequiredModal { ...defaultProps } /> );

		expect(
			screen.getByRole( 'button', { name: 'Update WooPayments' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Not now' } )
		).toBeInTheDocument();
	} );

	it( 'should call onClose when "Not now" button is clicked', () => {
		const onClose = jest.fn();
		render(
			<WooPaymentsUpdateRequiredModal
				{ ...defaultProps }
				onClose={ onClose }
			/>
		);

		const notNowButton = screen.getByRole( 'button', { name: 'Not now' } );
		fireEvent.click( notNowButton );

		expect( onClose ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'should navigate to plugins page when "Update WooPayments" button is clicked', () => {
		render( <WooPaymentsUpdateRequiredModal { ...defaultProps } /> );

		const updateButton = screen.getByRole( 'button', {
			name: 'Update WooPayments',
		} );
		fireEvent.click( updateButton );

		expect( getAdminLink ).toHaveBeenCalledWith( 'plugins.php' );
		expect( mockLocation.href ).toBe( '/wp-admin/plugins.php' );
	} );

	it( 'should show loading state on "Update WooPayments" button when clicked', () => {
		render( <WooPaymentsUpdateRequiredModal { ...defaultProps } /> );

		const updateButton = screen.getByRole( 'button', {
			name: 'Update WooPayments',
		} );

		expect( updateButton ).not.toHaveClass( 'is-busy' );

		fireEvent.click( updateButton );

		expect( updateButton ).toHaveClass( 'is-busy' );
	} );

	it( 'should disable "Not now" button when updating', () => {
		render( <WooPaymentsUpdateRequiredModal { ...defaultProps } /> );

		const updateButton = screen.getByRole( 'button', {
			name: 'Update WooPayments',
		} );
		const notNowButton = screen.getByRole( 'button', { name: 'Not now' } );

		expect( notNowButton ).not.toBeDisabled();

		fireEvent.click( updateButton );

		expect( notNowButton ).toBeDisabled();
	} );
} );
