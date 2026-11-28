/**
 * External dependencies
 */
import { render, fireEvent, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { WooPaymentsUpdateRequiredModal } from '..';

describe( 'WooPaymentsUpdateRequiredModal', () => {
	const defaultProps = {
		isOpen: true,
		onClose: jest.fn(),
	};

	beforeEach( () => {
		jest.clearAllMocks();
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
} );
