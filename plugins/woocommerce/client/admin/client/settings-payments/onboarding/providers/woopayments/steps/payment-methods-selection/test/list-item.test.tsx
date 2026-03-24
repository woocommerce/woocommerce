/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { type RecommendedPaymentMethod } from '@woocommerce/data';

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
	describe( 'Notice badge', () => {
		it( 'renders a badge chip when notice.badge is set', () => {
			const method = createMethod( {
				notice: {
					badge: 'Verification required',
					message: '',
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
		it( 'renders a warning notice with HTML message when method is enabled', () => {
			const method = createMethod( {
				id: 'p24',
				notice: {
					badge: 'Verification required',
					message:
						'Strict requirements apply. <a href="https://example.com/docs" target="_blank" rel="noreferrer noopener">Review requirements</a>',
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
				screen.getByRole( 'link', {
					name: /review requirements/i,
				} )
			).toHaveAttribute( 'href', 'https://example.com/docs' );
		} );

		it( 'does not render a warning notice when method is disabled', () => {
			const method = createMethod( {
				id: 'p24',
				notice: {
					badge: 'Verification required',
					message: 'Strict requirements apply.',
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
				screen.queryByTestId( 'payment-method-notice-warning' )
			).not.toBeInTheDocument();
		} );

		it( 'does not render a warning notice when notice.message is empty', () => {
			const method = createMethod( {
				notice: {
					badge: 'Verification required',
					message: '',
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
				screen.queryByTestId( 'payment-method-notice-warning' )
			).not.toBeInTheDocument();
		} );

		it( 'renders notice with plain text message', () => {
			const method = createMethod( {
				id: 'p24',
				notice: {
					badge: '',
					message: 'Warning message.',
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
		} );
	} );
} );
