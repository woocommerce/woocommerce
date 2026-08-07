/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { BackButton } from '..';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

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
				getByRole( 'button', { name: 'Bank transfer' } )
			).toBeInTheDocument();
		} );

		it( 'takes its accessible name from the tooltip text when it renders icon-only', () => {
			const { getByRole } = render(
				<BackButton href="/offline" tooltipText="Back to Payments" />
			);

			expect(
				getByRole( 'button', { name: 'Back to Payments' } )
			).toBeInTheDocument();
		} );

		it( 'falls back to the default tooltip text when no tooltip text is given', () => {
			const { getByRole } = render( <BackButton href="/offline" /> );

			expect(
				getByRole( 'button', { name: 'WooCommerce Settings' } )
			).toBeInTheDocument();
		} );
	} );
} );
