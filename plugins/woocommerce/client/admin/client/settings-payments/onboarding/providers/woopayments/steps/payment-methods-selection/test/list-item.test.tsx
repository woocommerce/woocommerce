/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { type RecommendedPaymentMethod } from '@woocommerce/data';

const mockSpeak = jest.fn();
jest.mock( '@wordpress/a11y', () => ( {
	speak: ( ...args: unknown[] ) => mockSpeak( ...args ),
} ) );

/**
 * Internal dependencies
 */
import { PaymentMethodListItem } from '../list-item';

const createMethod = (
	overrides: Partial< RecommendedPaymentMethod > = {}
): RecommendedPaymentMethod => ( {
	id: 'test_method',
	_order: 0,
	title: 'Test Method',
	description: 'A test payment method.',
	icon: 'https://example.com/icon.png',
	enabled: false,
	extraTitle: '',
	extraDescription: '',
	extraIcon: '',
	...overrides,
} );

const defaultProps = {
	paymentMethodsState: { test_method: false } as Record< string, boolean >,
	setPaymentMethodsState: jest.fn(),
	isExpanded: true,
	initialVisibilityStatus: true,
};

describe( 'PaymentMethodListItem', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'Notice badge', () => {
		it( 'renders a badge chip when notice.badge is set', () => {
			const method = createMethod( {
				notice: {
					badge: 'Verification required',
					message: '',
					link_text: '',
					link_url: '',
				},
			} );

			render(
				<PaymentMethodListItem { ...defaultProps } method={ method } />
			);

			expect(
				screen.getByText( 'Verification required' )
			).toBeInTheDocument();
		} );

		it( 'does not render a badge chip when notice.badge is empty', () => {
			const method = createMethod( {
				notice: {
					badge: '',
					message: 'Some warning.',
					link_text: '',
					link_url: '',
				},
			} );

			render(
				<PaymentMethodListItem { ...defaultProps } method={ method } />
			);

			expect(
				screen.queryByTestId( 'payment-method-notice-badge' )
			).not.toBeInTheDocument();
		} );

		it( 'does not render a badge chip when notice is not provided', () => {
			const method = createMethod();

			render(
				<PaymentMethodListItem { ...defaultProps } method={ method } />
			);

			expect(
				screen.queryByTestId( 'payment-method-notice-badge' )
			).not.toBeInTheDocument();
		} );
	} );

	describe( 'Warning notice', () => {
		it( 'renders a warning notice when method is enabled and notice.message is set', () => {
			const method = createMethod( {
				id: 'p24',
				notice: {
					badge: 'Verification required',
					message: 'Strict requirements apply.',
					link_text: 'Review requirements',
					link_url: 'https://example.com/docs',
				},
			} );

			render(
				<PaymentMethodListItem
					{ ...defaultProps }
					method={ method }
					paymentMethodsState={ { p24: true } }
				/>
			);

			expect(
				screen.getByText( 'Strict requirements apply.' )
			).toBeInTheDocument();
			expect(
				screen.getByRole( 'link', { name: /review requirements/i } )
			).toHaveAttribute( 'href', 'https://example.com/docs' );
		} );

		it( 'does not render a warning notice when method is disabled', () => {
			const method = createMethod( {
				id: 'p24',
				notice: {
					badge: 'Verification required',
					message: 'Strict requirements apply.',
					link_text: 'Review requirements',
					link_url: 'https://example.com/docs',
				},
			} );

			render(
				<PaymentMethodListItem
					{ ...defaultProps }
					method={ method }
					paymentMethodsState={ { p24: false } }
				/>
			);

			expect(
				screen.queryByText( 'Strict requirements apply.' )
			).not.toBeInTheDocument();
		} );

		it( 'does not render a warning notice when notice.message is empty', () => {
			const method = createMethod( {
				notice: {
					badge: 'Verification required',
					message: '',
					link_text: '',
					link_url: '',
				},
			} );

			render(
				<PaymentMethodListItem
					{ ...defaultProps }
					method={ method }
					paymentMethodsState={ { test_method: true } }
				/>
			);

			expect(
				screen.queryByTestId( 'payment-method-notice-info' )
			).not.toBeInTheDocument();
		} );

		it( 'shows notice after rerender with enabled state', () => {
			const method = createMethod( {
				id: 'p24',
				notice: {
					badge: 'Verification required',
					message: 'Strict requirements apply.',
					link_text: '',
					link_url: '',
				},
			} );

			const { rerender } = render(
				<PaymentMethodListItem
					{ ...defaultProps }
					method={ method }
					paymentMethodsState={ { p24: false } }
				/>
			);

			expect(
				screen.queryByTestId( 'payment-method-notice-info' )
			).not.toBeInTheDocument();

			rerender(
				<PaymentMethodListItem
					{ ...defaultProps }
					method={ method }
					paymentMethodsState={ { p24: true } }
				/>
			);

			expect(
				screen.getByTestId( 'payment-method-notice-info' )
			).toBeInTheDocument();
		} );

		it( 'renders notice without link when link_url is empty', () => {
			const method = createMethod( {
				id: 'p24',
				notice: {
					badge: '',
					message: 'Warning message.',
					link_text: 'Click here',
					link_url: '',
				},
			} );

			render(
				<PaymentMethodListItem
					{ ...defaultProps }
					method={ method }
					paymentMethodsState={ { p24: true } }
				/>
			);

			expect(
				screen.getByText( 'Warning message.' )
			).toBeInTheDocument();
			expect(
				screen.queryByRole( 'link', { name: /click here/i } )
			).not.toBeInTheDocument();
		} );
	} );
} );
