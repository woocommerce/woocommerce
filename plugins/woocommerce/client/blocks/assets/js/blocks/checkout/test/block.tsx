/**
 * External dependencies
 */
import { render, screen, waitFor, act, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { previewCart } from '@woocommerce/resource-previews';
import { dispatch, select } from '@wordpress/data';
import {
	cartStore,
	checkoutStore,
	validationStore,
} from '@woocommerce/block-data';
import { allSettings } from '@woocommerce/settings';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { server, http, HttpResponse } from '@woocommerce/test-utils/msw';

/**
 * Internal dependencies
 */
import Fields from '../inner-blocks/checkout-fields-block/frontend';
import ExpressPayment from '../inner-blocks/checkout-express-payment-block/block';
import ContactInformation from '../inner-blocks/checkout-contact-information-block/frontend';
import ShippingMethod from '../inner-blocks/checkout-shipping-method-block/frontend';
import PickupOptions from '../inner-blocks/checkout-pickup-options-block/frontend';
import ShippingAddress from '../inner-blocks/checkout-shipping-address-block/frontend';
import BillingAddress from '../inner-blocks/checkout-billing-address-block/frontend';
import ShippingMethods from '../inner-blocks/checkout-shipping-methods-block/frontend';
import Payment from '../inner-blocks/checkout-payment-block/frontend';
import AdditionalInformation from '../inner-blocks/checkout-additional-information-block/frontend';
import OrderNote from '../inner-blocks/checkout-order-note-block/block';
import Terms from '../inner-blocks/checkout-terms-block/frontend';
import { termsCheckboxDefaultText } from '../inner-blocks/checkout-terms-block/constants';
import Actions from '../inner-blocks/checkout-actions-block/frontend';
import Totals from '../inner-blocks/checkout-totals-block/frontend';
import OrderSummary from '../inner-blocks/checkout-order-summary-block/frontend';
import CartItems from '../inner-blocks/checkout-order-summary-cart-items/frontend';
import CouponForm from '../inner-blocks/checkout-order-summary-coupon-form/block';
import Subtotal from '../inner-blocks/checkout-order-summary-subtotal/frontend';
import Fee from '../inner-blocks/checkout-order-summary-fee/frontend';
import Discount from '../inner-blocks/checkout-order-summary-discount/frontend';
import Shipping from '../inner-blocks/checkout-order-summary-shipping/frontend';
import Taxes from '../inner-blocks/checkout-order-summary-taxes/frontend';
import { defaultCartState } from '../../../data/cart/default-state';
import Checkout from '../block';

jest.mock( '@wordpress/data', () => {
	const wpData = jest.requireActual( 'wordpress-data-wp-6-7' );
	return {
		__esModule: true,
		...wpData,
	};
} );

jest.mock( '@wordpress/compose', () => ( {
	...jest.requireActual( '@wordpress/compose' ),
	useResizeObserver: jest.fn().mockReturnValue( [ null, { width: 0 } ] ),
} ) );

global.ResizeObserver = jest.fn().mockImplementation( () => ( {
	observe: jest.fn(),
	unobserve: jest.fn(),
	disconnect: jest.fn(),
} ) );

global.IntersectionObserver = jest.fn().mockImplementation( () => ( {
	observe: jest.fn(),
	unobserve: jest.fn(),
	disconnect: jest.fn(),
} ) );

jest.mock( '@wordpress/element', () => {
	return {
		...jest.requireActual( '@wordpress/element' ),
		useId: () => {
			return 'mock-id';
		},
	};
} );

jest.mock( '../context', () => {
	return {
		...jest.requireActual( '../context' ),
		useCheckoutBlockContext: jest.fn().mockReturnValue( {
			showFormStepNumbers: false,
			cartPageId: 0,
			requireCompanyField: false,
			requirePhoneField: false,
			showApartmentField: false,
			showCompanyField: false,
			showOrderNotes: false,
			showPhoneField: false,
			showPolicyLinks: false,
			showRateAfterTaxName: false,
			showReturnToCart: false,
		} ),
	};
} );

/** @type {jest.Mock} */
const useCheckoutBlockContext =
	jest.requireMock( '../context' ).useCheckoutBlockContext;

const CheckoutBlock = () => {
	return (
		<Checkout attributes={ {} }>
			<Fields>
				<ExpressPayment />
				<ContactInformation />
				<ShippingMethod />
				<PickupOptions />
				<ShippingAddress />
				<BillingAddress />
				<ShippingMethods />
				<AdditionalInformation />
				<Payment />
				<OrderNote />
				<Terms
					checkbox={ true }
					showSeparator={ false }
					text={ termsCheckboxDefaultText }
				/>
				<Actions />
			</Fields>
			<Totals>
				<OrderSummary>
					<CartItems />
					<CouponForm />
					<Subtotal />
					<Fee />
					<Discount />
					<Shipping />
					<Taxes />
				</OrderSummary>
			</Totals>
		</Checkout>
	);
};

describe( 'Testing Checkout', () => {
	beforeEach( () => {
		// Set up MSW handlers for cart API
		server.use(
			http.get( '/wc/store/v1/cart', () => {
				return HttpResponse.json( previewCart );
			} )
		);
		act( () => {
			// need to clear the store resolution state between tests.
			dispatch( cartStore ).invalidateResolutionForStore();
			dispatch( cartStore ).receiveCart( defaultCartState.cartData );
		} );

		act( () => {
			const PaymentMethodContent = () => <div>A payment method</div>;
			registerPaymentMethod( {
				name: 'test-payment-method',
				label: 'Payment method with cards',
				content: <PaymentMethodContent />,
				edit: <PaymentMethodContent />,
				icons: null,
				canMakePayment: () => true,
				supports: {
					showSavedCards: true,
					showSaveOption: true,
					features: [ 'products' ],
				},
				ariaLabel: 'Test Payment Method',
			} );
		} );
	} );

	afterEach( () => {
		// MSW handlers are reset automatically in the global setup
	} );

	it( 'Renders checkout if there are items in the cart', async () => {
		render( <CheckoutBlock /> );

		await waitFor( () =>
			expect( screen.getByText( /Place Order/i ) ).toBeVisible()
		);
	} );

	it( 'Allows saving payment method if the customer is creating an account or has already logged in', async () => {
		const { rerender } = render( <CheckoutBlock /> );

		expect(
			await screen.findByText( /Payment method with cards/i )
		).toBeVisible();

		expect(
			screen.getByRole( 'checkbox', {
				name: 'Save payment information to my account for future purchases.',
			} )
		).toBeVisible();

		act( () => {
			dispatch( checkoutStore ).__internalSetCustomerId( 0 );
		} );

		rerender( <CheckoutBlock /> );

		expect(
			screen.queryByRole( 'checkbox', {
				name: 'Save payment information to my account for future purchases.',
			} )
		).not.toBeInTheDocument();

		act( () => {
			allSettings.checkoutAllowsGuest = true;
			allSettings.checkoutAllowsSignup = true;
			dispatch( checkoutStore ).__internalSetCustomerId( 0 );
			dispatch( checkoutStore ).__internalSetShouldCreateAccount( true );
		} );

		rerender( <CheckoutBlock /> );

		expect(
			screen.getByRole( 'checkbox', {
				name: 'Save payment information to my account for future purchases.',
			} )
		).toBeInTheDocument();

		act( () => {
			dispatch( checkoutStore ).__internalSetShouldCreateAccount( false );
		} );

		rerender( <CheckoutBlock /> );

		expect(
			screen.queryByRole( 'checkbox', {
				name: 'Save payment information to my account for future purchases.',
			} )
		).not.toBeInTheDocument();

		act( () => {
			allSettings.checkoutAllowsGuest = false;
			allSettings.checkoutAllowsSignup = true;
		} );

		rerender( <CheckoutBlock /> );

		expect(
			screen.getByRole( 'checkbox', {
				name: 'Save payment information to my account for future purchases.',
			} )
		).toBeVisible();

		// cleanup
		act( () => {
			allSettings.checkoutAllowsGuest = undefined;
			allSettings.checkoutAllowsSignup = undefined;
			dispatch( checkoutStore ).__internalSetCustomerId( 1 );
		} );
	} );

	it( 'Renders the shipping address card if the address is filled and the cart contains a shippable product', async () => {
		act( () => {
			const cartWithAddress = {
				...previewCart,
				shipping_address: {
					first_name: 'First Name',
					last_name: 'Last Name',
					company: '',
					address_1: 'Address 1',
					address_2: '',
					city: 'Toronto',
					state: 'ON',
					postcode: 'M4W 1A6',
					country: 'CA',
					phone: '',
				},
				billing_address: {
					first_name: 'First Name',
					last_name: 'Last Name',
					company: '',
					address_1: 'Address 1',
					address_2: '',
					city: 'Toronto',
					state: 'ON',
					postcode: 'M4W 1A6',
					country: 'CA',
					phone: '',
					email: '',
				},
			};
			// Override the MSW handler with cart that has address
			server.use(
				http.get( '/wc/store/v1/cart', () => {
					return HttpResponse.json( cartWithAddress );
				} )
			);
		} );
		const { rerender } = render( <CheckoutBlock /> );

		await waitFor( () =>
			expect(
				screen.getByRole( 'button', { name: 'Edit shipping address' } )
			).toBeVisible()
		);

		expect(
			screen.getByText( 'Toronto ON M4W 1A6', {
				selector: '.wc-block-components-address-card span',
			} )
		).toBeVisible();

		// Async is needed here despite the IDE warnings. Testing Library gives a warning if not awaited.
		await act( () =>
			dispatch( cartStore ).setShippingAddress( {
				first_name: 'First Name JP',
				last_name: 'Last Name JP',
				company: '',
				address_1: 'Address 1 JP',
				address_2: '',
				city: 'Kobe',
				state: 'JP28',
				postcode: '650-0000',
				country: 'JP',
				phone: '',
			} )
		);
		rerender( <CheckoutBlock /> );

		expect(
			screen.getByText( 'Hyogo Kobe Address 1 JP', {
				selector: '.wc-block-components-address-card span',
			} )
		).toBeInTheDocument();

		// Testing the default address format
		await act( () =>
			dispatch( cartStore ).setShippingAddress( {
				first_name: 'First Name GB',
				last_name: 'Last Name GB',
				company: '',
				address_1: 'Address 1 GB',
				address_2: '',
				city: 'Liverpool',
				state: 'Merseyside',
				postcode: 'L1 0BP',
				country: 'GB',
				phone: '',
			} )
		);
		rerender( <CheckoutBlock /> );

		expect(
			screen.getByText( 'Liverpool', {
				selector: '.wc-block-components-address-card span',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Merseyside', {
				selector: '.wc-block-components-address-card span',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'L1 0BP', {
				selector: '.wc-block-components-address-card span',
			} )
		).toBeInTheDocument();
	} );

	it( 'Renders the billing address card if the address is filled and the cart contains a virtual product', async () => {
		act( () => {
			const cartWithVirtualProduct = {
				...previewCart,
				needs_shipping: false,
			};
			// Override the MSW handler with virtual product cart
			server.use(
				http.get( '/wc/store/v1/cart', () => {
					return HttpResponse.json( cartWithVirtualProduct );
				} )
			);
		} );
		render( <CheckoutBlock /> );

		await waitFor( () =>
			expect(
				screen.getByRole( 'button', { name: 'Edit billing address' } )
			).toBeVisible()
		);
	} );

	it( 'Ensures checkbox labels have unique IDs', async () => {
		await act( async () => {
			// Set required settings
			allSettings.checkoutAllowsGuest = true;
			allSettings.checkoutAllowsSignup = true;
			dispatch( checkoutStore ).__internalSetCustomerId( 0 );
		} );

		// Render the CheckoutBlock
		render( <CheckoutBlock /> );

		// Query for all checkboxes
		const checkboxes = screen.getAllByRole( 'checkbox' );

		// Extract IDs from checkboxes
		const ids = checkboxes.map( ( checkbox ) => checkbox.id );

		// Ensure all IDs are unique
		const uniqueIds = new Set( ids );
		expect( uniqueIds.size ).toBe( ids.length );

		await act( async () => {
			// Restore initial settings
			allSettings.checkoutAllowsGuest = undefined;
			allSettings.checkoutAllowsSignup = undefined;
			dispatch( checkoutStore ).__internalSetCustomerId( 1 );
		} );
	} );

	it( 'Ensures correct classes are applied to FormStep when step numbers are shown/hidden', async () => {
		const mockReturnValue = {
			showFormStepNumbers: false,
			cartPageId: 0,
			requireCompanyField: false,
			requirePhoneField: false,
			showApartmentField: false,
			showCompanyField: false,
			showOrderNotes: false,
			showPhoneField: false,
			showPolicyLinks: false,
			showRateAfterTaxName: false,
			showReturnToCart: false,
		};
		useCheckoutBlockContext.mockReturnValue( mockReturnValue );
		// Render the CheckoutBlock
		const { container, rerender } = render( <CheckoutBlock /> );

		let formStepsWithNumber = container.querySelectorAll(
			'.wc-block-components-checkout-step--with-step-number'
		);

		expect( formStepsWithNumber ).toHaveLength( 0 );

		useCheckoutBlockContext.mockReturnValue( {
			...mockReturnValue,
			showFormStepNumbers: true,
		} );

		rerender( <CheckoutBlock /> );

		formStepsWithNumber = container.querySelectorAll(
			'.wc-block-components-checkout-step--with-step-number'
		);

		expect( formStepsWithNumber.length ).not.toBe( 0 );
	} );

	it( 'Shows guest checkout text', async () => {
		act( () => {
			allSettings.checkoutAllowsGuest = true;
			allSettings.checkoutAllowsSignup = true;
			dispatch( checkoutStore ).__internalSetCustomerId( 0 );
		} );

		// Render the CheckoutBlock
		const { rerender, queryByText } = render( <CheckoutBlock /> );

		// Wait for the component to fully load
		await waitFor( () =>
			expect(
				screen.getByText(
					/You are currently checking out as a guest./i
				)
			).toBeVisible()
		);

		act( () => {
			allSettings.checkoutAllowsGuest = true;
			allSettings.checkoutAllowsSignup = true;
			dispatch( checkoutStore ).__internalSetCustomerId( 1 );
		} );

		rerender( <CheckoutBlock /> );

		expect(
			queryByText( /You are currently checking out as a guest./i )
		).not.toBeInTheDocument();

		act( () => {
			// Restore initial settings
			allSettings.checkoutAllowsGuest = undefined;
			allSettings.checkoutAllowsSignup = undefined;
			dispatch( checkoutStore ).__internalSetCustomerId( 1 );
		} );
	} );

	it( "Ensures hidden postcode fields don't block Checkout", async () => {
		const user = userEvent.setup();
		render( <CheckoutBlock /> );

		await waitFor( () =>
			expect( screen.getByText( /Place Order/i ) ).toBeVisible()
		);

		const shippingForm = screen.getByRole( 'group', {
			name: /shipping address/i,
		} );
		const countrySelect =
			within( shippingForm ).getByLabelText( /Country\/Region/i );

		await act( async () => {
			await user.selectOptions( countrySelect, 'Austria' );
		} );

		expect(
			await within( shippingForm ).findByLabelText( /Postal code/i )
		).toBeInTheDocument();

		await act( async () => {
			await user.selectOptions( countrySelect, 'Spain' );
		} );

		expect(
			within( shippingForm ).queryByLabelText( /Postal code/i )
		).not.toBeInTheDocument();

		// Currently visible fields
		const fields = {
			email: screen.getByLabelText(
				/Email address/i
			) as HTMLInputElement,
			firstName: within( shippingForm ).getByLabelText(
				/First name/i
			) as HTMLInputElement,
			lastName: within( shippingForm ).getByLabelText(
				/Last name/i
			) as HTMLInputElement,
			address1: within( shippingForm ).getByLabelText(
				'Address'
			) as HTMLInputElement,
			city: within( shippingForm ).getByLabelText(
				/City/i
			) as HTMLInputElement,
			state: within( shippingForm ).getByLabelText(
				/Province/i
			) as HTMLSelectElement,
			terms: screen.getByRole( 'checkbox', {
				name: /terms and conditions/i,
			} ) as HTMLInputElement,
		};

		const fieldValues: Record< keyof typeof fields, string | boolean > = {
			email: 'test@test.com',
			firstName: 'John',
			lastName: 'Doe',
			address1: '123 Main St',
			city: 'BCN',
			state: 'Barcelona',
			terms: true,
		};

		// Fill the fields
		await act( async () => {
			for ( const [ key, value ] of Object.entries( fieldValues ) ) {
				switch ( key ) {
					case 'terms':
						if ( fields.terms.checked !== value ) {
							await user.click( fields.terms );
						}
						break;
					case 'state':
						await user.selectOptions(
							fields.state,
							value as string
						);
						break;
					default:
						await user.type(
							fields[ key as keyof typeof fields ],
							value as string
						);
				}
			}
		} );

		// wait for form to be ready
		await waitFor( () =>
			expect(
				screen.getByRole( 'button', { name: /Place order/i } )
			).toBeVisible()
		);

		// Submit the form
		await act( async () => {
			await user.click(
				screen.getByRole( 'button', { name: /Place order/i } )
			);
		} );

		// Given we're checking for invisible errors here, reaching to the data store is a good option.
		expect( select( validationStore ).hasValidationErrors() ).toBe( false );
	} );
} );
