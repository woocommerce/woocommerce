/**
 * External dependencies
 */
import {
	act,
	render,
	screen,
	fireEvent,
	waitFor,
} from '@testing-library/react';

/**
 * Internal dependencies
 */
import { QRLoginNumberMatchStep } from '../QRLoginNumberMatchStep';

// Tracks is fire-and-forget here — we don't assert against it, just keep the
// component's recordEvent calls from blowing up because no global window
// shim is registered in jsdom.
jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

const NOW_SECONDS = 1_700_000_000;
const CHALLENGE_EXPIRES_AT = NOW_SECONDS + 90;

const renderStep = (
	overrides: Partial< Parameters< typeof QRLoginNumberMatchStep >[ 0 ] > = {}
) => {
	const onChooseNumber = jest.fn();
	render(
		<QRLoginNumberMatchStep
			numbers={ [ '317', '042', '589' ] }
			deviceInfo={ {
				model: 'Pixel 10',
				os: 'Android',
				os_version: '16',
				app_version: '24.7.0',
			} }
			challengeExpiresAt={ CHALLENGE_EXPIRES_AT }
			onChooseNumber={ onChooseNumber }
			{ ...overrides }
		/>
	);
	return { onChooseNumber };
};

describe( 'QRLoginNumberMatchStep', () => {
	beforeEach( () => {
		jest.useFakeTimers();
		jest.setSystemTime( NOW_SECONDS * 1000 );
	} );

	afterEach( () => {
		jest.clearAllTimers();
		jest.useRealTimers();
	} );

	it( 'renders the three candidate numbers in the order received from the server', () => {
		renderStep();

		const tiles = screen.getAllByRole( 'button', {
			name: /Confirm with the number/i,
		} );
		expect( tiles ).toHaveLength( 3 );
		expect( tiles[ 0 ] ).toHaveTextContent( '317' );
		expect( tiles[ 1 ] ).toHaveTextContent( '042' );
		expect( tiles[ 2 ] ).toHaveTextContent( '589' );
	} );

	it( 'surfaces device model + OS + app version in the headline', () => {
		renderStep();

		expect(
			screen.getByText( /Pixel 10 · Android 16 · App version 24\.7\.0/ )
		).toBeInTheDocument();
	} );

	it( 'falls back to "Mobile app" when no device info is provided', () => {
		renderStep( { deviceInfo: null } );

		expect(
			screen.getByText( /Match this number on Mobile app/ )
		).toBeInTheDocument();
	} );

	it( 'invokes onChooseNumber with the tapped value', async () => {
		const { onChooseNumber } = renderStep();

		fireEvent.click(
			screen.getByRole( 'button', {
				name: /Confirm with the number 042/i,
			} )
		);

		expect( onChooseNumber ).toHaveBeenCalledWith( '042' );
		await waitFor( () =>
			expect(
				screen.getByRole( 'button', {
					name: /Confirm with the number 042/i,
				} )
			).not.toBeDisabled()
		);
	} );

	/**
	 * One-strike rule: while a click is in flight, all three tiles must be
	 * disabled so a fast double-click can't register as two attempts and
	 * race the state transition on the server side.
	 */
	it( 'disables all three tiles while a click is in flight', async () => {
		let resolveChoice: () => void = () => undefined;
		const onChooseNumber = jest.fn(
			() =>
				new Promise< void >( ( r ) => {
					resolveChoice = r;
				} )
		);
		render(
			<QRLoginNumberMatchStep
				numbers={ [ '317', '042', '589' ] }
				deviceInfo={ null }
				challengeExpiresAt={ CHALLENGE_EXPIRES_AT }
				onChooseNumber={ onChooseNumber }
			/>
		);

		fireEvent.click(
			screen.getByRole( 'button', {
				name: /Confirm with the number 042/i,
			} )
		);

		await waitFor( () => {
			expect(
				screen.getByRole( 'button', {
					name: /Confirm with the number 317/i,
				} )
			).toBeDisabled();
		} );
		expect(
			screen.getByRole( 'button', {
				name: /Confirm with the number 042/i,
			} )
		).toBeDisabled();
		expect(
			screen.getByRole( 'button', {
				name: /Confirm with the number 589/i,
			} )
		).toBeDisabled();

		// Subsequent click on a different tile while in flight is a no-op.
		fireEvent.click(
			screen.getByRole( 'button', {
				name: /Confirm with the number 317/i,
			} )
		);
		expect( onChooseNumber ).toHaveBeenCalledTimes( 1 );

		resolveChoice();
		await waitFor( () =>
			expect(
				screen.getByRole( 'button', {
					name: /Confirm with the number 042/i,
				} )
			).not.toBeDisabled()
		);
	} );

	it( 'cancel-login button calls onChooseNumber with the empty-string sentinel', async () => {
		const { onChooseNumber } = renderStep();

		fireEvent.click(
			screen.getByRole( 'button', { name: /cancel login/i } )
		);

		expect( onChooseNumber ).toHaveBeenCalledWith( '' );
		await waitFor( () =>
			expect(
				screen.getByRole( 'button', { name: /cancel login/i } )
			).not.toBeDisabled()
		);
	} );

	it( 'shows a 90-second countdown that ticks down each second', () => {
		renderStep();

		const countdown = screen.getByText( /Expires in 90s/ );
		expect( countdown ).toBeInTheDocument();
		expect( countdown ).toHaveAttribute( 'aria-live', 'off' );

		// jest.useFakeTimers() with the modern impl ties Date.now() to the
		// fake timer queue, so advancing the timer also advances the wall
		// clock — no separate setSystemTime needed. Wrap in act() so the
		// setSecondsRemaining update from the interval tick flushes before
		// the next assertion runs.
		act( () => {
			jest.advanceTimersByTime( 1000 );
		} );

		expect( screen.getByText( /Expires in 89s/ ) ).toBeInTheDocument();
	} );

	it( 'renders approval errors accessibly', () => {
		renderStep( { errorMessage: 'Approval is already in progress.' } );

		expect( screen.getByRole( 'alert' ) ).toHaveTextContent(
			'Approval is already in progress.'
		);
	} );

	it( 'disables tiles and surfaces an expired message once the challenge window elapses', () => {
		const { onChooseNumber } = renderStep( {
			challengeExpiresAt: NOW_SECONDS,
		} );

		const expiredMessage = screen.getByText(
			/This sign-in attempt has expired/
		);
		expect( expiredMessage ).toBeInTheDocument();
		expect( expiredMessage ).toHaveAttribute( 'aria-live', 'polite' );
		expect(
			screen.getByRole( 'button', {
				name: /Confirm with the number 042/i,
			} )
		).toBeDisabled();
		const cancelButton = screen.getByRole( 'button', {
			name: /cancel login/i,
		} );
		expect( cancelButton ).toBeDisabled();

		fireEvent.click( cancelButton );
		expect( onChooseNumber ).not.toHaveBeenCalled();
	} );
} );
