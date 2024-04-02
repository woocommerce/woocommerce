// /* eslint-disable @typescript-eslint/ban-ts-comment */
// /**
//  * External dependencies
//  */
// import { render, screen } from '@testing-library/react';
// import { recordEvent } from '@woocommerce/tracks';

// /**
//  * Internal dependencies
//  */
// import { Congrats } from '../congrats';

// jest.mock( '../../assembler-hub/site-hub', () => ( {
// 	__esModule: true,
// 	SiteHub: () => {
// 		return <div />;
// 	},
// } ) );

// jest.mock(
// 	'@wordpress/edit-site/build-module/components/layout/hooks',
// 	() => ( {
// 		__esModule: true,
// 		useIsSiteEditorLoading: jest.fn().mockReturnValue( false ),
// 	} )
// );

// jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );

// describe( 'Congrats', () => {
// 	let props: {
// 		sendEvent: jest.Mock;
// 	};

// 	beforeEach( () => {
// 		props = {
// 			sendEvent: jest.fn(),
// 		};
// 	} );

// 	it( 'should render Congrats page', () => {
// 		// @ts-ignore
// 		render( <Congrats { ...props } /> );

// 		expect(
// 			screen.getByText( /Your store looks great!/i )
// 		).toBeInTheDocument();

// 		expect(
// 			screen.getByRole( 'button', {
// 				name: /View store/i,
// 			} )
// 		).toBeInTheDocument();

// 		expect(
// 			screen.getByRole( 'button', {
// 				name: /Go to Products/i,
// 			} )
// 		).toBeInTheDocument();
// 		expect(
// 			screen.getByRole( 'button', {
// 				name: /Go to the Editor/i,
// 			} )
// 		).toBeInTheDocument();

// 		expect(
// 			screen.getByRole( 'button', {
// 				name: /Back to Home/i,
// 			} )
// 		).toBeInTheDocument();
// 	} );

// 	it( 'should record an event when clicking on "View store" button', () => {
// 		window.open = jest.fn();
// 		// @ts-ignore
// 		render( <Congrats { ...props } /> );

// 		screen
// 			.getByRole( 'button', {
// 				name: /View store/i,
// 			} )
// 			.click();

// 		expect( recordEvent ).toHaveBeenCalledWith(
// 			'customize_your_store_congrats_preview_store_click'
// 		);
// 	} );

// 	it( 'should record an event when clicking on "Go to the Editor" button', () => {
// 		// @ts-ignore Mocking window location
// 		delete window.location;
// 		window.location = {
// 			// @ts-ignore Mocking window location href
// 			href: jest.fn(),
// 		};

// 		// @ts-ignore
// 		render( <Congrats { ...props } /> );

// 		screen
// 			.getByRole( 'button', {
// 				name: /Go to the Editor/i,
// 			} )
// 			.click();

// 		expect( recordEvent ).toHaveBeenCalledWith(
// 			'customize_your_store_congrats_editor_click'
// 		);
// 	} );

// 	it( 'should send GO_BACK_TO_HOME event when clicking on "Back to Home" button', () => {
// 		// @ts-ignore
// 		render( <Congrats { ...props } /> );

// 		screen
// 			.getByRole( 'button', {
// 				name: /Back to Home/i,
// 			} )
// 			.click();

// 		expect( props.sendEvent ).toHaveBeenCalledWith( {
// 			type: 'GO_BACK_TO_HOME',
// 		} );
// 		expect( recordEvent ).toHaveBeenCalledWith(
// 			'customize_your_store_congrats_home_click'
// 		);
// 	} );
// } );
