/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { IntroModal, INTRO_MODAL_DISMISSED_OPTION_NAME } from '../';

global.window.wcNavigation = {};

jest.mock( '@wordpress/data', () => {
	// Require the original module to not be mocked...
	const originalModule = jest.requireActual( '@wordpress/data' );

	return {
		__esModule: true, // Use it when dealing with esModules
		...originalModule,
		useDispatch: jest.fn().mockReturnValue( {} ),
		useSelect: jest.fn().mockReturnValue( {} ),
	};
} );

describe( 'IntroModal', () => {
	test( 'should not show when modal options are resolving', () => {
		useSelect.mockImplementation( () => ( {
			isWelcomeModalShown: null,
			isResolving: true,
		} ) );

		const { container } = render( <IntroModal /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	test( 'should dismiss when the welcome modal is shown', () => {
		const updateOptions = jest.fn();
		useSelect.mockImplementation( () => ( {
			isWelcomeModalShown: true,
		} ) );
		useDispatch.mockImplementation( () => ( {
			updateOptions,
		} ) );

		const { container } = render( <IntroModal /> );

		expect( container ).toBeEmptyDOMElement();
		expect( updateOptions ).toHaveBeenCalledWith( {
			[ INTRO_MODAL_DISMISSED_OPTION_NAME ]: 'yes',
		} );
	} );

	test( 'should not dismiss when the modal has already been dismissed', () => {
		const updateOptions = jest.fn();
		useSelect.mockImplementation( () => ( {
			isDismissed: true,
			isResolving: false,
			isWelcomeModalShown: true,
		} ) );
		useDispatch.mockImplementation( () => ( {
			updateOptions,
		} ) );

		const { container } = render( <IntroModal /> );

		expect( container ).toBeEmptyDOMElement();
		expect( updateOptions ).not.toHaveBeenCalled();
	} );

	test( 'should show the intro modal when welcome modal is not shown', () => {
		useSelect.mockImplementation( () => ( {
			isResolving: false,
			isWelcomeModalShown: false,
		} ) );

		render( <IntroModal /> );

		expect(
			screen.queryByText( 'A new navigation for WooCommerce' )
		).not.toBeNull();
	} );

	test( 'should hide and update the dismissal option when closing the modal', () => {
		const updateOptions = jest.fn();
		useSelect.mockImplementation( () => ( {
			isResolving: false,
			isWelcomeModalShown: false,
		} ) );
		useDispatch.mockImplementation( () => ( {
			updateOptions,
		} ) );

		render( <IntroModal /> );

		fireEvent.click( screen.queryByLabelText( 'Close dialog' ) );

		expect(
			screen.queryByText( 'A new navigation for WooCommerce' )
		).toBeNull();
		expect( updateOptions ).toHaveBeenCalledWith( {
			[ INTRO_MODAL_DISMISSED_OPTION_NAME ]: 'yes',
		} );
	} );
} );
