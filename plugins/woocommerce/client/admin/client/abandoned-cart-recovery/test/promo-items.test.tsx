/**
 * External dependencies
 */
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { recordEvent } from '@woocommerce/tracks';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import AutomateWooItem from '../automatewoo-item';
import MailPoetItem from '../mailpoet-item';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useDispatch: jest.fn(),
} ) );

describe( 'AutomateWooItem', () => {
	beforeEach( () => {
		( recordEvent as jest.Mock ).mockClear();
	} );

	it( 'renders the AutomateWoo title, description, and Learn more CTA', () => {
		render( <AutomateWooItem /> );

		expect( screen.getByText( 'AutomateWoo' ) ).toBeInTheDocument();
		expect(
			screen.getByText( /multi-step abandoned cart sequences/i )
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
	const createSuccessNoticeMock = jest.fn();

	beforeEach( () => {
		( recordEvent as jest.Mock ).mockClear();
		createSuccessNoticeMock.mockClear();
		( useDispatch as jest.Mock ).mockReturnValue( {
			createSuccessNotice: createSuccessNoticeMock,
		} );
	} );

	it( 'renders the MailPoet title, description, and Get started CTA', () => {
		render(
			<MailPoetItem
				pluginsBeingSetup={ [] }
				onSetupClick={ () => Promise.resolve() }
			/>
		);

		expect( screen.getByText( 'MailPoet' ) ).toBeInTheDocument();
		expect(
			screen.getByText( /newsletters and automated welcome series/i )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: /get started/i } )
		).toBeInTheDocument();
	} );

	it( 'calls onSetupClick with the mailpoet slug and fires the Tracks event', async () => {
		const onSetupClick = jest.fn().mockResolvedValue( undefined );

		render(
			<MailPoetItem
				pluginsBeingSetup={ [] }
				onSetupClick={ onSetupClick }
			/>
		);

		await userEvent.click(
			screen.getByRole( 'button', { name: /get started/i } )
		);

		expect( recordEvent ).toHaveBeenCalledWith(
			'abandoned_cart_recovery_recommendation_click',
			{ plugin: 'mailpoet' }
		);
		expect( onSetupClick ).toHaveBeenCalledWith( [ 'mailpoet' ] );
	} );

	it( 'creates a success notice with a Set up MailPoet action when install completes', async () => {
		const onSetupClick = jest.fn().mockResolvedValue( undefined );

		render(
			<MailPoetItem
				pluginsBeingSetup={ [] }
				onSetupClick={ onSetupClick }
			/>
		);

		await userEvent.click(
			screen.getByRole( 'button', { name: /get started/i } )
		);

		await waitFor( () => {
			expect( createSuccessNoticeMock ).toHaveBeenCalledWith(
				'🎉 MailPoet is installed!',
				expect.objectContaining( {
					actions: expect.arrayContaining( [
						expect.objectContaining( {
							label: 'Set up MailPoet',
						} ),
					] ),
				} )
			);
		} );
	} );

	it( 'shows the busy state when mailpoet is in flight and disables the button', () => {
		render(
			<MailPoetItem
				pluginsBeingSetup={ [ 'mailpoet' ] }
				onSetupClick={ () => Promise.resolve() }
			/>
		);

		const button = screen.getByRole( 'button', { name: /get started/i } );
		expect( button ).toBeDisabled();
	} );
} );
