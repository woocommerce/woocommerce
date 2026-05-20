/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import AutomateWooItem from '../automatewoo-item';
import MailPoetItem from '../mailpoet-item';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

describe( 'AutomateWooItem', () => {
	beforeEach( () => {
		( recordEvent as jest.Mock ).mockClear();
	} );

	it( 'renders the AutomateWoo title, description, and Learn more CTA', () => {
		render( <AutomateWooItem /> );

		expect( screen.getByText( 'AutomateWoo' ) ).toBeInTheDocument();
		expect(
			screen.getByText( /multi-step recovery sequences/i )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: /learn more/i } )
		).toHaveAttribute(
			'href',
			expect.stringContaining( 'woocommerce.com/products/automatewoo' )
		);
	} );

	it( 'fires the abandoned_cart_recovery_recommendation_click track on CTA click', async () => {
		render( <AutomateWooItem /> );

		await userEvent.click(
			screen.getByRole( 'link', { name: /learn more/i } )
		);

		expect( recordEvent ).toHaveBeenCalledWith(
			'abandoned_cart_recovery_recommendation_click',
			{ plugin: 'automatewoo' }
		);
	} );
} );

describe( 'MailPoetItem', () => {
	beforeEach( () => {
		( recordEvent as jest.Mock ).mockClear();
	} );

	it( 'renders the MailPoet title, description, and Learn more CTA', () => {
		render( <MailPoetItem /> );

		expect( screen.getByText( 'MailPoet' ) ).toBeInTheDocument();
		expect(
			screen.getByText( /newsletters and ongoing automations/i )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: /learn more/i } )
		).toHaveAttribute(
			'href',
			expect.stringContaining( 'woocommerce.com/products/mailpoet' )
		);
	} );

	it( 'fires the abandoned_cart_recovery_recommendation_click track on CTA click', async () => {
		render( <MailPoetItem /> );

		await userEvent.click(
			screen.getByRole( 'link', { name: /learn more/i } )
		);

		expect( recordEvent ).toHaveBeenCalledWith(
			'abandoned_cart_recovery_recommendation_click',
			{ plugin: 'mailpoet' }
		);
	} );
} );
