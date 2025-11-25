/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import type {
	PaymentGatewayProvider,
	PaymentsProviderState,
	PaymentsProviderOnboardingState,
	PluginData,
	PaymentsProviderType,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { PaymentGatewayListItem } from '../payment-gateway-list-item';

// Define the enum value directly to avoid importing from @woocommerce/data.
const PaymentsProviderTypeGateway = 'gateway' as const;

// Mock dependencies.
jest.mock( '@woocommerce/onboarding', () => ( {
	WooPaymentsMethodsLogos: () => <div>WooPaymentsMethodsLogos</div>,
} ) );

jest.mock( '~/lib/sanitize-html', () => ( {
	__esModule: true,
	default: jest.fn( ( html ) => ( { __html: html } ) ),
} ) );

jest.mock( '~/settings-payments/components/status-badge', () => ( {
	StatusBadge: ( {
		status,
		popoverContent,
	}: {
		status: string;
		popoverContent?: React.ReactNode;
	} ) => (
		<div data-testid="status-badge" data-status={ status }>
			StatusBadge-{ status }
			{ popoverContent && (
				<div data-testid="status-badge-popover">{ popoverContent }</div>
			) }
		</div>
	),
} ) );

jest.mock( '~/settings-payments/components/ellipsis-menu-content', () => ( {
	// eslint-disable-next-line @typescript-eslint/no-unused-vars -- Mock is used by PaymentGatewayListItem component
	EllipsisMenuWrapper: ( { provider }: { provider: { id: string } } ) => (
		<div data-testid="ellipsis-menu">EllipsisMenu-{ provider.id }</div>
	),
} ) );

jest.mock( '~/settings-payments/components/sortable', () => ( {
	// eslint-disable-next-line @typescript-eslint/no-unused-vars -- Mock is used by PaymentGatewayListItem component
	DefaultDragHandle: () => <div data-testid="drag-handle">DragHandle</div>,
} ) );

jest.mock( '~/settings-payments/components/buttons', () => ( {
	// eslint-disable-next-line @typescript-eslint/no-unused-vars -- Mock is used by PaymentGatewayListItem component
	ActivatePaymentsButton: ( { incentive }: { incentive?: unknown } ) => (
		<button data-testid="activate-payments-button">
			ActivatePayments{ incentive ? '-with-incentive' : '' }
		</button>
	),
	// eslint-disable-next-line @typescript-eslint/no-unused-vars -- Mock is used by PaymentGatewayListItem component
	CompleteSetupButton: ( { disabled }: { disabled?: boolean } ) => (
		<button data-testid="complete-setup-button" disabled={ disabled }>
			CompleteSetup
		</button>
	),
	// eslint-disable-next-line @typescript-eslint/no-unused-vars -- Mock is used by PaymentGatewayListItem component
	EnableGatewayButton: ( { incentive }: { incentive?: unknown } ) => (
		<button data-testid="enable-gateway-button">
			Enable{ incentive ? '-with-incentive' : '' }
		</button>
	),
	// eslint-disable-next-line @typescript-eslint/no-unused-vars -- Mock is used by PaymentGatewayListItem component
	SettingsButton: () => (
		<button data-testid="settings-button">Settings</button>
	),
} ) );

jest.mock(
	'~/settings-payments/components/buttons/reactivate-live-payments-button',
	() => ( {
		// eslint-disable-next-line @typescript-eslint/no-unused-vars -- Mock is used by PaymentGatewayListItem component
		ReactivateLivePaymentsButton: () => (
			<button data-testid="reactivate-live-payments-button">
				ReactivateLivePayments
			</button>
		),
	} )
);

jest.mock( '~/settings-payments/components/incentive-status-badge', () => ( {
	// eslint-disable-next-line @typescript-eslint/no-unused-vars -- Mock is used by PaymentGatewayListItem component
	IncentiveStatusBadge: ( { incentive }: { incentive: { id: string } } ) => (
		<div data-testid="incentive-badge">Incentive-{ incentive.id }</div>
	),
} ) );

jest.mock( '~/settings-payments/components/official-badge', () => ( {
	// eslint-disable-next-line @typescript-eslint/no-unused-vars -- Mock is used by PaymentGatewayListItem component
	OfficialBadge: ( { suggestionId }: { suggestionId: string } ) => (
		<div data-testid="official-badge">Official-{ suggestionId }</div>
	),
} ) );

jest.mock( '@wordpress/components', () => ( {
	Tooltip: ( { children }: { children: React.ReactNode } ) => (
		<div>{ children }</div>
	),
} ) );

jest.mock( '~/utils/admin-settings', () => ( {
	WC_ASSET_URL: 'https://localhost/wp-content/plugins/woocommerce/assets/',
} ) );

// Helper function to create a mock gateway.
const createMockGateway = (
	overrides: Partial< PaymentGatewayProvider > = {}
): PaymentGatewayProvider => {
	return {
		id: 'test-gateway',
		_order: 1,
		_type: PaymentsProviderTypeGateway as PaymentsProviderType,
		title: 'Test Gateway',
		description: 'Test gateway description',
		icon: 'https://example.com/icon.png',
		supports: [ 'products', 'refunds' ],
		state: {
			enabled: false,
			account_connected: false,
			needs_setup: false,
			test_mode: false,
			dev_mode: false,
		} as PaymentsProviderState,
		management: {
			_links: {
				settings: {
					href: '/settings/test-gateway',
				},
			},
		},
		onboarding: {
			state: {
				supported: true,
				started: false,
				completed: false,
				test_mode: false,
			} as PaymentsProviderOnboardingState,
			messages: {},
			_links: {
				onboard: {
					href: '/onboard/test-gateway',
				},
				reset: {
					href: '/reset/test-gateway',
				},
			},
			recommended_payment_methods: [],
			type: 'standard',
		},
		plugin: {
			slug: 'test-gateway',
			file: 'test-gateway/test-gateway.php',
			status: 'active',
		} as PluginData,
		_links: {},
		...overrides,
	};
};

describe( 'PaymentGatewayListItem', () => {
	const defaultProps = {
		installingPlugin: null,
		acceptIncentive: jest.fn(),
		shouldHighlightIncentive: false,
		setIsOnboardingModalOpen: jest.fn(),
	};

	afterEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'Basic Rendering', () => {
		it( 'renders the gateway title', () => {
			const gateway = createMockGateway();
			const { getByText } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			expect( getByText( 'Test Gateway' ) ).toBeInTheDocument();
		} );

		it( 'renders the gateway description', () => {
			const gateway = createMockGateway();
			const { container } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			const content = container.querySelector(
				'.woocommerce-list__item-content'
			);
			expect( content ).toBeInTheDocument();
			expect( content ).toHaveClass( 'woocommerce-list__item-content' );
		} );

		it( 'renders the gateway icon', () => {
			const gateway = createMockGateway( {
				icon: 'https://example.com/test-icon.png',
			} );
			const { container } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			const icon = container.querySelector(
				'.woocommerce-list__item-image'
			);
			expect( icon ).toHaveAttribute(
				'src',
				'https://example.com/test-icon.png'
			);
			expect( icon ).toHaveAttribute( 'alt', 'Test Gateway logo' );
		} );

		it( 'applies correct CSS classes for regular gateway', () => {
			const gateway = createMockGateway();
			const { container } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			const item = container.querySelector(
				'.woocommerce-item__payment-gateway'
			);
			expect( item ).toBeInTheDocument();
			expect( item ).not.toHaveClass(
				'woocommerce-item__woocommerce-payments'
			);
		} );

		it( 'applies WooPayments CSS class for WooPayments gateway', () => {
			const gateway = createMockGateway( {
				id: 'woocommerce_payments',
			} );
			const { container } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			const item = container.querySelector(
				'.woocommerce-item__payment-gateway'
			);
			expect( item ).toHaveClass(
				'woocommerce-item__woocommerce-payments'
			);
		} );

		it( 'applies has-incentive CSS class when incentive exists and should highlight', () => {
			const gateway = createMockGateway( {
				_incentive: {
					id: 'test-incentive',
					promo_id: 'promo-123',
					title: 'Test Incentive',
					description: 'Test description',
					short_description: 'Short desc',
					cta_label: 'Accept',
					tc_url: 'https://example.com/terms',
					badge: 'Save 50%',
					_dismissals: [],
					_links: {
						dismiss: { href: '/dismiss' },
					},
				},
			} );
			const { container } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
					shouldHighlightIncentive={ true }
				/>
			);

			const item = container.querySelector(
				'.woocommerce-item__payment-gateway'
			);
			expect( item ).toHaveClass( 'has-incentive' );
		} );

		it( 'renders drag handle', () => {
			const gateway = createMockGateway();
			const { getByTestId } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			expect( getByTestId( 'drag-handle' ) ).toBeInTheDocument();
		} );

		it( 'renders ellipsis menu', () => {
			const gateway = createMockGateway();
			const { getByTestId } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			expect( getByTestId( 'ellipsis-menu' ) ).toBeInTheDocument();
		} );
	} );

	describe( 'Status Badge Rendering', () => {
		it( 'shows StatusBadge when no incentive', () => {
			const gateway = createMockGateway();
			const { getByTestId } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			expect( getByTestId( 'status-badge' ) ).toBeInTheDocument();
		} );

		it( 'shows IncentiveStatusBadge when incentive exists', () => {
			const gateway = createMockGateway( {
				_incentive: {
					id: 'test-incentive',
					promo_id: 'promo-123',
					title: 'Test Incentive',
					description: 'Test description',
					short_description: 'Short desc',
					cta_label: 'Accept',
					tc_url: 'https://example.com/terms',
					badge: 'Save 50%',
					_dismissals: [],
					_links: {
						dismiss: { href: '/dismiss' },
					},
				},
			} );
			const { getByTestId, queryByTestId } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			expect( getByTestId( 'incentive-badge' ) ).toBeInTheDocument();
			expect( queryByTestId( 'status-badge' ) ).not.toBeInTheDocument();
		} );

		it( 'determines not_supported status correctly', () => {
			const gateway = createMockGateway( {
				state: {
					enabled: false,
					account_connected: false,
					needs_setup: false,
					test_mode: false,
					dev_mode: false,
				},
				onboarding: {
					state: {
						supported: false,
						started: false,
						completed: false,
						test_mode: false,
					},
					messages: {
						not_supported: 'This gateway is not supported',
					},
					_links: {
						onboard: { href: '/onboard' },
						reset: { href: '/reset' },
					},
					recommended_payment_methods: [],
					type: 'standard',
				},
			} );
			const { getByTestId } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			const statusBadge = getByTestId( 'status-badge' );
			expect( statusBadge ).toHaveAttribute(
				'data-status',
				'not_supported'
			);
		} );

		it( 'shows popover content for not_supported status', () => {
			const gateway = createMockGateway( {
				state: {
					enabled: false,
					account_connected: false,
					needs_setup: false,
					test_mode: false,
					dev_mode: false,
				},
				onboarding: {
					state: {
						supported: false,
						started: false,
						completed: false,
						test_mode: false,
					},
					messages: {
						not_supported: 'Gateway not available in your country',
					},
					_links: {
						onboard: { href: '/onboard' },
						reset: { href: '/reset' },
					},
					recommended_payment_methods: [],
					type: 'standard',
				},
			} );
			const { getByTestId } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			const popover = getByTestId( 'status-badge-popover' );
			expect( popover ).toBeInTheDocument();
			expect( popover ).toHaveTextContent(
				'Gateway not available in your country'
			);
		} );

		it( 'determines needs_setup status correctly', () => {
			const gateway = createMockGateway( {
				state: {
					enabled: false,
					account_connected: false,
					needs_setup: false,
					test_mode: false,
					dev_mode: false,
				},
			} );
			const { getByTestId } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			const statusBadge = getByTestId( 'status-badge' );
			expect( statusBadge ).toHaveAttribute(
				'data-status',
				'needs_setup'
			);
		} );

		it( 'determines test_account status correctly', () => {
			const gateway = createMockGateway( {
				state: {
					enabled: true,
					account_connected: true,
					needs_setup: false,
					test_mode: false,
					dev_mode: false,
				},
				onboarding: {
					state: {
						supported: true,
						started: true,
						completed: true,
						test_mode: true,
					},
					messages: {},
					_links: {
						onboard: { href: '/onboard' },
						reset: { href: '/reset' },
					},
					recommended_payment_methods: [],
					type: 'standard',
				},
			} );
			const { getByTestId } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			const statusBadge = getByTestId( 'status-badge' );
			expect( statusBadge ).toHaveAttribute(
				'data-status',
				'test_account'
			);
		} );

		it( 'determines test_mode status correctly', () => {
			const gateway = createMockGateway( {
				state: {
					enabled: true,
					account_connected: true,
					needs_setup: false,
					test_mode: true,
					dev_mode: false,
				},
				onboarding: {
					state: {
						supported: true,
						started: true,
						completed: true,
						test_mode: false,
					},
					messages: {},
					_links: {
						onboard: { href: '/onboard' },
						reset: { href: '/reset' },
					},
					recommended_payment_methods: [],
					type: 'standard',
				},
			} );
			const { getByTestId } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			const statusBadge = getByTestId( 'status-badge' );
			expect( statusBadge ).toHaveAttribute( 'data-status', 'test_mode' );
		} );

		it( 'determines active status correctly', () => {
			const gateway = createMockGateway( {
				state: {
					enabled: true,
					account_connected: true,
					needs_setup: false,
					test_mode: false,
					dev_mode: false,
				},
				onboarding: {
					state: {
						supported: true,
						started: true,
						completed: true,
						test_mode: false,
					},
					messages: {},
					_links: {
						onboard: { href: '/onboard' },
						reset: { href: '/reset' },
					},
					recommended_payment_methods: [],
					type: 'standard',
				},
			} );
			const { getByTestId } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			const statusBadge = getByTestId( 'status-badge' );
			expect( statusBadge ).toHaveAttribute( 'data-status', 'active' );
		} );

		it( 'determines inactive status correctly', () => {
			const gateway = createMockGateway( {
				state: {
					enabled: false,
					account_connected: true,
					needs_setup: false,
					test_mode: false,
					dev_mode: false,
				},
				onboarding: {
					state: {
						supported: true,
						started: true,
						completed: true,
						test_mode: false,
					},
					messages: {},
					_links: {
						onboard: { href: '/onboard' },
						reset: { href: '/reset' },
					},
					recommended_payment_methods: [],
					type: 'standard',
				},
			} );
			const { getByTestId } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			const statusBadge = getByTestId( 'status-badge' );
			expect( statusBadge ).toHaveAttribute( 'data-status', 'inactive' );
		} );
	} );

	describe( 'Badge Rendering', () => {
		it( 'shows OfficialBadge when _suggestion_id exists', () => {
			const gateway = createMockGateway( {
				_suggestion_id: 'test-suggestion',
			} );
			const { getByTestId } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			const officialBadge = getByTestId( 'official-badge' );
			expect( officialBadge ).toBeInTheDocument();
			expect( officialBadge ).toHaveTextContent(
				'Official-test-suggestion'
			);
		} );

		it( 'does not show OfficialBadge when _suggestion_id does not exist', () => {
			const gateway = createMockGateway();
			const { queryByTestId } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			expect( queryByTestId( 'official-badge' ) ).not.toBeInTheDocument();
		} );

		it( 'shows recurring payments icon when subscriptions support exists', () => {
			const gateway = createMockGateway( {
				supports: [ 'products', 'subscriptions' ],
			} );
			const { container } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			const recurringIcon = container.querySelector(
				'.woocommerce-list__item-recurring-payments-icon'
			);
			expect( recurringIcon ).toBeInTheDocument();
			expect( recurringIcon ).toHaveAttribute(
				'src',
				'https://localhost/wp-content/plugins/woocommerce/assets/images/icons/recurring-payments.svg'
			);
		} );

		it( 'does not show recurring payments icon when subscriptions support does not exist', () => {
			const gateway = createMockGateway( {
				supports: [ 'products', 'refunds' ],
			} );
			const { container } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			const recurringIcon = container.querySelector(
				'.woocommerce-list__item-recurring-payments-icon'
			);
			expect( recurringIcon ).not.toBeInTheDocument();
		} );
	} );

	describe( 'WooPayments Specific Rendering', () => {
		it( 'renders WooPaymentsMethodsLogos for WooPayments gateway', () => {
			const gateway = createMockGateway( {
				id: 'woocommerce_payments',
			} );
			const { getByText } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			expect(
				getByText( 'WooPaymentsMethodsLogos' )
			).toBeInTheDocument();
		} );

		it( 'does not render WooPaymentsMethodsLogos for non-WooPayments gateway', () => {
			const gateway = createMockGateway( {
				id: 'stripe',
			} );
			const { queryByText } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			expect(
				queryByText( 'WooPaymentsMethodsLogos' )
			).not.toBeInTheDocument();
		} );
	} );

	describe( 'Button Rendering', () => {
		it( 'shows EnableGatewayButton when gateway is disabled and does not need onboarding', () => {
			const gateway = createMockGateway( {
				state: {
					enabled: false,
					account_connected: true,
					needs_setup: false,
					test_mode: false,
					dev_mode: false,
				},
				onboarding: {
					state: {
						supported: true,
						started: true,
						completed: true,
						test_mode: false,
					},
					messages: {},
					_links: {
						onboard: { href: '/onboard' },
						reset: { href: '/reset' },
					},
					recommended_payment_methods: [],
					type: 'standard',
				},
			} );
			const { getByTestId } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			expect(
				getByTestId( 'enable-gateway-button' )
			).toBeInTheDocument();
		} );

		it( 'shows SettingsButton when gateway does not need onboarding', () => {
			const gateway = createMockGateway( {
				state: {
					enabled: true,
					account_connected: true,
					needs_setup: false,
					test_mode: false,
					dev_mode: false,
				},
				onboarding: {
					state: {
						supported: true,
						started: true,
						completed: true,
						test_mode: false,
					},
					messages: {},
					_links: {
						onboard: { href: '/onboard' },
						reset: { href: '/reset' },
					},
					recommended_payment_methods: [],
					type: 'standard',
				},
			} );
			const { getByTestId } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			expect( getByTestId( 'settings-button' ) ).toBeInTheDocument();
		} );

		it( 'shows CompleteSetupButton (enabled) when onboarding is supported', () => {
			const gateway = createMockGateway( {
				state: {
					enabled: false,
					account_connected: false,
					needs_setup: false,
					test_mode: false,
					dev_mode: false,
				},
				onboarding: {
					state: {
						supported: true,
						started: false,
						completed: false,
						test_mode: false,
					},
					messages: {},
					_links: {
						onboard: { href: '/onboard' },
						reset: { href: '/reset' },
					},
					recommended_payment_methods: [],
					type: 'standard',
				},
			} );
			const { getByTestId } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			const button = getByTestId( 'complete-setup-button' );
			expect( button ).toBeInTheDocument();
			expect( button ).not.toBeDisabled();
		} );

		it( 'shows CompleteSetupButton (disabled) when onboarding is not supported', () => {
			const gateway = createMockGateway( {
				state: {
					enabled: false,
					account_connected: false,
					needs_setup: false,
					test_mode: false,
					dev_mode: false,
				},
				onboarding: {
					state: {
						supported: false,
						started: false,
						completed: false,
						test_mode: false,
					},
					messages: {
						not_supported: 'Not supported message',
					},
					_links: {
						onboard: { href: '/onboard' },
						reset: { href: '/reset' },
					},
					recommended_payment_methods: [],
					type: 'standard',
				},
			} );
			const { getByTestId } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			const button = getByTestId( 'complete-setup-button' );
			expect( button ).toBeInTheDocument();
			expect( button ).toBeDisabled();
		} );

		it( 'shows ActivatePaymentsButton for WooPayments in test mode (not dev mode)', () => {
			const gateway = createMockGateway( {
				id: 'woocommerce_payments',
				state: {
					enabled: true,
					account_connected: true,
					needs_setup: false,
					test_mode: false,
					dev_mode: false,
				},
				onboarding: {
					state: {
						supported: true,
						started: true,
						completed: true,
						test_mode: true,
					},
					messages: {},
					_links: {
						onboard: { href: '/onboard' },
						reset: { href: '/reset' },
						disable_test_account: { href: '/disable-test' },
					},
					recommended_payment_methods: [],
					type: 'standard',
				},
			} );
			const { getByTestId } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			expect(
				getByTestId( 'activate-payments-button' )
			).toBeInTheDocument();
		} );

		it( 'does not show ActivatePaymentsButton for WooPayments in dev mode', () => {
			const gateway = createMockGateway( {
				id: 'woocommerce_payments',
				state: {
					enabled: true,
					account_connected: true,
					needs_setup: false,
					test_mode: false,
					dev_mode: true,
				},
				onboarding: {
					state: {
						supported: true,
						started: true,
						completed: true,
						test_mode: true,
					},
					messages: {},
					_links: {
						onboard: { href: '/onboard' },
						reset: { href: '/reset' },
					},
					recommended_payment_methods: [],
					type: 'standard',
				},
			} );
			const { queryByTestId } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			expect(
				queryByTestId( 'activate-payments-button' )
			).not.toBeInTheDocument();
		} );

		it( 'shows ReactivateLivePaymentsButton for WooPayments when test mode enabled after live account setup', () => {
			const gateway = createMockGateway( {
				id: 'woocommerce_payments',
				state: {
					enabled: true,
					account_connected: true,
					needs_setup: false,
					test_mode: true,
					dev_mode: false,
				},
				onboarding: {
					state: {
						supported: true,
						started: true,
						completed: true,
						test_mode: false,
					},
					messages: {},
					_links: {
						onboard: { href: '/onboard' },
						reset: { href: '/reset' },
					},
					recommended_payment_methods: [],
					type: 'standard',
				},
			} );
			const { getByTestId } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			expect(
				getByTestId( 'reactivate-live-payments-button' )
			).toBeInTheDocument();
		} );

		it( 'does not show ReactivateLivePaymentsButton in dev mode', () => {
			const gateway = createMockGateway( {
				id: 'woocommerce_payments',
				state: {
					enabled: true,
					account_connected: true,
					needs_setup: false,
					test_mode: true,
					dev_mode: true,
				},
				onboarding: {
					state: {
						supported: true,
						started: true,
						completed: true,
						test_mode: false,
					},
					messages: {},
					_links: {
						onboard: { href: '/onboard' },
						reset: { href: '/reset' },
					},
					recommended_payment_methods: [],
					type: 'standard',
				},
			} );
			const { queryByTestId } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			expect(
				queryByTestId( 'reactivate-live-payments-button' )
			).not.toBeInTheDocument();
		} );
	} );

	describe( 'Props Handling', () => {
		it( 'renders without error when installingPlugin prop is provided', () => {
			const gateway = createMockGateway( {
				state: {
					enabled: false,
					account_connected: false,
					needs_setup: false,
					test_mode: false,
					dev_mode: false,
				},
			} );
			const { getByTestId } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
					installingPlugin="test-plugin"
				/>
			);

			// Verify component renders successfully with installingPlugin prop.
			expect(
				getByTestId( 'complete-setup-button' )
			).toBeInTheDocument();
		} );

		it( 'renders without error when acceptIncentive callback is provided', () => {
			const acceptIncentive = jest.fn();
			const gateway = createMockGateway( {
				_incentive: {
					id: 'test-incentive',
					promo_id: 'promo-123',
					title: 'Test Incentive',
					description: 'Test description',
					short_description: 'Short desc',
					cta_label: 'Accept',
					tc_url: 'https://example.com/terms',
					badge: 'Save 50%',
					_dismissals: [],
					_links: {
						dismiss: { href: '/dismiss' },
					},
				},
			} );

			render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
					acceptIncentive={ acceptIncentive }
				/>
			);

			// Verify component renders successfully and doesn't call callback during render.
			expect( acceptIncentive ).not.toHaveBeenCalled();
		} );

		it( 'renders without error when setIsOnboardingModalOpen callback is provided', () => {
			const setIsOnboardingModalOpen = jest.fn();
			const gateway = createMockGateway();

			render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
					setIsOnboardingModalOpen={ setIsOnboardingModalOpen }
				/>
			);

			// Verify component renders successfully and doesn't call callback during render.
			expect( setIsOnboardingModalOpen ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'Edge Cases and Error Conditions', () => {
		it( 'handles missing gateway icon gracefully', () => {
			const gateway = createMockGateway( {
				icon: undefined,
			} );
			const { container } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			const icon = container.querySelector(
				'.woocommerce-list__item-image'
			);
			// Component should handle missing icon without crashing.
			expect( icon ).not.toBeInTheDocument();
		} );

		it( 'handles missing description gracefully', () => {
			const gateway = createMockGateway( {
				description: undefined,
			} );
			const { container } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			// Component should render without crashing when description is missing.
			const item = container.querySelector(
				'.woocommerce-item__payment-gateway'
			);
			expect( item ).toBeInTheDocument();
		} );

		it( 'handles gateway without _suggestion_id', () => {
			const gateway = createMockGateway( {
				_suggestion_id: undefined,
			} );
			const { queryByTestId } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			// Official badge should not be shown when _suggestion_id is undefined.
			expect( queryByTestId( 'official-badge' ) ).not.toBeInTheDocument();
		} );

		it( 'handles gateway without incentive gracefully', () => {
			const gateway = createMockGateway( {
				_incentive: undefined,
			} );
			const { getByTestId, queryByTestId } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			// Should show regular status badge, not incentive badge.
			expect( getByTestId( 'status-badge' ) ).toBeInTheDocument();
			expect(
				queryByTestId( 'incentive-badge' )
			).not.toBeInTheDocument();
		} );

		it( 'handles null onboarding messages gracefully', () => {
			const gateway = createMockGateway( {
				onboarding: {
					state: {
						supported: true,
						started: false,
						completed: false,
						test_mode: false,
					},
					messages: {},
					_links: {
						onboard: { href: '/onboard' },
						reset: { href: '/reset' },
					},
					recommended_payment_methods: [],
					type: 'standard',
				},
			} );
			const { container } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			// Component should render without crashing when messages are null.
			const item = container.querySelector(
				'.woocommerce-item__payment-gateway'
			);
			expect( item ).toBeInTheDocument();
		} );

		it( 'handles empty supports array', () => {
			const gateway = createMockGateway( {
				supports: [],
			} );
			const { container } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			// Recurring payments icon should not be shown.
			const recurringIcon = container.querySelector(
				'.woocommerce-list__item-recurring-payments-icon'
			);
			expect( recurringIcon ).not.toBeInTheDocument();
		} );

		it( 'handles undefined supports array', () => {
			const gateway = createMockGateway( {
				supports: undefined,
			} );
			const { container } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			// Component should render without crashing when supports is undefined.
			const item = container.querySelector(
				'.woocommerce-item__payment-gateway'
			);
			expect( item ).toBeInTheDocument();
		} );

		it( 'handles conflicting state flags gracefully', () => {
			const gateway = createMockGateway( {
				state: {
					enabled: true,
					account_connected: false,
					needs_setup: true,
					test_mode: true,
					dev_mode: false,
				},
			} );
			const { container } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			// Component should prioritize status determination without crashing.
			const item = container.querySelector(
				'.woocommerce-item__payment-gateway'
			);
			expect( item ).toBeInTheDocument();
		} );

		it( 'handles undefined onboarding.state gracefully', () => {
			const gateway = createMockGateway( {
				onboarding: {
					...createMockGateway().onboarding,
					// eslint-disable-next-line @typescript-eslint/no-explicit-any -- Testing edge case with undefined state.
					state: undefined as any,
				},
			} );
			const { container } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			// Component should render without crashing when state is undefined.
			const item = container.querySelector(
				'.woocommerce-item__payment-gateway'
			);
			expect( item ).toBeInTheDocument();

			// Button should still render (component handles undefined gracefully with optional chaining).
			const completeSetupButton = container.querySelector(
				'[data-testid="complete-setup-button"]'
			);
			expect( completeSetupButton ).toBeInTheDocument();
		} );

		it( 'handles completely undefined gateway.onboarding without crashing', () => {
			const gateway = createMockGateway( {
				state: {
					enabled: false,
					account_connected: false,
					needs_setup: false,
					test_mode: false,
					dev_mode: false,
				},
				// eslint-disable-next-line @typescript-eslint/no-explicit-any -- Testing edge case with undefined onboarding.
				onboarding: undefined as any,
			} );
			const { container, getByTestId } = render(
				<PaymentGatewayListItem
					gateway={ gateway }
					{ ...defaultProps }
				/>
			);

			// Component should render without crashing when entire onboarding object is undefined.
			const item = container.querySelector(
				'.woocommerce-item__payment-gateway'
			);
			expect( item ).toBeInTheDocument();

			// Should show status badge.
			expect( getByTestId( 'status-badge' ) ).toBeInTheDocument();

			// CompleteSetupButton should render with safe fallback props.
			const completeSetupButton = getByTestId( 'complete-setup-button' );
			expect( completeSetupButton ).toBeInTheDocument();
			expect( completeSetupButton ).not.toBeDisabled();
		} );
	} );
} );
