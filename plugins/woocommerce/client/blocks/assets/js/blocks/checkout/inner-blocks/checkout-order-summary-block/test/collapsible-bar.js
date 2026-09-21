/**
 * External dependencies
 */
import {
	act,
	render,
	screen,
	fireEvent,
	createEvent,
} from '@testing-library/react';
import {
	ExperimentalDiscountsMeta,
	ExperimentalOrderMeta,
	SlotFillProvider,
} from '@woocommerce/blocks-checkout';

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

describe( 'Checkout Order Summary placement', () => {
	/**
	 * Renders the block together with the action-area slot.
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
					<div data-testid="summary-content" />
				</SummaryBlock>
				<CheckoutOrderSummarySlot />
			</SlotFillProvider>
		);
	};

	it.each( [ 'is-mobile', 'is-small', 'is-medium' ] )(
		'moves the summary to the action area when the container reports %p',
		( containerClassName ) => {
			const { container } = renderWithSlot( containerClassName );
			const actionArea = container.querySelector(
				'.checkout-order-summary-block-fill'
			);

			expect( actionArea ).toContainElement(
				screen.getByTestId( 'summary-content' )
			);
		}
	);

	it.each( [ '', 'is-large' ] )(
		'keeps the summary inline when the container reports %p',
		( containerClassName ) => {
			const { container } = renderWithSlot( containerClassName );
			const inlineSummary = container.querySelector(
				'.wc-block-components-checkout-order-summary__content'
			);

			expect( inlineSummary ).toContainElement(
				screen.getByTestId( 'summary-content' )
			);
		}
	);

	it( 'moves an open summary to the action area as it approaches the viewport', () => {
		let observerCallback;
		const disconnect = jest.fn();
		const intersectionObserverSpy = jest
			.spyOn( window, 'IntersectionObserver' )
			.mockImplementation( ( callback ) => {
				observerCallback = callback;
				return {
					observe: jest.fn(),
					disconnect,
				};
			} );

		const { container, unmount } = renderWithSlot( 'is-medium' );
		const title = screen.getByRole( 'button', {
			name: /Order summary/,
		} );
		const inlineSummary = container.querySelector(
			'.wc-block-components-checkout-order-summary__content'
		);
		const actionArea = container.querySelector(
			'.checkout-order-summary-block-fill'
		);
		const actionAreaAnchor = container.querySelector(
			'.checkout-order-summary-block-fill-wrapper'
		);
		const summaryContent = screen.getByTestId( 'summary-content' );

		fireEvent.click( title );
		expect( inlineSummary ).toContainElement( summaryContent );

		act( () => {
			observerCallback( [
				{ target: title, isIntersecting: false },
				{ target: actionAreaAnchor, isIntersecting: true },
			] );
		} );

		expect( title ).toHaveAttribute( 'aria-expanded', 'false' );
		expect( actionArea ).toContainElement( summaryContent );
		expect( actionAreaAnchor ).toHaveAttribute( 'aria-hidden', 'false' );

		unmount();
		expect( disconnect ).toHaveBeenCalled();
		intersectionObserverSpy.mockRestore();
	} );

	it( 'keeps public extension fills mounted once while the summary moves', () => {
		let containerClassName = '';
		baseContext.useContainerWidthContext.mockImplementation( () =>
			containerWidthOf( containerClassName )
		);

		const ExtensionContent = ( { testId } ) => (
			<div data-testid={ testId } />
		);
		const tree = () => (
			<SlotFillProvider>
				<ExperimentalDiscountsMeta>
					<ExtensionContent testId="discount-extension" />
				</ExperimentalDiscountsMeta>
				<ExperimentalOrderMeta>
					<ExtensionContent testId="order-extension" />
				</ExperimentalOrderMeta>
				<SummaryBlock>
					<ExperimentalDiscountsMeta.Slot
						extensions={ {} }
						cart={ {} }
						context="woocommerce/checkout"
					/>
				</SummaryBlock>
				<CheckoutOrderSummarySlot />
			</SlotFillProvider>
		);
		const { container, rerender } = render( tree() );
		const discountExtension = screen.getByTestId( 'discount-extension' );
		const orderExtension = screen.getByTestId( 'order-extension' );
		const inlineSummary = container.querySelector(
			'.wc-block-components-checkout-order-summary__content'
		);

		expect( inlineSummary ).toContainElement( discountExtension );
		expect( inlineSummary ).toContainElement( orderExtension );

		containerClassName = 'is-large';
		rerender( tree() );

		expect( screen.getAllByTestId( 'discount-extension' ) ).toHaveLength(
			1
		);
		expect( screen.getAllByTestId( 'order-extension' ) ).toHaveLength( 1 );
		expect( screen.getByTestId( 'discount-extension' ) ).toBe(
			discountExtension
		);
		expect( screen.getByTestId( 'order-extension' ) ).toBe(
			orderExtension
		);

		containerClassName = 'is-medium';
		rerender( tree() );

		const actionArea = container.querySelector(
			'.checkout-order-summary-block-fill'
		);
		expect( actionArea ).toContainElement( discountExtension );
		expect( actionArea ).toContainElement( orderExtension );

		fireEvent.click(
			screen.getByRole( 'button', { name: /Order summary/ } )
		);

		expect( inlineSummary ).toContainElement( discountExtension );
		expect( inlineSummary ).toContainElement( orderExtension );
		expect( screen.getAllByTestId( 'discount-extension' ) ).toHaveLength(
			1
		);
		expect( screen.getAllByTestId( 'order-extension' ) ).toHaveLength( 1 );

		fireEvent.click(
			screen.getByRole( 'button', { name: /Order summary/ } )
		);

		expect( actionArea ).toContainElement( discountExtension );
		expect( actionArea ).toContainElement( orderExtension );
		expect( screen.getByTestId( 'discount-extension' ) ).toBe(
			discountExtension
		);
		expect( screen.getByTestId( 'order-extension' ) ).toBe(
			orderExtension
		);
	} );
} );
