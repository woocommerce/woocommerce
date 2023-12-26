/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { IntroOptIn } from '../IntroOptIn';
import { CoreProfilerStateMachineContext } from '../../';

describe( 'IntroOptIn', () => {
	let props: {
		sendEvent: jest.Mock;
		navigationProgress: number;
		context: Pick< CoreProfilerStateMachineContext, 'optInDataSharing' >;
	};

	beforeEach( () => {
		props = {
			sendEvent: jest.fn(),
			navigationProgress: 0,
			context: {
				optInDataSharing: true,
			},
		};
	} );

	it( 'should render intro-opt-in page', () => {
		// @ts-ignore
		render( <IntroOptIn { ...props } /> );
		expect( screen.getByText( /Welcome to Woo!/i ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', {
				name: /Set up my store/i,
			} )
		).toBeInTheDocument();
		// should render opt-in checkbox
		expect( screen.getByRole( 'checkbox' ) ).toBeInTheDocument();
	} );

	it( 'should checkbox be checked when optInDataSharing is true', () => {
		render(
			// @ts-ignore
			<IntroOptIn { ...props } />
		);
		expect( screen.getByRole( 'checkbox' ) ).toBeChecked();
	} );

	it( 'should toggle checkbox when checkbox is clicked', () => {
		render(
			// @ts-ignore
			<IntroOptIn { ...props } />
		);
		screen.getByRole( 'checkbox' ).click();
		expect( screen.getByRole( 'checkbox' ) ).not.toBeChecked();
	} );

	it( 'should call sendEvent with INTRO_COMPLETED event when button is clicked', () => {
		render(
			// @ts-ignore
			<IntroOptIn { ...props } />
		);
		screen
			.getByRole( 'button', {
				name: /Set up my store/i,
			} )
			.click();
		expect( props.sendEvent ).toHaveBeenCalledWith( {
			type: 'INTRO_COMPLETED',
			payload: { optInDataSharing: true },
		} );
	} );

	it( 'should call sendEvent with INTRO_SKIPPED event when skip button is clicked', () => {
		render(
			// @ts-ignore
			<IntroOptIn { ...props } />
		);
		screen
			.getByRole( 'button', {
				name: /Skip guided setup/i,
			} )
			.click();
		expect( props.sendEvent ).toHaveBeenCalledWith( {
			type: 'INTRO_SKIPPED',
			payload: { optInDataSharing: false },
		} );
	} );
} );
