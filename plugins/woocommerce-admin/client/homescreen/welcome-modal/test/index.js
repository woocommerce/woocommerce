/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { WelcomeModal } from '../index';

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );

describe( 'WelcomeModal', () => {
	it( 'should call onClose when it is closed', () => {
		const onCloseSpy = jest.fn();

		const { queryAllByRole } = render(
			<WelcomeModal onClose={ onCloseSpy } />
		);

		fireEvent.click( queryAllByRole( 'button' )[ 0 ] );

		expect( onCloseSpy ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'should not render the guide after it is closed', () => {
		const { queryAllByRole, container } = render(
			<WelcomeModal onClose={ () => {} } />
		);

		fireEvent.click( queryAllByRole( 'button' )[ 0 ] );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'should track open and close', () => {
		const { queryAllByRole } = render(
			<WelcomeModal onClose={ () => {} } />
		);

		expect( recordEvent ).toHaveBeenLastCalledWith(
			'task_list_welcome_modal_open'
		);

		fireEvent.click( queryAllByRole( 'button' )[ 0 ] );

		expect( recordEvent ).toHaveBeenLastCalledWith(
			'task_list_welcome_modal_close'
		);
	} );
} );
