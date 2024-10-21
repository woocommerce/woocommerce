/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { UserProfile } from '../UserProfile';
import { CoreProfilerStateMachineContext } from '../..';

describe( 'UserProfile', () => {
	let props: {
		sendEvent: jest.Mock;
		navigationProgress: number;
		context: Pick< CoreProfilerStateMachineContext, 'userProfile' >;
	};

	beforeEach( () => {
		props = {
			sendEvent: jest.fn(),
			navigationProgress: 0,
			context: {
				userProfile: {
					businessChoice: 'im_just_starting_my_business',
					sellingOnlineAnswer: null,
					sellingPlatforms: null,
				},
			},
		};
	} );

	it( 'should render user profile page', () => {
		// @ts-ignore
		render( <UserProfile { ...props } /> );
		expect(
			screen.getByText( /Which one of these best describes you?/i, {
				selector: 'h1',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', {
				name: /Continue/i,
			} )
		).toBeInTheDocument();
	} );

	it( 'should show online selling question when choosing "im_already_selling"', () => {
		// @ts-ignore
		render( <UserProfile { ...props } /> );
		const radioInput = screen.getByLabelText< HTMLInputElement >(
			'I’m already selling'
		); // Replace with the label of your radio button

		// Perform the radio button selection
		fireEvent.click( radioInput );

		// Assert the expected behavior
		expect( radioInput.checked ).toBe( true );

		const onlineSellingQuestion = screen.getByText(
			/Are you selling online?/i
		);
		expect( onlineSellingQuestion ).toBeInTheDocument();
	} );

	it( 'should show online selling question when choosing "Yes, I’m selling online"', () => {
		render(
			// @ts-ignore
			<UserProfile
				{ ...{
					...props,
					context: {
						userProfile: {
							businessChoice: 'im_already_selling',
							sellingOnlineAnswer: 'yes_im_selling_online',
							sellingPlatforms: null,
						},
					},
				} }
			/>
		);
		const platformSelector = screen.getByLabelText( /Select an option/i );
		expect( platformSelector ).toBeInTheDocument();
	} );

	it( 'should call sendEvent with USER_PROFILE_COMPLETED event when button is clicked', () => {
		render(
			// @ts-ignore
			<UserProfile { ...props } />
		);
		screen
			.getByRole( 'button', {
				name: /Continue/i,
			} )
			.click();
		expect( props.sendEvent ).toHaveBeenCalledWith( {
			type: 'USER_PROFILE_COMPLETED',
			payload: {
				userProfile: {
					businessChoice: 'im_just_starting_my_business',
					sellingOnlineAnswer: null,
					sellingPlatforms: null,
				},
			},
		} );
	} );

	it( 'should call sendEvent with USER_PROFILE_SKIPPED event when skip button is clicked', () => {
		render(
			// @ts-ignore
			<UserProfile { ...props } />
		);
		screen
			.getByRole( 'button', {
				name: /Skip this step/i,
			} )
			.click();
		expect( props.sendEvent ).toHaveBeenCalledWith( {
			type: 'USER_PROFILE_SKIPPED',
			payload: {
				userProfile: {
					skipped: true,
				},
			},
		} );
	} );
} );
