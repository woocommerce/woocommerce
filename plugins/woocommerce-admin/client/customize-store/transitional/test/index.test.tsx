/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { Transitional } from '../index';

jest.mock( '../mshots-image', () => ( {
	__esModule: true,
	MShotsImage: () => {
		return <img alt="preview-img" />;
	},
} ) );

jest.mock( '../../assembler-hub/site-hub', () => ( {
	__esModule: true,
	SiteHub: () => {
		return <div />;
	},
} ) );

describe( 'Transitional', () => {
	let props: {
		sendEvent: jest.Mock;
	};

	beforeEach( () => {
		props = {
			sendEvent: jest.fn(),
		};
	} );

	it( 'should render Transitional page', () => {
		// @ts-ignore
		render( <Transitional { ...props } /> );

		expect(
			screen.getByText( /Your store looks great!/i )
		).toBeInTheDocument();

		expect( screen.getByRole( 'img' ) ).toBeInTheDocument();

		expect(
			screen.getByRole( 'link', {
				name: /Preview store/i,
			} )
		).toBeInTheDocument();

		expect(
			screen.getByRole( 'link', {
				name: /Go to the Editor/i,
			} )
		).toBeInTheDocument();

		expect(
			screen.getByRole( 'button', {
				name: /Back to Home/i,
			} )
		).toBeInTheDocument();
	} );

	it( 'should send GO_BACK_TO_HOME event when clicking on "Back to Home" button', () => {
		// @ts-ignore
		render( <Transitional { ...props } /> );

		screen
			.getByRole( 'button', {
				name: /Back to Home/i,
			} )
			.click();

		expect( props.sendEvent ).toHaveBeenCalledWith( {
			type: 'GO_BACK_TO_HOME',
		} );
	} );
} );
