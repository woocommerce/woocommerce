/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { previewCart as mockPreviewCart } from '../../../../../previews/cart';
import SummaryBlock from '../frontend';

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
