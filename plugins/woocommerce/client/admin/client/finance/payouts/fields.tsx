/**
 * External dependencies
 */
import { Button, ExternalLink } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { ProviderCell } from '../components/provider-cell';
import { PayoutStatusBadge } from '../components/payout-status-badge';
import { AmountFormatter } from '../utils/format-amount';
import { formatFinanceDate, EMPTY_VALUE } from '../utils/format-date';
import { getPayoutStatusLabel, PAYOUT_STATUSES } from '../utils/payout-status';
import type { FinanceProvider, Payout } from '../types';

export const PAYOUT_FIELD_IDS = {
	provider: 'provider',
	id: 'id',
	amount: 'amount',
	status: 'status',
	dateInitiated: 'date_initiated',
	dateExpected: 'date_expected',
	bankAccount: 'bank_account',
	providerStatus: 'provider_status',
	providerLink: 'provider_link',
} as const;

export const DEFAULT_VISIBLE_FIELDS = [
	PAYOUT_FIELD_IDS.provider,
	PAYOUT_FIELD_IDS.dateInitiated,
	PAYOUT_FIELD_IDS.status,
	PAYOUT_FIELD_IDS.providerStatus,
	PAYOUT_FIELD_IDS.bankAccount,
	PAYOUT_FIELD_IDS.id,
	PAYOUT_FIELD_IDS.dateExpected,
	PAYOUT_FIELD_IDS.amount,
	PAYOUT_FIELD_IDS.providerLink,
];

const PINNED_FIELDS: string[] = [
	PAYOUT_FIELD_IDS.provider,
];

/**
 * Keep the provider as the first column, whatever order the view asks for.
 *
 * DataViews can only lock column moving for the whole table, so the order is normalised instead.
 *
 * @param fields The visible field ids from the view.
 */
export const pinPayoutFields = ( fields?: string[] ): string[] => [
	...PINNED_FIELDS,
	...( fields ?? [] ).filter( ( id ) => ! PINNED_FIELDS.includes( id ) ),
];

type FieldDeps = {
	providersById: Record< string, FinanceProvider >;
	formatAmount: AmountFormatter;
	onSelectPayout: ( payout: Payout ) => void;
};

/**
 * DataViews fields for a payout. Sorting and filtering are off because the API supports neither.
 *
 * @param deps                The dependencies used to render cells.
 * @param deps.providersById  The known providers keyed by id.
 * @param deps.formatAmount   The amount formatter.
 * @param deps.onSelectPayout Called with the payout whose id was clicked.
 */
export const getPayoutFields = ( {
	providersById,
	formatAmount,
	onSelectPayout,
}: FieldDeps ): Field< Payout >[] => [
	{
		id: PAYOUT_FIELD_IDS.provider,
		label: __( 'Provider', 'woocommerce' ),
		enableSorting: false,
		enableHiding: false,
		filterBy: false,
		getValue: ( { item } ) =>
			providersById[ item.provider_id ]?.title ?? item.provider_id,
		render: ( { item } ) => (
			<ProviderCell
				provider={ providersById[ item.provider_id ] }
				fallbackId={ item.provider_id }
			/>
		),
	},
	{
		id: PAYOUT_FIELD_IDS.id,
		label: __( 'Payout ID', 'woocommerce' ),
		enableSorting: false,
		enableHiding: false,
		filterBy: false,
		getValue: ( { item } ) => item.id,
		render: ( { item } ) => (
			<Button
				className="woocommerce-finance-payouts__id-button"
				variant="link"
				onClick={ () => onSelectPayout( item ) }
			>
				{ item.id }
			</Button>
		),
	},
	{
		id: PAYOUT_FIELD_IDS.amount,
		label: __( 'Amount', 'woocommerce' ),
		enableSorting: false,
		filterBy: false,
		getValue: ( { item } ) => formatAmount( item.amount, item.currency ),
	},
	{
		id: PAYOUT_FIELD_IDS.status,
		label: __( 'Status', 'woocommerce' ),
		enableSorting: false,
		filterBy: false,
		elements: PAYOUT_STATUSES.map( ( status ) => ( {
			value: status,
			label: getPayoutStatusLabel( status ),
		} ) ),
		getValue: ( { item } ) => item.status,
		render: ( { item } ) => <PayoutStatusBadge status={ item.status } />,
	},
	{
		id: PAYOUT_FIELD_IDS.dateInitiated,
		label: __( 'Date initiated', 'woocommerce' ),
		enableSorting: false,
		filterBy: false,
		getValue: ( { item } ) => formatFinanceDate( item.date_initiated ),
	},
	{
		id: PAYOUT_FIELD_IDS.dateExpected,
		label: __( 'Expected date', 'woocommerce' ),
		enableSorting: false,
		filterBy: false,
		getValue: ( { item } ) => formatFinanceDate( item.date_expected ),
	},
	{
		id: PAYOUT_FIELD_IDS.bankAccount,
		label: __( 'Bank account', 'woocommerce' ),
		enableSorting: false,
		filterBy: false,
		getValue: ( { item } ) => item.bank_account ?? EMPTY_VALUE,
	},
	{
		id: PAYOUT_FIELD_IDS.providerStatus,
		label: __( 'Provider status', 'woocommerce' ),
		enableSorting: false,
		filterBy: false,
		getValue: ( { item } ) => item.provider_status ?? EMPTY_VALUE,
	},
	{
		id: PAYOUT_FIELD_IDS.providerLink,
		label: __( 'Provider link', 'woocommerce' ),
		enableSorting: false,
		filterBy: false,
		getValue: ( { item } ) => item.provider_link?.title ?? EMPTY_VALUE,
		render: ( { item } ) =>
			item.provider_link?.url ? (
				<ExternalLink
					href={ item.provider_link.url }
					rel="noopener noreferrer"
				>
					{ item.provider_link.title }
				</ExternalLink>
			) : (
				EMPTY_VALUE
			),
	},
];
