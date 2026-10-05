import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';

/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';
import { recordEvent } from '@woocommerce/tracks';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { BackButton } from '..';
vi.mock( '@woocommerce/tracks', () => {
	const mock = {
		recordEvent: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const push = vi.fn();

// Only `getHistory` is stubbed — the rest of the module has to stay real,
// because `@woocommerce/data` wires itself up against it on import.
vi.mock( '@woocommerce/navigation', async () => {
	const mock = {
		...( await vi.importActual( '@woocommerce/navigation' ) ),
		getHistory: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
beforeEach( () => {
	vi.clearAllMocks();
	( getHistory as Mock ).mockReturnValue( {
		push,
	} );
} );
describe( 'BackButton', () => {
	describe( 'Accessible name', () => {
		// The label is what a sighted user reads, so it has to be what a
		// screen reader announces too — the tooltip must not override it.
		it( 'takes its accessible name from the visible label when one is given', () => {
			const { getByRole } = render(
				<BackButton href="/offline" tooltipText="Back to Payments">
					Bank transfer
				</BackButton>
			);
			expect(
				getByRole( 'button', {
					name: 'Bank transfer',
				} )
			).toBeInTheDocument();
		} );
		it( 'takes its accessible name from the tooltip text when it renders icon-only', () => {
			const { getByRole } = render(
				<BackButton href="/offline" tooltipText="Back to Payments" />
			);
			expect(
				getByRole( 'button', {
					name: 'Back to Payments',
				} )
			).toBeInTheDocument();
		} );
		it( 'falls back to the default tooltip text when no tooltip text is given', () => {
			const { getByRole } = render( <BackButton href="/offline" /> );
			expect(
				getByRole( 'button', {
					name: 'WooCommerce Settings',
				} )
			).toBeInTheDocument();
		} );
	} );
	describe( 'Going back', () => {
		// The label sits inside the button, so clicking the words has to go
		// back just like clicking the chevron does.
		it( 'navigates and records the click when the visible label is clicked', () => {
			const { getByText } = render(
				<BackButton href="/offline" isRoute from="offline_gateway">
					Bank transfer
				</BackButton>
			);
			fireEvent.click( getByText( 'Bank transfer' ) );
			expect( push ).toHaveBeenCalledWith( '/offline' );
			expect( recordEvent ).toHaveBeenCalledWith(
				'settings_payments_back_button_click',
				expect.objectContaining( {
					from: 'offline_gateway',
				} )
			);
		} );
		it( 'navigates and records the click when it renders icon-only', () => {
			const { getByRole } = render(
				<BackButton
					href="/offline"
					isRoute
					tooltipText="Back to Payments"
				/>
			);
			fireEvent.click( getByRole( 'button' ) );
			expect( push ).toHaveBeenCalledWith( '/offline' );
			expect( recordEvent ).toHaveBeenCalledWith(
				'settings_payments_back_button_click',
				expect.any( Object )
			);
		} );
	} );
} );
