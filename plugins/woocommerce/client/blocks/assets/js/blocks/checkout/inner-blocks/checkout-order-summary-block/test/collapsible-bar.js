/**
 * External dependencies
 */
import { render, screen, fireEvent, createEvent } from '@testing-library/react';
import { SlotFillProvider } from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import { previewCart as mockPreviewCart } from '../../../../../previews/cart';
import SummaryBlock from '../frontend';
import { CheckoutOrderSummarySlot } from '../slotfills';

const baseContext = jest.requireMock( '@woocommerce/base-context' );

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
	SITE_CURRENCY: {
		code: 'USD',
		symbol: '$',
		thousandSeparator: ',',
		decimalSeparator: '.',
		minorUnit: 2,
		prefix: '$',
		suffix: '',
	},
} ) );

jest.mock( '@woocommerce/base-context/hooks', () => ( {
	...jest.requireActual( '@woocommerce/base-context/hooks' ),
	useStoreCart: jest.fn().mockReturnValue( {
		cartItems: [],
		cartTotals: {
			total_price: '4000',
			currency_code: 'USD',
			currency_symbol: '$',
			currency_minor_unit: 2,
			currency_decimal_separator: '.',
			currency_thousand_separator: ',',
			currency_prefix: '$',
			currency_suffix: '',
		},
		cartCoupons: [],
		cartFees: [],
		cartNeedsShipping: false,
		shippingRates: [],
		shippingAddress: mockPreviewCart.shipping_address,
		billingAddress: mockPreviewCart.billing_address,
		cartHasCalculatedShipping: true,
	} ),
} ) );

jest.mock( '@woocommerce/base-context', () => ( {
	...jest.requireActual( '@woocommerce/base-context' ),
	useContainerWidthContext: jest.fn(),
} ) );

/**
 * The values ContainerWidthContext produces for a given container class name.
 * An empty class name is what it reports until the resize observer first fires.
 *
 * @param {string} containerClassName Class name for the measured width.
 * @return {Object} The context value.
 */
const containerWidthOf = ( containerClassName ) => ( {
	hasContainerWidth: containerClassName !== '',
	containerClassName,
	isMobile: containerClassName === 'is-mobile',
	isSmall: containerClassName === 'is-small',
	isMedium: containerClassName === 'is-medium',
	isLarge: containerClassName === 'is-large',
} );

/**
 * @param {string} containerClassName Class name for the measured width.
 * @return {import('@testing-library/react').RenderResult} The render result.
 */
const renderAt = ( containerClassName ) => {
	baseContext.useContainerWidthContext.mockReturnValue(
		containerWidthOf( containerClassName )
	);
	return render(
		<SummaryBlock>
			<div />
		</SummaryBlock>
	);
};

describe( 'Checkout Order Summary collapsible bar', () => {
	describe.each( [ '', 'is-mobile', 'is-small', 'is-medium' ] )(
		'when the container reports %p',
		( containerClassName ) => {
			it( 'exposes the summary as an expandable button', () => {
				renderAt( containerClassName );

				const bar = screen.getByRole( 'button', {
					name: /Order summary/,
				} );
				expect( bar ).toHaveAttribute( 'aria-expanded', 'false' );
				expect( bar ).toHaveAttribute( 'aria-controls' );
				expect( bar ).toHaveAttribute( 'tabindex', '0' );
			} );

			it( 'toggles on click', () => {
				renderAt( containerClassName );

				const bar = screen.getByRole( 'button', {
					name: /Order summary/,
				} );
				fireEvent.click( bar );
				expect( bar ).toHaveAttribute( 'aria-expanded', 'true' );
			} );

			it.each( [ 'Enter', ' ' ] )( 'toggles on %p', ( key ) => {
				renderAt( containerClassName );

				const bar = screen.getByRole( 'button', {
					name: /Order summary/,
				} );
				fireEvent.keyDown( bar, { key } );
				expect( bar ).toHaveAttribute( 'aria-expanded', 'true' );
			} );

			// Without this the browser still acts on the key: Space scrolls the
			// page out from under the summary that just opened, and Enter
			// submits the checkout form the bar sits inside.
			it.each( [ 'Enter', ' ' ] )(
				'suppresses the browser default for %p',
				( key ) => {
					renderAt( containerClassName );

					const bar = screen.getByRole( 'button', {
						name: /Order summary/,
					} );
					const event = createEvent.keyDown( bar, { key } );
					fireEvent( bar, event );

					expect( event.defaultPrevented ).toBe( true );
				}
			);

			it( 'collapses again when activated a second time', () => {
				renderAt( containerClassName );

				const bar = screen.getByRole( 'button', {
					name: /Order summary/,
				} );
				fireEvent.click( bar );
				expect( bar ).toHaveAttribute( 'aria-expanded', 'true' );

				fireEvent.click( bar );
				expect( bar ).toHaveAttribute( 'aria-expanded', 'false' );
			} );
		}
	);

	it( 'is not a button once the container is large enough for two columns', () => {
		const { container } = renderAt( 'is-large' );

		const bar = container.querySelector(
			'.wc-block-components-checkout-order-summary__title'
		);
		expect( bar ).not.toHaveAttribute( 'role' );
		expect( bar ).not.toHaveAttribute( 'tabindex' );
		expect( bar ).not.toHaveAttribute( 'aria-expanded' );
	} );
} );

describe( 'Checkout Order Summary fill', () => {
	/**
	 * Renders the block together with the slot the fill targets, so the second
	 * summary instance actually appears in the tree.
	 *
	 * @param {string} containerClassName Class name for the measured width.
	 * @return {import('@testing-library/react').RenderResult} The render result.
	 */
	const renderWithSlot = ( containerClassName ) => {
		baseContext.useContainerWidthContext.mockReturnValue(
			containerWidthOf( containerClassName )
		);
		return render(
			<SlotFillProvider>
				<SummaryBlock>
					<div />
				</SummaryBlock>
				<CheckoutOrderSummarySlot />
			</SlotFillProvider>
		);
	};

	/**
	 * @param {HTMLElement} container Render container.
	 * @return {NodeList} The rendered fill wrappers.
	 */
	const fillsIn = ( container ) =>
		container.querySelectorAll(
			'.checkout-order-summary-block-fill-wrapper'
		);

	describe.each( [ '', 'is-mobile', 'is-small', 'is-medium' ] )(
		'when the container reports %p',
		( containerClassName ) => {
			it( 'renders the second summary into the slot', () => {
				const { container } = renderWithSlot( containerClassName );

				expect( fillsIn( container ) ).toHaveLength( 1 );
			} );
		}
	);

	it( 'renders no fill once the container is large enough for two columns', () => {
		const { container } = renderWithSlot( 'is-large' );

		expect( fillsIn( container ) ).toHaveLength( 0 );
	} );
} );
