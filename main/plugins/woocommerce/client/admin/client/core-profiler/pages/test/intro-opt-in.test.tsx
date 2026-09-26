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
		context: Pick<
			CoreProfilerStateMachineContext,
			'optInDataSharing' | 'userProfile' | 'coreProfilerCompletedSteps'
		>;
	};

	beforeEach( () => {
		props = {
			sendEvent: jest.fn(),
			navigationProgress: 0,
			context: {
				optInDataSharing: true,
				userProfile:
					undefined as unknown as typeof props.context.userProfile,
				coreProfilerCompletedSteps: {},
			},
		};
	} );

	it( 'should render intro-opt-in page', () => {
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

	it( 'checkbox should be checked when optInDataSharing is true', () => {
		render( <IntroOptIn { ...props } /> );
		expect( screen.getByRole( 'checkbox' ) ).toBeChecked();
	} );

	it( 'checkbox should be checked if user has completed profiler and opted in', () => {
		const newProps = {
			...props,
			context: {
				optInDataSharing: true,
				userProfile: {
					completed: true,
				},
			},
		};
		render( <IntroOptIn { ...newProps } /> );
		expect( screen.getByRole( 'checkbox' ) ).toBeChecked();
	} );

	it( 'checkbox should be unchecked when user has completed profiler and opted out', () => {
		const newProps = {
			...props,
			context: {
				optInDataSharing: false,
				userProfile: {
					completed: true,
				},
			},
		};
		render( <IntroOptIn { ...newProps } /> );
		expect( screen.getByRole( 'checkbox' ) ).not.toBeChecked();
	} );

	it( 'checkbox should be unchecked if user has completed intro opt in step previously and opted out', () => {
		const newProps = {
			...props,
			context: {
				optInDataSharing: false,
				coreProfilerCompletedSteps: {
					'intro-opt-in': {
						completed_at: 1,
					},
				},
			},
		};
		render( <IntroOptIn { ...newProps } /> );
		expect( screen.getByRole( 'checkbox' ) ).not.toBeChecked();
	} );

	it( 'checkbox should be checked if user has not completed profiler', () => {
		const newProps = {
			...props,
			context: {
				...props.context,
				userProfile: undefined,
			} as unknown as Pick<
				CoreProfilerStateMachineContext,
				'optInDataSharing' | 'userProfile'
			>,
		};
		render( <IntroOptIn { ...newProps } /> );
		expect( screen.getByRole( 'checkbox' ) ).toBeChecked();
	} );

	it( 'should toggle checkbox when checkbox is clicked', () => {
		render( <IntroOptIn { ...props } /> );
		screen.getByRole( 'checkbox' ).click();
		expect( screen.getByRole( 'checkbox' ) ).not.toBeChecked();
	} );

	it( 'should call sendEvent with INTRO_COMPLETED event when button is clicked', () => {
		render( <IntroOptIn { ...props } /> );
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

	it( 'should call sendEvent with INTRO_SKIPPED event and optInDataSharing: true when skip button is clicked and the checkbox is checked', () => {
		render( <IntroOptIn { ...props } /> );
		expect( screen.getByRole( 'checkbox' ) ).toBeChecked();
		screen
			.getByRole( 'button', {
				name: /Skip guided setup/i,
			} )
			.click();
		expect( props.sendEvent ).toHaveBeenCalledWith( {
			type: 'INTRO_SKIPPED',
			payload: { optInDataSharing: true },
		} );
	} );

	it( 'should call sendEvent with INTRO_SKIPPED event and optInDataSharing: false when skip button is clicked and the checkbox is unchecked', () => {
		render( <IntroOptIn { ...props } /> );
		screen.getByRole( 'checkbox' ).click();
		expect( screen.getByRole( 'checkbox' ) ).not.toBeChecked();
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
