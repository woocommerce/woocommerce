/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { JetpackCTA } from '../install-jetpack-cta';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

describe( 'JetpackCTA', () => {
	it( 'shows buttons as busy and disabled when isBusy is true', () => {
		const { queryAllByRole } = render(
			<JetpackCTA
				onClickInstall={ () => {} }
				onClickDismiss={ () => {} }
				isBusy={ true }
				jetpackInstallState={ '' }
			/>
		);

		const buttons = queryAllByRole( 'button' );

		expect( buttons[ 0 ] ).toHaveClass( 'is-busy' );
		expect( buttons[ 1 ] ).toHaveClass( 'is-busy' );
		expect( buttons[ 0 ] ).toHaveAttribute( 'disabled' );
		expect( buttons[ 1 ] ).toHaveAttribute( 'disabled' );
	} );

	it( 'shows buttons as not busy and enabled when isBusy is false', () => {
		const { queryAllByRole } = render(
			<JetpackCTA
				onClickInstall={ () => {} }
				onClickDismiss={ () => {} }
				isBusy={ false }
				jetpackInstallState={ '' }
			/>
		);

		const buttons = queryAllByRole( 'button' );

		expect( buttons[ 0 ] ).not.toHaveClass( 'is-busy' );
		expect( buttons[ 1 ] ).not.toHaveClass( 'is-busy' );
		expect( buttons[ 0 ] ).not.toHaveAttribute( 'disabled' );
		expect( buttons[ 1 ] ).not.toHaveAttribute( 'disabled' );
	} );

	it( 'calls the onClickInstall handler and records a track when the install button is clicked', () => {
		const onClickInstallSpy = jest.fn();
		const { queryAllByRole } = render(
			<JetpackCTA
				onClickInstall={ onClickInstallSpy }
				onClickDismiss={ () => {} }
				isBusy={ false }
				jetpackInstallState={ '' }
			/>
		);

		const installButton = queryAllByRole( 'button' )[ 0 ];

		fireEvent.click( installButton );

		expect( recordEvent ).toHaveBeenCalledWith(
			'statsoverview_install_jetpack'
		);

		expect( onClickInstallSpy ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'calls the onClickDismiss handler and records a track when the dismiss button is clicked', () => {
		const onClickDismissSpy = jest.fn();
		const { queryAllByRole } = render(
			<JetpackCTA
				onClickInstall={ () => {} }
				onClickDismiss={ onClickDismissSpy }
				isBusy={ false }
				jetpackInstallState={ '' }
			/>
		);

		const dismissButton = queryAllByRole( 'button' )[ 1 ];

		fireEvent.click( dismissButton );

		expect( recordEvent ).toHaveBeenCalledWith(
			'statsoverview_dismiss_install_jetpack'
		);

		expect( onClickDismissSpy ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'displays text based on the install status of Jetpack', () => {
		const { queryByText, rerender } = render(
			<JetpackCTA
				onClickInstall={ () => {} }
				onClickDismiss={ () => {} }
				isBusy={ false }
				jetpackInstallState={ 'unavailable' }
			/>
		);

		expect( queryByText( 'Get Jetpack' ) ).toBeInTheDocument();

		rerender(
			<JetpackCTA
				onClickInstall={ () => {} }
				onClickDismiss={ () => {} }
				isBusy={ false }
				jetpackInstallState={ 'installed' }
			/>
		);

		expect( queryByText( 'Activate Jetpack' ) ).toBeInTheDocument();

		rerender(
			<JetpackCTA
				onClickInstall={ () => {} }
				onClickDismiss={ () => {} }
				isBusy={ false }
				jetpackInstallState={ 'activated' }
			/>
		);

		expect( queryByText( 'Connect Jetpack' ) ).toBeInTheDocument();
	} );
} );
