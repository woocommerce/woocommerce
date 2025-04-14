/**
 * External dependencies
 */
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { BusinessInfo } from '../BusinessInfo';
import { CoreProfilerStateMachineContext } from '../..';

describe( 'BusinessInfo', () => {
	let props: {
		sendEvent: jest.Mock;
		navigationProgress: number;
		context: CoreProfilerStateMachineContext;
	};

	beforeEach( () => {
		props = {
			sendEvent: jest.fn(),
			navigationProgress: 0,
			context: {
				geolocatedLocation: undefined,
				userProfile: {
					businessChoice: 'im_just_starting_my_business',
				},
				businessInfo: {
					storeName: '',
					location: '',
				},
				countries: [
					{
						key: 'AU:VIC',
						label: 'Australia — Victoria',
					},
					{
						key: 'AW',
						label: 'Aruba',
					},
				],
				// @ts-expect-error -- we don't need the other props in the tests
				onboardingProfile: {
					is_store_country_set: false,
					industry: [ 'other' ],
				},
			},
		};
	} );

	it( 'should render business info page', () => {
		render( <BusinessInfo { ...props } /> );
		expect(
			screen.getByText( /Tell us a bit about your store/i, {
				selector: 'h1',
			} )
		).toBeInTheDocument();

		expect(
			screen.getByText( /Give your store a name/i )
		).toBeInTheDocument();

		expect(
			screen.getByText( /Where is your store located?/i )
		).toBeInTheDocument();

		expect( screen.getByText( /Your email address/i ) ).toBeInTheDocument();

		expect(
			screen.getByRole( 'button', {
				name: /Continue/i,
			} )
		).toBeInTheDocument();
	} );

	it( 'should show the correct label for each businessChoice', () => {
		props.context.userProfile.businessChoice =
			'im_just_starting_my_business';
		render( <BusinessInfo { ...props } /> );
		expect(
			screen.getByText(
				/What type of products or services do you plan to sell?/i
			)
		).toBeInTheDocument();

		props.context.userProfile.businessChoice = 'im_already_selling';
		render( <BusinessInfo { ...props } /> );
		expect(
			screen.getByText( /Which industry is your business in?/i )
		).toBeInTheDocument();

		props.context.userProfile.businessChoice =
			'im_setting_up_a_store_for_a_client';
		render( <BusinessInfo { ...props } /> );
		expect(
			screen.getByText( /Which industry is your client’s business in?/i )
		).toBeInTheDocument();
	} );

	it( 'should prepopulate the store country selector if geolocation is available', () => {
		props.context.geolocatedLocation = {
			latitude: '-37.83961',
			longitude: '144.94228',
			country_short: 'AU',
			country_long: 'Australia',
			region: 'Victoria',
			city: 'Port Melbourne',
		};
		render( <BusinessInfo { ...props } /> );
		expect(
			screen.getByRole( 'combobox', {
				name: '', // unfortunately the SelectControl loses its name when its populated and there's no other unique identifier
			} )
		).toHaveValue( 'Australia — Victoria' );
	} );

	it( 'should not prepopulate the store country selector if geolocation is not available', () => {
		render( <BusinessInfo { ...props } /> );
		expect(
			screen.getByRole( 'combobox', {
				name: /Select country\/region/i,
			} )
		).toHaveValue( '' );
	} );

	it( 'should correctly prepopulate the store name if it is passed in', () => {
		props.context.businessInfo.storeName = 'Test Store Name';
		render( <BusinessInfo { ...props } /> );
		expect(
			screen.getByRole( 'textbox', {
				name: /Give your store a name/i,
			} )
		).toHaveValue( 'Test Store Name' );
	} );

	it( 'should correctly prepopulate the industry if it is passed in', () => {
		props.context.onboardingProfile.industry = [ 'food_and_drink' ];
		render( <BusinessInfo { ...props } /> );
		expect(
			screen.getByRole( 'combobox', {
				name: /Select an industry/i,
			} )
		).toHaveValue( 'Food and drink' );
	} );

	it( 'should correctly prepopulate the store location if it is passed in', () => {
		props.context.businessInfo.location = 'AW';
		props.context.onboardingProfile.is_store_country_set = true;
		render( <BusinessInfo { ...props } /> );
		expect(
			screen.getByRole( 'combobox', {
				name: '',
			} )
		).toHaveValue( 'Aruba' );
	} );

	it( 'should correctly send event with empty form inputs when continue is clicked', () => {
		props.context.geolocatedLocation = {
			latitude: '-37.83961',
			longitude: '144.94228',
			country_short: 'AU',
			country_long: 'Australia',
			region: 'Victoria',
			city: 'Port Melbourne',
		};
		render( <BusinessInfo { ...props } /> );
		const continueButton = screen.getByRole( 'button', {
			name: /Continue/i,
		} );
		userEvent.click( continueButton );
		expect( props.sendEvent ).toHaveBeenCalledWith( {
			payload: {
				geolocationOverruled: false,
				industry: 'other',
				storeLocation: 'AU:VIC',
				storeName: '',
				isOptInMarketing: false,
				storeEmailAddress: '',
			},
			type: 'BUSINESS_INFO_COMPLETED',
		} );
	} );

	it( 'should show the warning tooltip if geolocation is available and the user has manually changed the country, and send the event with geolocationOverruled', async () => {
		props.context.geolocatedLocation = {
			latitude: '-37.83961',
			longitude: '144.94228',
			country_short: 'AU',
			country_long: 'Australia',
			region: 'Victoria',
			city: 'Port Melbourne',
		};
		render( <BusinessInfo { ...props } /> );
		const countrySelector = screen.getByRole( 'combobox', {
			name: '',
		} );

		countrySelector.focus();

		await waitFor( () => {
			expect(
				screen.getByRole( 'option', {
					name: /Aruba/i,
				} )
			).toBeInTheDocument();
		} );

		await userEvent.click(
			screen.getByRole( 'option', {
				name: /Aruba/i,
			} )
		);

		expect(
			screen.getByText(
				/Setting up your store in the wrong country may lead to the following issues: /i
			)
		).toBeInTheDocument();

		const continueButton = screen.getByRole( 'button', {
			name: /Continue/i,
		} );
		userEvent.click( continueButton );
		expect( props.sendEvent ).toHaveBeenCalledWith( {
			payload: {
				geolocationOverruled: true,
				industry: 'other',
				storeLocation: 'AW',
				storeName: '',
				isOptInMarketing: false,
				storeEmailAddress: '',
			},
			type: 'BUSINESS_INFO_COMPLETED',
		} );
	} );

	it( 'should correctly send event with inputs filled in a fresh form when continue is clicked', async () => {
		props.context.geolocatedLocation = {
			latitude: '-37.83961',
			longitude: '144.94228',
			country_short: 'AU',
			country_long: 'Australia',
			region: 'Victoria',
			city: 'Port Melbourne',
		};
		render( <BusinessInfo { ...props } /> );
		const storeNameInput = screen.getByRole( 'textbox', {
			name: /Give your store a name/i,
		} );
		userEvent.type( storeNameInput, 'Test Store Name' );
		const industrySelector = screen.getByRole( 'combobox', {
			name: /Select an industry/i,
		} );

		industrySelector.focus();

		await waitFor( () => {
			expect(
				screen.getByRole( 'option', {
					name: /Food and drink/i,
				} )
			).toBeInTheDocument();
		} );

		await userEvent.click(
			screen.getByRole( 'option', {
				name: /Food and drink/i,
			} )
		);

		const continueButton = screen.getByRole( 'button', {
			name: /Continue/i,
		} );
		userEvent.click( continueButton );
		expect( props.sendEvent ).toHaveBeenCalledWith( {
			payload: {
				geolocationOverruled: false,
				industry: 'food_and_drink',
				storeLocation: 'AU:VIC',
				storeName: 'Test Store Name',
				isOptInMarketing: false,
				storeEmailAddress: '',
			},
			type: 'BUSINESS_INFO_COMPLETED',
		} );
	} );

	it( 'should send the event with the correct values if the form has been pre-filled from context', () => {
		props.context.geolocatedLocation = {
			latitude: '-37.83961',
			longitude: '144.94228',
			country_short: 'AU',
			country_long: 'Australia',
			region: 'Victoria',
			city: 'Port Melbourne',
		};
		props.context.onboardingProfile.industry = [ 'food_and_drink' ];
		props.context.businessInfo.storeName = 'Test Store Name';
		props.context.businessInfo.location = 'AU:VIC';
		render( <BusinessInfo { ...props } /> );
		const continueButton = screen.getByRole( 'button', {
			name: /Continue/i,
		} );
		userEvent.click( continueButton );
		expect( props.sendEvent ).toHaveBeenCalledWith( {
			payload: {
				geolocationOverruled: false,
				industry: 'food_and_drink',
				storeLocation: 'AU:VIC',
				storeName: 'Test Store Name',
				isOptInMarketing: false,
				storeEmailAddress: '',
			},
			type: 'BUSINESS_INFO_COMPLETED',
		} );
	} );

	describe( 'business info page, email marketing opt-in', () => {
		it( 'should not disable the continue field when opt in checkbox is not checked and email field is empty', () => {
			props.context.businessInfo.location = 'AW';
			props.context.onboardingProfile.is_store_country_set = true;
			render( <BusinessInfo { ...props } /> );
			const continueButton = screen.getByRole( 'button', {
				name: /Continue/i,
			} );
			expect( continueButton ).not.toBeDisabled();
		} );

		it( 'should disable the continue field when opt in checkbox is checked and email field is empty', () => {
			props.context.businessInfo.location = 'AW';
			props.context.onboardingProfile.is_store_country_set = true;
			render( <BusinessInfo { ...props } /> );
			const checkbox = screen.getByRole( 'checkbox', {
				name: /Opt-in to receive tips, discounts, and recommendations from the Woo team directly in your inbox./i,
			} );
			userEvent.click( checkbox );
			const continueButton = screen.getByRole( 'button', {
				name: /Continue/i,
			} );
			expect( continueButton ).toBeDisabled();
		} );

		it( 'should correctly send event with opt-in true when opt in checkbox is checked and email field is filled', () => {
			props.context.businessInfo.location = 'AW';
			props.context.onboardingProfile.is_store_country_set = true;
			render( <BusinessInfo { ...props } /> );
			const checkbox = screen.getByRole( 'checkbox', {
				name: /Opt-in to receive tips, discounts, and recommendations from the Woo team directly in your inbox./i,
			} );
			userEvent.click( checkbox );
			const emailInput = screen.getByRole( 'textbox', {
				name: /Your email address/i,
			} );
			userEvent.type( emailInput, 'wordpress@automattic.com' );
			const continueButton = screen.getByRole( 'button', {
				name: /Continue/i,
			} );
			userEvent.click( continueButton );
			expect( props.sendEvent ).toHaveBeenCalledWith( {
				payload: {
					geolocationOverruled: false,
					industry: 'other',
					storeLocation: 'AW',
					storeName: '',
					isOptInMarketing: true,
					storeEmailAddress: 'wordpress@automattic.com',
				},
				type: 'BUSINESS_INFO_COMPLETED',
			} );
		} );

		it( 'should correctly prepopulate the email field if populated in the onboarding profile', () => {
			props.context.onboardingProfile.store_email =
				'wordpress@automattic.com';
			render( <BusinessInfo { ...props } /> );
			const emailInput = screen.getByRole( 'textbox', {
				name: /Your email address/i,
			} );
			expect( emailInput ).toHaveValue( 'wordpress@automattic.com' );
		} );

		it( 'should correctly prepopulate the email field if populated in the current user', () => {
			props.context.currentUserEmail = 'currentUser@automattic.com';
			render( <BusinessInfo { ...props } /> );
			const emailInput = screen.getByRole( 'textbox', {
				name: /Your email address/i,
			} );
			expect( emailInput ).toHaveValue( 'currentUser@automattic.com' );
		} );

		it( 'should correctly favor the onboarding profile email over the current user email', () => {
			props.context.currentUserEmail = 'currentUser@automattic.com';
			props.context.onboardingProfile.store_email =
				'wordpress@automattic.com';
			render( <BusinessInfo { ...props } /> );
			const emailInput = screen.getByRole( 'textbox', {
				name: /Your email address/i,
			} );
			expect( emailInput ).toHaveValue( 'wordpress@automattic.com' );
		} );

		it( 'should not show an error for invalid email if isOptInMarketing is false', () => {
			props.context.businessInfo.location = 'AW';
			render( <BusinessInfo { ...props } /> );
			const emailInput = screen.getByRole( 'textbox', {
				name: /Your email address/i,
			} );
			userEvent.type( emailInput, 'invalid email' );
			expect(
				screen.queryByText( /This email is not valid./i )
			).not.toBeInTheDocument();
		} );

		it( 'should validate the email field when isOptInMarketing is true', () => {
			props.context.businessInfo.location = 'AW';
			render( <BusinessInfo { ...props } /> );
			const checkbox = screen.getByRole( 'checkbox', {
				name: /Opt-in to receive tips, discounts, and recommendations from the Woo team directly in your inbox./i,
			} );
			userEvent.click( checkbox );
			const emailInput = screen.getByRole( 'textbox', {
				name: /Your email address/i,
			} );
			userEvent.type( emailInput, 'invalid email' );
			expect(
				screen.getByText( /This email is not valid./i )
			).toBeInTheDocument();
		} );

		it( 'should not show an error for invalid email if isOptInMarketing is true and email is valid', () => {
			props.context.businessInfo.location = 'AW';
			render( <BusinessInfo { ...props } /> );
			const checkbox = screen.getByRole( 'checkbox', {
				name: /Opt-in to receive tips, discounts, and recommendations from the Woo team directly in your inbox./i,
			} );
			userEvent.click( checkbox );
			const emailInput = screen.getByRole( 'textbox', {
				name: /Your email address/i,
			} );
			userEvent.type( emailInput, 'valid@email.com' );
		} );

		it( 'should show an error for invalid email if isOptInMarketing is checked after the invalid email has already been filled out', () => {
			props.context.businessInfo.location = 'AW';
			render( <BusinessInfo { ...props } /> );
			expect(
				screen.queryByText( /This email is not valid./i )
			).not.toBeInTheDocument();
			const emailInput = screen.getByRole( 'textbox', {
				name: /Your email address/i,
			} );
			userEvent.type( emailInput, 'invalid email' );
			const checkbox = screen.getByRole( 'checkbox', {
				name: /Opt-in to receive tips, discounts, and recommendations from the Woo team directly in your inbox./i,
			} );
			userEvent.click( checkbox );
			expect(
				screen.getByText( /This email is not valid./i )
			).toBeInTheDocument();
		} );

		it( 'should hide the error after the invalid email has been corrected', () => {
			props.context.businessInfo.location = 'AW';
			render( <BusinessInfo { ...props } /> );
			const checkbox = screen.getByRole( 'checkbox', {
				name: /Opt-in to receive tips, discounts, and recommendations from the Woo team directly in your inbox./i,
			} );
			userEvent.click( checkbox );
			const emailInput = screen.getByRole( 'textbox', {
				name: /Your email address/i,
			} );
			userEvent.type( emailInput, 'invalid email' );
			expect(
				screen.getByText( /This email is not valid./i )
			).toBeInTheDocument();
			userEvent.clear( emailInput );
			userEvent.type( emailInput, 'valid@email.com' );
			expect(
				screen.queryByText( /This email is not valid./i )
			).not.toBeInTheDocument();
		} );

		it( 'should not allow the continue button to be pressed if email is invalid and isOptInMarketing is checked', () => {
			props.context.businessInfo.location = 'AW';
			render( <BusinessInfo { ...props } /> );
			const checkbox = screen.getByRole( 'checkbox', {
				name: /Opt-in to receive tips, discounts, and recommendations from the Woo team directly in your inbox./i,
			} );
			userEvent.click( checkbox );
			const emailInput = screen.getByRole( 'textbox', {
				name: /Your email address/i,
			} );
			userEvent.type( emailInput, 'invalid email' );
			const continueButton = screen.getByRole( 'button', {
				name: /Continue/i,
			} );
			expect( continueButton ).toBeDisabled();
		} );

		it( 'should allow the continue button to be pressed if email is invalid and isOptInMarketing is unchecked', () => {
			props.context.businessInfo.location = 'AW';
			props.context.onboardingProfile.is_store_country_set = true;

			render( <BusinessInfo { ...props } /> );
			const emailInput = screen.getByRole( 'textbox', {
				name: /Your email address/i,
			} );

			userEvent.type( emailInput, 'invalid email' );
			const continueButton = screen.getByRole( 'button', {
				name: /Continue/i,
			} );
			expect( continueButton ).not.toBeDisabled();
		} );

		it( 'should allow the continue button to be pressed if email is valid and isOptInMarketing is checked', () => {
			props.context.businessInfo.location = 'AW';
			props.context.onboardingProfile.is_store_country_set = true;
			render( <BusinessInfo { ...props } /> );
			const checkbox = screen.getByRole( 'checkbox', {
				name: /Opt-in to receive tips, discounts, and recommendations from the Woo team directly in your inbox./i,
			} );
			userEvent.click( checkbox );
			const emailInput = screen.getByRole( 'textbox', {
				name: /Your email address/i,
			} );
			userEvent.type( emailInput, 'valid@email.com' );
			const continueButton = screen.getByRole( 'button', {
				name: /Continue/i,
			} );
			expect( continueButton ).not.toBeDisabled();
		} );
	} );
} );
