import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';

/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { render, screen, act } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { IntroOptIn } from '../IntroOptIn';
import { CoreProfilerStateMachineContext } from '../../';
describe( 'IntroOptIn', () => {
	let props: {
		sendEvent: Mock;
		navigationProgress: number;
		context: Pick<
			CoreProfilerStateMachineContext,
			'optInDataSharing' | 'userProfile' | 'coreProfilerCompletedSteps'
		>;
	};
	beforeEach( () => {
		props = {
			sendEvent: vi.fn(),
			navigationProgress: 0,
			context: {
				optInDataSharing: true,
				userProfile:
					undefined as unknown as typeof props.context.userProfile,
				coreProfilerCompletedSteps: {},
			},
		};
	} );
	it( 'should render intro-opt-in page', async () => {
		await act( async () => {
			render( <IntroOptIn { ...props } /> );
		} );
		expect( screen.getByText( /Welcome to Woo!/i ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', {
				name: /Set up my store/i,
			} )
		).toBeInTheDocument();
		// should render opt-in checkbox
		expect( screen.getByRole( 'checkbox' ) ).toBeInTheDocument();
	} );
	it( 'checkbox should be checked when optInDataSharing is true', async () => {
		await act( async () => {
			render( <IntroOptIn { ...props } /> );
		} );
		expect( screen.getByRole( 'checkbox' ) ).toBeChecked();
	} );
	it( 'checkbox should be checked if user has completed profiler and opted in', async () => {
		const newProps = {
			...props,
			context: {
				optInDataSharing: true,
				userProfile: {
					completed: true,
				},
			},
		};
		await act( async () => {
			render( <IntroOptIn { ...newProps } /> );
		} );
		expect( screen.getByRole( 'checkbox' ) ).toBeChecked();
	} );
	it( 'checkbox should be unchecked when user has completed profiler and opted out', async () => {
		const newProps = {
			...props,
			context: {
				optInDataSharing: false,
				userProfile: {
					completed: true,
				},
			},
		};
		await act( async () => {
			render( <IntroOptIn { ...newProps } /> );
		} );
		expect( screen.getByRole( 'checkbox' ) ).not.toBeChecked();
	} );
	it( 'checkbox should be unchecked if user has completed intro opt in step previously and opted out', async () => {
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
		await act( async () => {
			render( <IntroOptIn { ...newProps } /> );
		} );
		expect( screen.getByRole( 'checkbox' ) ).not.toBeChecked();
	} );
	it( 'checkbox should be checked if user has not completed profiler', async () => {
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
		await act( async () => {
			render( <IntroOptIn { ...newProps } /> );
		} );
		expect( screen.getByRole( 'checkbox' ) ).toBeChecked();
	} );
	it( 'should toggle checkbox when checkbox is clicked', async () => {
		await act( async () => {
			render( <IntroOptIn { ...props } /> );
		} );
		await act( async () => {
			screen.getByRole( 'checkbox' ).click();
		} );
		expect( screen.getByRole( 'checkbox' ) ).not.toBeChecked();
	} );
	it( 'should call sendEvent with INTRO_COMPLETED event when button is clicked', async () => {
		await act( async () => {
			render( <IntroOptIn { ...props } /> );
		} );
		screen
			.getByRole( 'button', {
				name: /Set up my store/i,
			} )
			.click();
		expect( props.sendEvent ).toHaveBeenCalledWith( {
			type: 'INTRO_COMPLETED',
			payload: {
				optInDataSharing: true,
			},
		} );
	} );
	it( 'should call sendEvent with INTRO_SKIPPED event and optInDataSharing: true when skip button is clicked and the checkbox is checked', async () => {
		await act( async () => {
			render( <IntroOptIn { ...props } /> );
		} );
		expect( screen.getByRole( 'checkbox' ) ).toBeChecked();
		screen
			.getByRole( 'button', {
				name: /Skip guided setup/i,
			} )
			.click();
		expect( props.sendEvent ).toHaveBeenCalledWith( {
			type: 'INTRO_SKIPPED',
			payload: {
				optInDataSharing: true,
			},
		} );
	} );
	it( 'should call sendEvent with INTRO_SKIPPED event and optInDataSharing: false when skip button is clicked and the checkbox is unchecked', async () => {
		await act( async () => {
			render( <IntroOptIn { ...props } /> );
		} );
		await act( async () => {
			screen.getByRole( 'checkbox' ).click();
		} );
		expect( screen.getByRole( 'checkbox' ) ).not.toBeChecked();
		screen
			.getByRole( 'button', {
				name: /Skip guided setup/i,
			} )
			.click();
		expect( props.sendEvent ).toHaveBeenCalledWith( {
			type: 'INTRO_SKIPPED',
			payload: {
				optInDataSharing: false,
			},
		} );
	} );
} );
