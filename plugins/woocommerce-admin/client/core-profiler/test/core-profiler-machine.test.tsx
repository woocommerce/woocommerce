/**
 * External dependencies
 */
import {
	fireEvent,
	render,
	RenderResult,
	waitFor,
} from '@testing-library/react';
import { createModel } from '@xstate/test';
import { createMachine } from 'xstate';
/**
 * Internal dependencies
 */
import { preFetchActions, CoreProfilerController } from '../';
import recordTracksActions from '../actions/tracks';

const preFetchActionsMocks = Object.fromEntries(
	Object.entries( preFetchActions ).map( ( [ key ] ) => [ key, jest.fn() ] )
);

const recordTracksActionsMocks = Object.fromEntries(
	Object.entries( recordTracksActions ).map( ( [ key ] ) => [
		key,
		jest.fn(),
	] )
);

// mock out the external dependencies which we don't want to test here
const actionOverrides = {
	...preFetchActionsMocks,
	...recordTracksActionsMocks,
	updateQueryStep: jest.fn(),
	updateTrackingOption: jest.fn(),
	updateOnboardingProfileOption: jest.fn(),
	redirectToWooHome: jest.fn(),
};

const servicesOverrides = {
	getAllowTrackingOption: jest.fn().mockResolvedValue( 'yes' ),
	getCountries: jest
		.fn()
		.mockResolvedValue( [
			{ code: 'US', name: 'United States', states: [] },
		] ),
	getGeolocation: jest.fn().mockResolvedValue( {} ),
	getOnboardingProfileOption: jest.fn().mockResolvedValue( {} ),
};

/**
 *  Test model's machine is not the application's model!
 *  In other words, the events and states don't have to match up.
 *  We can insert other states to test the pre and post-conditions as well.
 */
describe( 'All states in CoreProfilerMachine should be reachable', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );
	const testMachine = createMachine( {
		id: 'coreProfilerTestMachine',
		initial: 'initializing',
		predictableActionArguments: true,
		states: {
			initializing: {
				on: {
					INITIALIZATION_COMPLETE: 'introOptIn',
				},
				meta: {
					test: async ( page: ReturnType< typeof render > ) => {
						await page.findByTestId(
							'core-profiler-loading-screen'
						);
						expect( page ).toMatchSnapshot();
					},
				},
			},
			introOptIn: {
				on: {
					INTRO_COMPLETED: 'userProfile',
					INTRO_SKIPPED: 'skipFlowBusinessLocation',
				},
				meta: {
					test: async ( page: ReturnType< typeof render > ) => {
						expect(
							await page.findByTestId(
								'core-profiler-intro-opt-in-screen'
							)
						).toBeVisible();
						expect( page ).toMatchSnapshot();
					},
				},
			},
			skipFlowBusinessLocation: {
				meta: {
					test: async ( page: ReturnType< typeof render > ) => {
						expect(
							await page.findByTestId(
								'core-profiler-business-location'
							)
						).toBeVisible();
						expect( page ).toMatchSnapshot();
					},
				},
			},
			userProfile: {
				meta: {
					test: async ( page: ReturnType< typeof render > ) => {
						expect(
							await page.findByTestId(
								'core-profiler-user-profile'
							)
						).toBeVisible();
						expect( page ).toMatchSnapshot();
					},
				},
			},
		},
	} );
	const coreProfilerMachineModel = createModel( testMachine ).withEvents( {
		INITIALIZATION_COMPLETE: () => {},
		INTRO_SKIPPED: async ( page ) => {
			await fireEvent.click(
				( page as RenderResult ).getByText( 'Skip guided setup' )
			);
		},
		INTRO_COMPLETED: async ( page ) => {
			await fireEvent.click(
				( page as RenderResult ).getByText( 'Set up my store' )
			);
		},
		SKIP_FLOW_BUSINESS_LOCATION: () => {},
	} );
	const testPlans = coreProfilerMachineModel.getSimplePathPlans();
	testPlans.forEach( ( plan ) => {
		describe( `${ plan.description }`, () => {
			afterEach( () => jest.clearAllMocks() );
			plan.paths.forEach( ( path ) => {
				test( `${ path.description }`, async () => {
					const rendered = render(
						<CoreProfilerController
							actionOverrides={ actionOverrides }
							servicesOverrides={ servicesOverrides }
						/>
					);
					return waitFor( () => {
						// waitFor here so that the render settles before we test
						return path.test( rendered );
					} );
				} );
			} );
		} );
	} );

	it( 'coverage', () => {
		coreProfilerMachineModel.testCoverage();
	} );
} );
