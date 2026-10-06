/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { OfficialBadge } from '../official-badge';

jest.mock( '~/utils/admin-settings', () => ( {
	WC_ASSET_URL: 'https://example.test/',
} ) );

jest.mock( '~/settings-payments/utils', () => ( {
	recordPaymentsEvent: jest.fn(),
} ) );

describe( 'OfficialBadge component', () => {
	it( 'allows Enter on a popover link without preventing its default action', async () => {
		render(
			<OfficialBadge variant="expanded" suggestionId="test_gateway" />
		);
		fireEvent.click(
			screen.getByRole( 'button', {
				name: /Official WooCommerce extension badge/,
			} )
		);
		const link = await screen.findByRole( 'link', { name: /Learn more/ } );
		link.focus();
		const enter = new window.KeyboardEvent( 'keydown', {
			key: 'Enter',
			bubbles: true,
			cancelable: true,
		} );

		fireEvent( link, enter );

		expect( enter.defaultPrevented ).toBe( false );
		expect( link ).toBeInTheDocument();
	} );

	it( 'opens with Enter and Space and returns focus to the trigger on Escape', async () => {
		render(
			<OfficialBadge variant="expanded" suggestionId="test_gateway" />
		);
		const trigger = screen.getByRole( 'button', {
			name: /Official WooCommerce extension badge/,
		} );
		trigger.focus();
		fireEvent.keyDown( trigger, { key: 'Enter' } );
		const link = await screen.findByRole( 'link', { name: /Learn more/ } );
		link.focus();

		fireEvent.keyDown( link, { key: 'Escape' } );

		expect(
			screen.queryByRole( 'link', { name: /Learn more/ } )
		).not.toBeInTheDocument();
		expect( trigger ).toHaveFocus();
		fireEvent.keyDown( trigger, { key: ' ' } );
		expect(
			await screen.findByRole( 'link', { name: /Learn more/ } )
		).toBeInTheDocument();
	} );
} );
