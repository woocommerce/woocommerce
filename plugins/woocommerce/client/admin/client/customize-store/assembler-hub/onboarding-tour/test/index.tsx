/**
 * External dependencies
 */
import { createContext } from '@wordpress/element';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
/**
 * Internal dependencies
 */
import { OnboardingTour } from '../index';
import { trackEvent } from '~/customize-store/tracking';

jest.mock( '~/customize-store/tracking', () => ( { trackEvent: jest.fn() } ) );
jest.mock( '../../', () => ( {
	CustomizeStoreContext: createContext( {
		context: {
			aiOnline: true,
		},
	} ),
} ) );

describe( 'OnboardingTour', () => {
	let props: {
		onClose: jest.Mock;
		skipTour: jest.Mock;
		takeTour: jest.Mock;
		setShowWelcomeTour: jest.Mock;
		showWelcomeTour: boolean;
		setIsResizeHandleVisible: ( isVisible: boolean ) => void;
	};

	beforeEach( () => {
		props = {
			onClose: jest.fn(),
			skipTour: jest.fn(),
			takeTour: jest.fn(),
			setShowWelcomeTour: jest.fn(),
			showWelcomeTour: true,
			setIsResizeHandleVisible: jest.fn(),
		};
	} );

	it( 'should render step 1', () => {
		render( <OnboardingTour { ...props } showWelcomeTour={ false } /> );

		expect(
			screen.getByText( /View your changes in real time/i )
		).toBeInTheDocument();
	} );

	it( 'should record an event when clicking on "Take a tour" button', async () => {
		render( <OnboardingTour { ...props } /> );

		await userEvent.click(
			screen.getByRole( 'button', {
				name: /Take a tour/i,
			} )
		);

		expect( props.takeTour ).toHaveBeenCalled();
	} );

	it( 'should record an event when clicking on "Skip" button', async () => {
		render( <OnboardingTour { ...props } /> );

		await userEvent.click(
			screen.getByRole( 'button', {
				name: /Skip/i,
			} )
		);

		expect( props.skipTour ).toHaveBeenCalled();
	} );

	it( 'should record an event when clicking on the "Close Tour" button', async () => {
		render( <OnboardingTour { ...props } showWelcomeTour={ false } /> );

		await userEvent.click(
			screen.getByRole( 'button', {
				name: 'Close Tour',
			} )
		);

		expect( trackEvent ).toHaveBeenCalledWith(
			'customize_your_store_assembler_hub_tour_close'
		);
	} );

	it( 'should record an event when complete the tour', async () => {
		render( <OnboardingTour { ...props } showWelcomeTour={ false } /> );

		await userEvent.click(
			screen.getByRole( 'button', {
				name: 'Next',
			} )
		);

		await userEvent.click(
			screen.getByRole( 'button', {
				name: 'Done',
			} )
		);

		expect( trackEvent ).toHaveBeenCalledWith(
			'customize_your_store_assembler_hub_tour_complete'
		);
	} );
} );
