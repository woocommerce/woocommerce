/**
 * External dependencies
 */
import { render, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import TourKit from '..';

jest.mock( '@automattic/calypso-config' );

const config = {
	steps: [
		{
			referenceElements: {
				desktop: '.render-step-near-me',
			},
			meta: {
				name: 'step-one',
				heading: 'Step1',
				descriptions: {
					desktop: 'Description',
				},
			},
		},
		{
			referenceElements: {
				desktop: '.render-step-near-me',
			},
			meta: {
				name: 'step-one',
				heading: 'Step2',
				descriptions: {
					desktop: 'Description',
				},
			},
		},
	],
	closeHandler: () => jest.fn(),
	options: {},
};

describe( 'TourKit', () => {
	it( 'should render TourKit', () => {
		const { queryByText } = render( <TourKit config={ config } /> );
		expect( queryByText( 'Step1' ) ).toBeInTheDocument();
	} );

	it( 'should go to next step and show a back button', async () => {
		const { queryByText, getByRole } = render(
			<TourKit config={ config } />
		);
		userEvent.click( getByRole( 'button', { name: 'Next' } ) );

		await waitFor( () =>
			expect( queryByText( 'Step2' ) ).toBeInTheDocument()
		);
		expect( getByRole( 'button', { name: 'Back' } ) ).toBeInTheDocument();
	} );
} );
