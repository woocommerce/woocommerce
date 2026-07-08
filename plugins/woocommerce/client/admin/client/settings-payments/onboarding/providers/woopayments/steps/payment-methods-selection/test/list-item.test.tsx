/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { type RecommendedPaymentMethod } from '@woocommerce/data';
import { type ReactNode } from 'react';

type MockComponentProps = {
	children?: ReactNode;
	className?: string;
	href?: string;
	intent?: string;
	openInNewTab?: boolean;
	rel?: string;
	spokenMessage?: string;
	'data-testid'?: string;
};

jest.mock( '@wordpress/components', () => ( {
	ToggleControl: ( { checked }: { checked?: boolean } ) => (
		<input type="checkbox" checked={ checked } readOnly />
	),
} ) );

jest.mock( '@wordpress/ui', () => ( {
	Notice: {
		Root: ( {
			children,
			className,
			intent,
			spokenMessage,
			'data-testid': testId,
		}: MockComponentProps ) => (
			<div
				className={ className }
				data-intent={ intent }
				data-spoken-message={ spokenMessage }
				data-testid={ testId }
			>
				{ children }
			</div>
		),
		Description: ( { children }: MockComponentProps ) => (
			<span>{ children }</span>
		),
		Actions: ( { children }: MockComponentProps ) => (
			<div>{ children }</div>
		),
		ActionLink: ( {
			children,
			href,
			openInNewTab,
			rel,
		}: MockComponentProps ) => (
			// eslint-disable-next-line react/jsx-no-target-blank -- The mock forwards rel so tests can assert the component contract.
			<a
				href={ href }
				rel={ rel }
				target={ openInNewTab ? '_blank' : undefined }
			>
				{ children }
			</a>
		),
	},
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

	describe( 'Payment method notice', () => {
		it( 'renders a notice when method is enabled and notice.message is set', () => {
			const method = createMethod( {
				id: 'p24',
				notice: {
					badge: 'Verification required',
					message: 'Strict requirements &amp; eligibility apply.',
					link_text: 'Review requirements &amp; terms',
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

			const notice = screen.getByTestId( 'payment-method-notice-info' );
			expect( notice ).toHaveAttribute( 'data-intent', 'info' );
			expect( notice ).toHaveAttribute(
				'data-spoken-message',
				'Strict requirements & eligibility apply.'
			);
			expect( notice ).toHaveTextContent(
				'Strict requirements & eligibility apply.'
			);
			const requirementsLink = screen.getByRole( 'link', {
				name: /review requirements & terms/i,
			} );
			expect( requirementsLink ).toHaveAttribute(
				'href',
				'https://example.com/docs'
			);
			expect( requirementsLink ).toHaveAttribute( 'target', '_blank' );
			expect( requirementsLink ).toHaveAttribute(
				'rel',
				'noopener noreferrer'
			);
		} );

		it( 'does not render a notice when method is disabled', () => {
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

		it( 'does not render a notice when notice.message is empty', () => {
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
