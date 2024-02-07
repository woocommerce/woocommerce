/**
 * External dependencies
 */
import { createContext } from '@wordpress/element';
import { render, screen } from '@testing-library/react';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { OnboardingTour } from '../index';
import { FlowType } from '~/customize-store/types';

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );
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
		flowType: FlowType.AIOnline | FlowType.noAI;
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
			flowType: FlowType.AIOnline,
		};
	} );

	it( 'should render welcome tour mentioning the AI when the flowType is AIOnline', () => {
		render( <OnboardingTour { ...props } /> );

		expect(
			screen.getByText( /Welcome to your AI-generated store!/i )
		).toBeInTheDocument();
	} );

	it( 'should render welcome tour not mentioning the AI when the flowType is AIOnline', () => {
		render( <OnboardingTour { ...props } flowType={ FlowType.noAI } /> );

		expect(
			screen.getByText(
				/Discover what's possible with the store designer/i
			)
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

		expect( props.takeTour ).toHaveBeenCalled();
	} );

	it( 'should record an event when clicking on "Skip" button', () => {
		render( <OnboardingTour { ...props } /> );

		screen
			.getByRole( 'button', {
				name: /Skip/i,
			} )
			.click();

		expect( props.skipTour ).toHaveBeenCalled();
	} );

	it( 'should record an event when clicking on the "Close Tour" button', () => {
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
