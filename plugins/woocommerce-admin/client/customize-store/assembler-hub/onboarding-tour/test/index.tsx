/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { OnboardingTour } from '../index';

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );

describe( 'OnboardingTour', () => {
	let props: {
		onClose: jest.Mock;
		setShowWelcomeTour: jest.Mock;
		showWelcomeTour: boolean;
		setIsResizeHandleVisible: ( isVisible: boolean ) => void;
	};

	beforeEach( () => {
		props = {
			onClose: jest.fn(),
			setShowWelcomeTour: jest.fn(),
			showWelcomeTour: true,
			setIsResizeHandleVisible: jest.fn(),
		};
	} );

	it( 'should render welcome tour', () => {
		render( <OnboardingTour { ...props } /> );

		expect(
			screen.getByText( /Welcome to your AI-generated store!/i )
		).toBeInTheDocument();
	} );

	it( 'should render step 1', () => {
		render( <OnboardingTour { ...props } showWelcomeTour={ false } /> );

		expect(
			screen.getByText( /View your changes in real time/i )
		).toBeInTheDocument();
	} );

	it( 'should record an event when clicking on "Take a tour" button', () => {
		render( <OnboardingTour { ...props } /> );

		screen
			.getByRole( 'button', {
				name: /Take a tour/i,
			} )
			.click();

		expect( recordEvent ).toHaveBeenCalledWith(
			'customize_your_store_assembler_hub_tour_start'
		);
	} );

	it( 'should record an event when clicking on "Skip" button', () => {
		render( <OnboardingTour { ...props } /> );

		screen
			.getByRole( 'button', {
				name: /Skip/i,
			} )
			.click();

		expect( recordEvent ).toHaveBeenCalledWith(
			'customize_your_store_assembler_hub_tour_skip'
		);
	} );

	it( 'should record an event when clicking on "Skip" button', () => {
		render( <OnboardingTour { ...props } showWelcomeTour={ false } /> );

		screen
			.getByRole( 'button', {
				name: 'Close Tour',
			} )
			.click();

		expect( recordEvent ).toHaveBeenCalledWith(
			'customize_your_store_assembler_hub_tour_close'
		);
	} );

	it( 'should record an event when complete the tour', () => {
		render( <OnboardingTour { ...props } showWelcomeTour={ false } /> );

		screen
			.getByRole( 'button', {
				name: 'Next',
			} )
			.click();

		screen
			.getByRole( 'button', {
				name: 'Done',
			} )
			.click();

		expect( recordEvent ).toHaveBeenCalledWith(
			'customize_your_store_assembler_hub_tour_complete'
		);
	} );
} );
