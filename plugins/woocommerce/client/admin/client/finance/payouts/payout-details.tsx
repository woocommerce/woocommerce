/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import { ProviderCell } from '../components/provider-cell';
import { PayoutStatusBadge } from '../components/payout-status-badge';
import { AmountFormatter } from '../utils/format-amount';
import { formatFinanceDate, EMPTY_VALUE } from '../utils/format-date';
import type { FinanceProvider, Payout } from '../types';

type PayoutDetailsProps = {
	payout: Payout;
	provider?: FinanceProvider;
	formatAmount: AmountFormatter;
};

/**
 * Read-only summary of one payout, shown in the details drawer.
 */
export const PayoutDetails = ( {
	payout,
	provider,
	formatAmount,
}: PayoutDetailsProps ) => {
	const rows: { label: string; value: ReactNode }[] = [
		{
			label: __( 'Provider', 'woocommerce' ),
			value: (
				<ProviderCell
					provider={ provider }
					fallbackId={ payout.provider_id }
				/>
			),
		},
		{
			label: __( 'Amount', 'woocommerce' ),
			value: formatAmount( payout.amount, payout.currency ),
		},
		{
			label: __( 'Status', 'woocommerce' ),
			value: <PayoutStatusBadge status={ payout.status } />,
		},
		{
			label: __( 'Date initiated', 'woocommerce' ),
			value: formatFinanceDate( payout.date_initiated ),
		},
		{
			label: __( 'Expected date', 'woocommerce' ),
			value: formatFinanceDate( payout.date_expected ),
		},
		{
			label: __( 'Bank account', 'woocommerce' ),
			value: payout.bank_account ?? EMPTY_VALUE,
		},
		{
			label: __( 'Provider status', 'woocommerce' ),
			value: payout.provider_status ?? EMPTY_VALUE,
		},
		{
			label: __( 'Payout ID', 'woocommerce' ),
			value: payout.id,
		},
	];

	return (
		<div className="woocommerce-finance-payout-details">
			<dl className="woocommerce-finance-payout-details__list">
				{ rows.map( ( row ) => (
					<div
						key={ row.label }
						className="woocommerce-finance-payout-details__row"
					>
						<dt>{ row.label }</dt>
						<dd>{ row.value }</dd>
					</div>
				) ) }
			</dl>
			{ payout.link && (
				<Button
					variant="secondary"
					href={ payout.link.url }
					target="_blank"
					rel="noreferrer"
				>
					{ payout.link.title }
				</Button>
			) }
		</div>
	);
};
