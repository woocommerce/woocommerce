/**
 * Internal dependencies
 */
import { getSettingsPaymentsProviderRouteUrl } from '../utils';
import { getTransactionDetailsRoute } from '../money-movement/utils';
import {
	getBalanceCurrencyOptions,
	getInstantBalanceForCurrency,
	getMonthlyAnchorLabel,
	getPayoutStatusClassName,
	getSelectedBalanceCurrency,
} from '../overview/utils';
import type { WooPaymentsDepositsOverview } from '../overview/types';

const createOverview = (
	overrides: Partial< WooPaymentsDepositsOverview > = {}
): WooPaymentsDepositsOverview => ( {
	balance: {
		available: [
			{ amount: 1000, currency: 'usd' },
			{ amount: 2500, currency: 'eur' },
		],
		pending: [
			{ amount: 250, currency: 'usd' },
			{ amount: 500, currency: 'gbp' },
		],
		instant: [
			{
				amount: 900,
				currency: 'usd',
				fee: 14,
				net: 886,
				fee_percentage: 1.5,
			},
		],
	},
	account: {
		default_currency: 'usd',
	},
	deposit: {
		last_paid: [
			{
				id: 'po_cad',
				date: 1781740800000,
				type: 'deposit',
				amount: 700,
				status: 'paid',
				currency: 'cad',
			},
		],
	},
	...overrides,
} );

describe( 'getSettingsPaymentsProviderRouteUrl', () => {
	beforeEach( () => {
		window.wcSettings = {
			...window.wcSettings,
			adminUrl: 'https://example.com/wp-admin',
		};
	} );

	it( 'builds provider route URLs without query parameters', () => {
		expect(
			getSettingsPaymentsProviderRouteUrl( '/woopayments/payouts' )
		).toBe(
			'https://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Fpayouts'
		);
	} );

	it( 'builds express checkout provider route URLs', () => {
		expect(
			getSettingsPaymentsProviderRouteUrl(
				'/woopayments/settings/express-checkout/payment_request'
			)
		).toBe(
			'https://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Fsettings%2Fexpress-checkout%2Fpayment_request'
		);
	} );

	it( 'keeps route query parameters outside the encoded path', () => {
		expect(
			getSettingsPaymentsProviderRouteUrl(
				'/woopayments/settings/express-checkout/payment_request?from=settings-payments'
			)
		).toBe(
			'https://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Fsettings%2Fexpress-checkout%2Fpayment_request&from=settings-payments'
		);
	} );

	it( 'builds fraud protection settings provider route URLs', () => {
		expect(
			getSettingsPaymentsProviderRouteUrl(
				'/woopayments/settings/fraud-protection?from=woopayments-settings'
			)
		).toBe(
			'https://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Fsettings%2Ffraud-protection&from=woopayments-settings'
		);
	} );
} );

describe( 'getTransactionDetailsRoute', () => {
	it( 'uses card reader fee metadata as the transaction detail type', () => {
		expect(
			getTransactionDetailsRoute( {
				id: 'txn_reader_fee_123',
				type: 'charge',
				metadata: {
					charge_type: 'card_reader_fee',
				},
			} )
		).toBe(
			'/woopayments/transactions/details?id=txn_reader_fee_123&transaction_type=card_reader_fee'
		);
	} );
} );

describe( 'overview financial summary helpers', () => {
	it( 'extracts unique balance currency options with the default currency first', () => {
		expect( getBalanceCurrencyOptions( createOverview() ) ).toEqual( [
			'usd',
			'cad',
			'eur',
			'gbp',
		] );
	} );

	it( 'does not synthesize the account currency when no overview group uses it', () => {
		expect(
			getBalanceCurrencyOptions(
				createOverview( {
					account: {
						default_currency: 'aud',
					},
				} )
			)
		).toEqual( [ 'cad', 'usd', 'eur', 'gbp' ] );
	} );

	it( 'falls back to the first overview currency when the selected currency is unavailable', () => {
		expect(
			getSelectedBalanceCurrency(
				createOverview( {
					account: {
						default_currency: 'aud',
					},
				} ),
				'nzd'
			)
		).toBe( 'cad' );
		expect( getSelectedBalanceCurrency( createOverview(), 'aud' ) ).toBe(
			'usd'
		);
		expect( getSelectedBalanceCurrency( createOverview(), 'eur' ) ).toBe(
			'eur'
		);
	} );

	it( 'finds instant balances by currency', () => {
		expect(
			getInstantBalanceForCurrency( createOverview(), 'usd' )
		).toMatchObject( {
			amount: 900,
			currency: 'usd',
			fee_percentage: 1.5,
		} );
		expect(
			getInstantBalanceForCurrency( createOverview(), 'eur' )
		).toBeNull();
	} );

	it( 'formats monthly anchor labels', () => {
		expect( getMonthlyAnchorLabel( 1 ) ).toBe( '1st' );
		expect( getMonthlyAnchorLabel( 2 ) ).toBe( '2nd' );
		expect( getMonthlyAnchorLabel( 3 ) ).toBe( '3rd' );
		expect( getMonthlyAnchorLabel( 15 ) ).toBe( '15th' );
		expect( getMonthlyAnchorLabel( 21 ) ).toBe( '21st' );
		expect( getMonthlyAnchorLabel( 31 ) ).toBe( 'last day of every month' );
	} );

	it( 'normalizes payout status class names', () => {
		expect( getPayoutStatusClassName( 'in_transit' ) ).toBe(
			'woocommerce-woopayments-overview__status-chip--in-transit'
		);
		expect( getPayoutStatusClassName( 'paid' ) ).toBe(
			'woocommerce-woopayments-overview__status-chip--paid'
		);
	} );
} );
