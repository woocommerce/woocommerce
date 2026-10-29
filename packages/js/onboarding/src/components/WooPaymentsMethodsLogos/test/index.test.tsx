/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { WooPaymentsMethodsLogos } from '../';

describe( 'WooPaymentsMethodsLogos', () => {
	it.each( [ true, false ] )(
		'shows as many logos in the popover as the "+N" count (WooPay eligible: %s)',
		async ( isWooPayEligible ) => {
			const { container } = render(
				<WooPaymentsMethodsLogos
					isWooPayEligible={ isWooPayEligible }
					maxElements={ 10 }
				/>
			);

			const count = screen.getByRole( 'button' );
			const hiddenCount = Number(
				count.textContent?.replace( /\D/g, '' )
			);
			expect( container.querySelectorAll( 'img' ) ).toHaveLength( 10 );

			fireEvent.click( count );

			await waitFor( () =>
				expect(
					document.querySelectorAll(
						'.woocommerce-woopayments-payment-methods-logos-popover img'
					)
				).toHaveLength( hiddenCount )
			);
		}
	);
} );
